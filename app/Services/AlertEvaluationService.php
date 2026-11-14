<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\AlertRule;
use App\Models\BaselineProfile;
use App\Models\CrewProfile;
use App\Models\MetricDefinition;

class AlertEvaluationService
{
    public function __construct(private readonly HazardRuleEvaluator $hazardRules = new HazardRuleEvaluator) {}

    public function evaluateAll(): void
    {
        CrewProfile::query()->get()->each(function (CrewProfile $crew): void {
            $this->evaluateCrew($crew);
        });
    }

    public function evaluateCrew(CrewProfile $crew): void
    {
        foreach ($this->evaluateRulesForCrew($crew) as $result) {
            if ($result['state'] !== 'triggered') {
                continue;
            }

            $existing = Alert::query()
                ->where('crew_profile_id', $crew->id)
                ->where('alert_rule_id', $result['rule']->id)
                ->latest('last_evaluated_at')
                ->first();

            if ($existing && $existing->status === 'closed' && ! is_null($existing->closed_at)) {
                $existing = null;
            }

            if ($existing) {
                $updates = ['last_evaluated_at' => now()];
                if ($existing->status === 'open') {
                    $updates += [
                        'severity' => $result['severity'],
                        'summary' => $result['summary'],
                        'explanation' => $result['explanation'],
                        'evidence' => $result['evidence'],
                    ];
                }
                $existing->update($updates);

                if ($existing->status === 'open') {
                    $existing->evidenceSnapshots()->create(['snapshot' => $result['evidence']]);
                }

                continue;
            }

            $alert = Alert::query()->create([
                'crew_profile_id' => $crew->id,
                'alert_rule_id' => $result['rule']->id,
                'status' => 'open',
                'severity' => $result['severity'],
                'summary' => $result['summary'],
                'explanation' => $result['explanation'],
                'evidence' => $result['evidence'],
                'first_detected_at' => now(),
                'last_evaluated_at' => now(),
            ]);
            $alert->evidenceSnapshots()->create(['snapshot' => $result['evidence']]);
        }
    }

    public function currentStates(CrewProfile $crew, bool $includeHazardRules = false): array
    {
        return collect($this->evaluateRulesForCrew($crew))
            ->when(! $includeHazardRules, fn ($results) => $results->reject(fn (array $result, string $code) => $this->hazardRules->supports($code)))
            ->mapWithKeys(fn (array $result, string $code) => [$code => $result['state']])
            ->all();
    }

    private function evaluateRulesForCrew(CrewProfile $crew): array
    {
        return AlertRule::query()
            ->where('is_active', true)
            ->get()
            ->mapWithKeys(function (AlertRule $rule) use ($crew): array {
                $result = $this->evaluateRule($crew, $rule);

                return $result === null ? [] : [$rule->code => $result + ['rule' => $rule]];
            })
            ->all();
    }

    private function evaluateRule(CrewProfile $crew, AlertRule $rule): ?array
    {
        return match ($rule->code) {
            'sleep_fatigue_review' => $this->evaluateSleepFatigueRule($crew, $rule),
            'resting_heart_rate_change' => $this->evaluateHeartRateRule($crew, $rule),
            default => $this->hazardRules->supports($rule->code) ? $this->hazardRules->evaluate($crew, $rule) : null,
        };
    }

    /**
     * Per-hazard status for the dashboard: priority_review > review > insufficient_data > no_flag.
     * Unresolved alerts keep a hazard flagged even when current numbers have recovered.
     *
     * @return array<string, array{status: string, alert_count: int}> keyed by hazard id
     */
    public function hazardStatuses(CrewProfile $crew): array
    {
        $rules = AlertRule::query()->where('is_active', true)->whereNotNull('hazard_id')->get();
        $states = $this->currentStates($crew, true);
        $open = $crew->alerts()
            ->where('status', '!=', 'closed')
            ->with('rule:id,hazard_id')
            ->get()
            ->filter(fn (Alert $alert) => $alert->rule?->hazard_id !== null)
            ->groupBy(fn (Alert $alert) => $alert->rule->hazard_id);

        $result = [];
        foreach ($rules->groupBy('hazard_id') as $hazardId => $hazardRules) {
            $alerts = $open->get($hazardId, collect());
            $evaluated = $hazardRules->map(fn (AlertRule $rule) => $states[$rule->code] ?? 'insufficient_data');

            $status = match (true) {
                $alerts->contains('severity', 'priority_review') => 'priority_review',
                $alerts->isNotEmpty() => 'review',
                $evaluated->every(fn ($state) => $state === 'insufficient_data') => 'insufficient_data',
                default => 'no_flag',
            };

            $result[$hazardId] = ['status' => $status, 'alert_count' => $alerts->count()];
        }

        return $result;
    }

