<?php

namespace App\Services;

use App\Models\BodyRegion;
use App\Models\CabinReading;
use App\Models\CrewProfile;
use App\Models\HealthReading;
use App\Models\PhysicalReading;
use App\Models\RadiationReading;
use App\Models\WellbeingCheckin;

/**
 * Builds the Body Map view model for a crew member by reusing the existing
 * alert/hazard rules engine as the single source of truth for status.
 * This service never recomputes thresholds; it only reads AlertEvaluationService
 * output and the latest raw metric values for display.
 *
 * The viewer is a 2D SVG figure: regions are matched in the front end by
 * `slug` (data-region) or `svg_id`.
 */
class BodyMapService
{
    /** Per-request cache of latest readings, keyed by crew + metric. */
    private array $latestCache = [];

    public function __construct(private readonly AlertEvaluationService $alerts = new AlertEvaluationService) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function regionsFor(CrewProfile $crew): array
    {
        $states = $this->alerts->currentStates($crew, true);
        $hazardStatuses = $this->alerts->hazardStatuses($crew);

        $regions = BodyRegion::query()
            ->with(['metrics.metricDefinition', 'hazards.hazard', 'guidance'])
            ->orderBy('sort_order')
            ->get();

        return $regions->map(fn (BodyRegion $region) => $this->buildRegion($region, $crew, $states, $hazardStatuses))->all();
    }

    public function regionDetail(CrewProfile $crew, string $slug): ?array
    {
        $region = BodyRegion::query()
            ->with(['metrics.metricDefinition', 'hazards.hazard', 'guidance', 'incidents' => fn ($q) => $q->where('crew_profile_id', $crew->id)->latest('recorded_at')])
            ->where('slug', $slug)
            ->first();

        if (! $region) {
            return null;
        }

        $states = $this->alerts->currentStates($crew, true);
        $hazardStatuses = $this->alerts->hazardStatuses($crew);

        $data = $this->buildRegion($region, $crew, $states, $hazardStatuses);

        $data['metric_history'] = $region->metrics
            ->map(function ($m, $i) use ($crew, $data) {
                return $this->metricHistory($crew, $m) + [
                    'label' => $data['metrics'][$i]['label'] ?? null,
                    'unit' => $data['metrics'][$i]['unit'] ?? null,
                ];
            })
            ->all();

        $data['recent_incidents'] = $region->incidents
            ->take(5)
            ->map(fn ($incident) => [
                'id' => $incident->id,
                'recorded_at' => $incident->recorded_at?->toIso8601String(),
                'associated_symptoms' => $incident->associated_symptoms,
            ])
            ->values()
            ->all();

        return $data;
    }

    private function buildRegion(BodyRegion $region, CrewProfile $crew, array $states, array $hazardStatuses): array
    {
        $metrics = $region->metrics->map(fn ($m) => $this->metricSnapshot($crew, $m))->all();

        $statusLevel = $this->statusLevelForRegion($region, $states, $hazardStatuses);

        $guidance = $region->guidanceFor($statusLevel);

        return [
            'slug' => $region->slug,
            'name' => $region->name,
            'body_system' => $region->body_system,
            'svg_id' => $region->svg_id,
            'function_text' => $region->function_text,
            'status_level' => $statusLevel,
            'as_of' => now()->toIso8601String(),
            'metrics' => $metrics,
            'hazards' => $region->hazards->map(fn ($rh) => [
                'slug' => $rh->hazard->slug,
                'name' => $rh->hazard->name,
                'icon' => $rh->hazard->icon,
                'color' => $rh->hazard->color,
                'why_it_matters' => $rh->why_it_matters,
            ])->all(),
            'guidance' => $guidance ? [
                'self_care_steps' => $guidance->self_care_steps,
                'red_flags' => $guidance->red_flags,
                'escalate_to' => $guidance->escalate_to,
                'sources' => $guidance->sources,
            ] : null,
        ];
    }

    /**
     * Status priority mirrors AlertEvaluationService::hazardStatuses():
     * priority_review > review > insufficient_data > no_flag, mapped to
     * the Body Map vocabulary: urgent > review > watch > nominal.
     */
    private function statusLevelForRegion(BodyRegion $region, array $states, array $hazardStatuses): string
    {
        $linkedHazardIds = $region->hazards->pluck('hazard_id')->all();

        $relevantStates = $region->metrics
            ->pluck('hazard_metric_code')
            ->filter()
            ->map(fn ($code) => $this->ruleCodeForMetric($code))
            ->filter()
            ->map(fn ($code) => $states[$code] ?? null)
            ->filter();

        $worstFromHazards = collect($linkedHazardIds)
            ->map(fn ($id) => $hazardStatuses[$id]['status'] ?? null)
            ->filter();

        if ($worstFromHazards->contains('priority_review')) {
            return 'urgent';
        }

        if ($worstFromHazards->contains('review') || $relevantStates->contains('triggered')) {
            return 'review';
        }

        if ($worstFromHazards->isNotEmpty() && $worstFromHazards->every(fn ($s) => $s === 'insufficient_data')) {
            return 'watch';
        }

        if ($relevantStates->isNotEmpty() && $relevantStates->every(fn ($s) => $s === 'insufficient_data')) {
            return 'watch';
        }

        return 'nominal';
    }

