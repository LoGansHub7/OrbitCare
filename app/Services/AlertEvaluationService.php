<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\AlertRule;
use App\Models\BaselineProfile;
use App\Models\CrewProfile;
use App\Models\MetricDefinition;

class AlertEvaluationService
{
    public function evaluateAll(): void
    {
        CrewProfile::query()->with(['checkins', 'readings'])->get()->each(function (CrewProfile $crew): void {
            $this->evaluateCrew($crew);
        });
    }

    public function evaluateCrew(CrewProfile $crew): void
    {
        AlertRule::query()->where('is_active', true)->get()->each(function (AlertRule $rule) use ($crew): void {
            $result = $this->evaluateRule($crew, $rule);

            if ($result === null) {
                return;
            }

            $existing = Alert::query()
                ->where('crew_profile_id', $crew->id)
                ->where('alert_rule_id', $rule->id)
                ->latest('last_evaluated_at')
                ->first();

            if ($existing && $existing->status === 'closed' && ! is_null($existing->closed_at)) {
                $existing = null;
            }

            if ($existing) {
                $existing->update([
                    'status' => $existing->status,
                    'severity' => $result['severity'],
                    'summary' => $result['summary'],
                    'explanation' => $result['explanation'],
                    'evidence' => $result['evidence'],
                    'last_evaluated_at' => now(),
                    'acknowledged_at' => $existing->acknowledged_at ?? $result['acknowledged_at'],
                    'closed_at' => $existing->closed_at ?? $result['closed_at'],
                ]);

                return;
            }

            Alert::query()->create([
                'crew_profile_id' => $crew->id,
                'alert_rule_id' => $rule->id,
                'status' => $result['status'],
                'severity' => $result['severity'],
                'summary' => $result['summary'],
                'explanation' => $result['explanation'],
                'evidence' => $result['evidence'],
                'first_detected_at' => now(),
                'last_evaluated_at' => now(),
                'acknowledged_at' => $result['acknowledged_at'],
                'closed_at' => $result['closed_at'],
            ]);
        });
    }

    private function evaluateRule(CrewProfile $crew, AlertRule $rule): ?array
    {
        return match ($rule->code) {
            'sleep_fatigue_review' => $this->evaluateSleepFatigueRule($crew, $rule),
            'resting_heart_rate_change' => $this->evaluateHeartRateRule($crew, $rule),
            default => null,
        };
    }

    private function evaluateSleepFatigueRule(CrewProfile $crew, AlertRule $rule): ?array
    {
        $checkins = $crew->checkins()
            ->orderBy('mission_date', 'desc')
            ->take(7)
            ->get();

        if ($checkins->count() < ($rule->parameters['min_samples'] ?? 3)) {
            return null;
        }

        $sleepBaseline = $this->baselineFor($crew, 'sleep_duration');
        $fatigueBaseline = $this->baselineFor($crew, 'fatigue_rating');
        $recentWindow = $checkins->take(3);
        $avgSleep = round((float) $recentWindow->avg('sleep_hours'), 2);
        $avgFatigue = round((float) $recentWindow->avg('fatigue_rating'), 2);
        $sleepThreshold = (float) ($rule->parameters['sleep_threshold_hours'] ?? 6.5);
        $fatigueThreshold = (float) ($rule->parameters['fatigue_threshold'] ?? 3.5);

        $triggered = $avgSleep <= min($sleepBaseline, $sleepThreshold)
            && $avgFatigue >= max($fatigueBaseline, $fatigueThreshold);

        if (! $triggered) {
            return null;
        }

        return [
            'status' => 'open',
            'severity' => 'review',
            'summary' => 'Recent sleep and fatigue trend warrants review.',
            'explanation' => sprintf(
                'The latest three-day window averaged %.1f hours of sleep and %.1f on fatigue, compared with a baseline of %.1f hours and %.1f fatigue. This is an experimental rule, not a diagnosis.',
                $avgSleep,
                $avgFatigue,
                $sleepBaseline,
                $fatigueBaseline,
            ),
            'evidence' => [
                'window_days' => 3,
                'avg_sleep_hours' => $avgSleep,
                'avg_fatigue_score' => $avgFatigue,
                'baseline_sleep_hours' => $sleepBaseline,
                'baseline_fatigue_score' => $fatigueBaseline,
                'sample_count' => $recentWindow->count(),
            ],
            'acknowledged_at' => null,
            'closed_at' => null,
        ];
    }

    private function evaluateHeartRateRule(CrewProfile $crew, AlertRule $rule): ?array
    {
        $def = MetricDefinition::query()->where('code', 'resting_heart_rate')->first();

        if (! $def) {
            return null;
        }

        $readings = $crew->readings()
            ->where('metric_definition_id', $def->id)
            ->orderBy('observed_at', 'desc')
            ->take(7)
            ->get();

        if ($readings->count() < ($rule->parameters['min_samples'] ?? 3)) {
            return null;
        }

        $baseline = $this->baselineFor($crew, 'resting_heart_rate');
        $avg = round((float) $readings->take(3)->avg('value'), 2);
        $threshold = (float) ($rule->parameters['delta_bpm'] ?? 8.0);

        $triggered = $avg >= ($baseline + $threshold);

        if (! $triggered) {
            return null;
        }

        return [
            'status' => 'open',
            'severity' => 'review',
            'summary' => 'Resting heart rate has risen above the baseline trend.',
            'explanation' => sprintf(
                'The latest three-day average resting heart rate is %.1f bpm, which is %.1f bpm above the individual baseline of %.1f bpm. This is an experimental alert and not a medical diagnosis.',
                $avg,
                max(0, $avg - $baseline),
                $baseline,
            ),
            'evidence' => [
                'window_days' => 3,
                'avg_resting_heart_rate_bpm' => $avg,
                'baseline_resting_heart_rate_bpm' => $baseline,
                'delta_bpm' => round($avg - $baseline, 2),
            ],
            'acknowledged_at' => null,
            'closed_at' => null,
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
}