    private function evaluateSleepFatigueRule(CrewProfile $crew, AlertRule $rule): array
    {
        $windowStart = today()->subDays(2);
        $windowEnd = today();
        $checkins = $crew->checkins()
            ->whereDate('mission_date', '>=', $windowStart->toDateString())
            ->whereDate('mission_date', '<=', $windowEnd->toDateString())
            ->orderBy('mission_date', 'desc')
            ->get();

        if ($checkins->count() < ($rule->parameters['min_samples'] ?? 3) || $checkins->first()?->submitted_at->lt(now()->subHours(36))) {
            return $this->insufficient($rule, 'Sleep and fatigue cannot be evaluated because the available check-in data is insufficient or stale.', [
                'sample_count' => $checkins->count(),
                'window_start' => $windowStart->toDateString(),
                'window_end' => $windowEnd->toDateString(),
            ]);
        }

        $sleepBaseline = $this->baselineFor($crew, 'sleep_duration');
        $fatigueBaseline = $this->baselineFor($crew, 'fatigue_rating');
        $avgSleep = round((float) $checkins->avg('sleep_hours'), 2);
        $avgFatigue = round((float) $checkins->avg('fatigue_rating'), 2);
        $sleepDelta = (float) ($rule->parameters['sleep_delta_hours'] ?? 1.0);
        $fatigueDelta = (float) ($rule->parameters['fatigue_delta'] ?? 1.0);

        $triggered = $avgSleep <= ($sleepBaseline - $sleepDelta)
            && $avgFatigue >= ($fatigueBaseline + $fatigueDelta);

        if (! $triggered) {
            return ['state' => 'clear'];
        }

        return [
            'state' => 'triggered',
            'severity' => $avgFatigue >= 4 && $checkins->take(5)->count() >= 5 ? 'priority_review' : 'review',
            'summary' => 'Recent sleep and fatigue trend warrants review.',
            'explanation' => sprintf(
                'Your sleep averaged %.1f hours over the last 3 days versus a baseline of %.1f hours, while fatigue averaged %.1f versus a baseline of %.1f. This is an experimental rule (v%s), not a diagnosis.',
                $avgSleep,
                $sleepBaseline,
                $avgFatigue,
                $fatigueBaseline,
                $rule->version,
            ),
            'evidence' => [
                'window_days' => 3,
                'avg_sleep_hours' => $avgSleep,
                'avg_fatigue_score' => $avgFatigue,
                'baseline_sleep_hours' => $sleepBaseline,
                'baseline_fatigue_score' => $fatigueBaseline,
                'sample_count' => $checkins->count(),
                'window_start' => $windowStart->toDateString(),
                'window_end' => $windowEnd->toDateString(),
            ],
        ];
    }

    private function evaluateHeartRateRule(CrewProfile $crew, AlertRule $rule): array
    {
        $def = MetricDefinition::query()->where('code', 'resting_heart_rate')->first();

        if (! $def) {
            return ['state' => 'insufficient_data'];
        }

        $windowStart = today()->subDays(2);
        $windowEnd = today();
        $readings = $crew->readings()
            ->where('metric_definition_id', $def->id)
            ->whereBetween('observed_at', [$windowStart->copy()->startOfDay(), $windowEnd->copy()->endOfDay()])
            ->orderBy('observed_at', 'desc')
            ->get();
        $dailyAverages = $readings
            ->groupBy(fn ($reading) => $reading->observed_at->toDateString())
            ->map(fn ($day) => (float) $day->avg('value'));

        if ($dailyAverages->count() < ($rule->parameters['min_samples'] ?? 3) || $readings->first()?->observed_at->lt(now()->subHours($def->freshness_hours))) {
            return $this->insufficient($rule, 'Resting heart rate cannot be evaluated because the available readings are insufficient or stale.', [
                'sample_count' => $dailyAverages->count(),
                'window_start' => $windowStart->toDateString(),
                'window_end' => $windowEnd->toDateString(),
            ]);
        }

        $baseline = $this->baselineFor($crew, 'resting_heart_rate');
        $avg = round((float) $dailyAverages->avg(), 2);
        $threshold = (float) ($rule->parameters['delta_bpm'] ?? 8.0);

        $triggered = $avg >= ($baseline + $threshold);

        if (! $triggered) {
            return ['state' => 'clear'];
        }

        return [
            'state' => 'triggered',
            'severity' => $avg >= ($baseline + 15) ? 'priority_review' : 'review',
            'summary' => 'Resting heart rate has risen above the baseline trend.',
            'explanation' => sprintf(
                'The three-day average resting heart rate is %.1f bpm, which is %.1f bpm above the individual baseline of %.1f bpm. This is an experimental alert and not a medical diagnosis.',
                $avg,
                max(0, $avg - $baseline),
                $baseline,
            ),
            'evidence' => [
                'window_days' => 3,
                'avg_resting_heart_rate_bpm' => $avg,
                'baseline_resting_heart_rate_bpm' => $baseline,
                'delta_bpm' => round($avg - $baseline, 2),
                'sample_count' => $dailyAverages->count(),
                'window_start' => $windowStart->toDateString(),
                'window_end' => $windowEnd->toDateString(),
            ],
        ];
    }

    private function baselineFor(CrewProfile $crew, string $metricCode): float
    {
        $metric = MetricDefinition::query()->where('code', $metricCode)->first();

        if (! $metric) {
            return 0.0;
        }

        $baseline = BaselineProfile::query()
            ->where('crew_profile_id', $crew->id)
            ->where('metric_definition_id', $metric->id)
            ->first();

        if (! $baseline) {
            return match ($metricCode) {
                'sleep_duration' => 7.2,
                'fatigue_rating' => 2.0,
                'resting_heart_rate' => 58.0,
                default => 0.0,
            };
        }

        return (float) $baseline->baseline_value;
    }

    private function insufficient(AlertRule $rule, string $explanation, array $evidence): array
    {
        return [
            'state' => 'insufficient_data',
            'summary' => 'Insufficient data for experimental evaluation.',
            'explanation' => $explanation,
            'evidence' => $evidence + ['rule_version' => $rule->version],
        ];
    }
}