    private function ruleCodeForMetric(string $hazardMetricCode): ?string
    {
        return match ($hazardMetricCode) {
            'radiation_dose' => 'radiation_dose_rate_rise',
            'wellbeing_mood' => 'wellbeing_decline_trend',
            'autonomy_escalation' => 'autonomy_escalation_under_delay',
            'physical_deconditioning' => 'physical_deconditioning_trend',
            'cabin_co2' => 'cabin_co2_elevated',
            default => null,
        };
    }

    private function metricSnapshot(CrewProfile $crew, $regionMetric): array
    {
        if ($regionMetric->metric_definition_id) {
            $reading = $this->latestCache[$crew->id.':def:'.$regionMetric->metric_definition_id] ??=
                HealthReading::query()
                    ->where('crew_profile_id', $crew->id)
                    ->where('metric_definition_id', $regionMetric->metric_definition_id)
                    ->orderByDesc('observed_at')
                    ->first();

            return [
                'label' => $regionMetric->metricDefinition?->label,
                'unit' => $regionMetric->metricDefinition?->unit,
                'value' => $reading?->value,
                'observed_at' => $reading?->observed_at?->toIso8601String(),
                'source' => $reading?->source,
                'freshness' => $this->freshness($reading?->observed_at, $regionMetric->metricDefinition?->freshness_hours),
            ];
        }

        return $this->hazardMetricSnapshot($crew, $regionMetric->hazard_metric_code);
    }

    private function hazardMetricSnapshot(CrewProfile $crew, ?string $code): array
    {
        $key = $crew->id.':hazard:'.$code;

        return $this->latestCache[$key] ??= match ($code) {
            'radiation_dose' => $this->fromLatest(
                RadiationReading::query()->where('crew_profile_id', $crew->id)->orderByDesc('recorded_at')->first(),
                'Radiation dose rate', 'mSv/day', 'dose_rate', 'recorded_at'
            ),
            'wellbeing_mood' => $this->fromLatest(
                WellbeingCheckin::query()->where('crew_profile_id', $crew->id)->orderByDesc('recorded_at')->first(),
                'Mood', '1-5 scale', 'mood', 'recorded_at'
            ),
            'cabin_co2' => $this->fromLatest(
                CabinReading::query()->orderByDesc('recorded_at')->first(),
                'Cabin CO2', 'ppm', 'co2_level', 'recorded_at'
            ),
            'physical_deconditioning' => $this->fromLatest(
                PhysicalReading::query()->where('crew_profile_id', $crew->id)->orderByDesc('recorded_at')->first(),
                'Exercise minutes', 'minutes', 'exercise_minutes', 'recorded_at'
            ),
            default => ['label' => null, 'unit' => null, 'value' => null, 'observed_at' => null, 'source' => null, 'freshness' => 'no_data'],
        };
    }

    private function fromLatest(?object $model, string $label, string $unit, string $valueField, string $timeField): array
    {
        if (! $model) {
            return ['label' => $label, 'unit' => $unit, 'value' => null, 'observed_at' => null, 'source' => null, 'freshness' => 'no_data'];
        }

        return [
            'label' => $label,
            'unit' => $unit,
            'value' => $model->{$valueField},
            'observed_at' => $model->{$timeField}?->toIso8601String(),
            'source' => ($model->is_simulated ?? false) ? 'simulation' : 'manual',
            'freshness' => $this->freshness($model->{$timeField}, 48),
        ];
    }

    private function freshness($observedAt, ?int $freshnessHours): string
    {
        if (! $observedAt) {
            return 'no_data';
        }

        $hours = $freshnessHours ?? 48;

        return $observedAt->lt(now()->subHours($hours)) ? 'stale' : 'current';
    }

    private function metricHistory(CrewProfile $crew, $regionMetric): array
    {
        if ($regionMetric->metric_definition_id) {
            $points = HealthReading::query()
                ->where('crew_profile_id', $crew->id)
                ->where('metric_definition_id', $regionMetric->metric_definition_id)
                ->orderByDesc('observed_at')
                ->limit(30)
                ->get(['value', 'observed_at'])
                ->reverse()
                ->values();

            return [
                'metric' => $regionMetric->metricDefinition?->code,
                'points' => $points->map(fn ($r) => ['t' => $r->observed_at->toIso8601String(), 'v' => (float) $r->value])->all(),
            ];
        }

        return $this->hazardMetricHistory($crew, $regionMetric->hazard_metric_code);
    }

    /**
     * History for metrics that live in the hazard tables rather than
     * health_readings, so every region can draw a sparkline.
     */
    private function hazardMetricHistory(CrewProfile $crew, ?string $code): array
    {
        [$query, $field] = match ($code) {
            'radiation_dose' => [RadiationReading::query()->where('crew_profile_id', $crew->id), 'dose_rate'],
            'wellbeing_mood' => [WellbeingCheckin::query()->where('crew_profile_id', $crew->id), 'mood'],
            'physical_deconditioning' => [PhysicalReading::query()->where('crew_profile_id', $crew->id), 'exercise_minutes'],
            'cabin_co2' => [CabinReading::query(), 'co2_level'],
            default => [null, null],
        };

        if (! $query) {
            return ['metric' => $code, 'points' => []];
        }

        $points = $query
            ->orderByDesc('recorded_at')
            ->limit(30)
            ->get(['recorded_at', $field])
            ->reverse()
            ->values();

        return [
            'metric' => $code,
            'points' => $points->map(fn ($r) => [
                't' => $r->recorded_at->toIso8601String(),
                'v' => (float) $r->{$field},
            ])->all(),
        ];
    }
}