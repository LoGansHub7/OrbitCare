<?php

namespace App\Services;

use App\Models\AlertRule;
use App\Models\AutonomyEvent;
use App\Models\CabinReading;
use App\Models\CrewProfile;
use App\Models\PhysicalReading;
use App\Models\RadiationReading;
use App\Models\WellbeingCheckin;
use Illuminate\Support\Collection;

/**
 * Experimental rules over the five hazard monitoring tables.
 * All thresholds live in alert_rules.parameters and are team assumptions, not NASA limits.
 */
class HazardRuleEvaluator
{
    public const CODES = [
        'radiation_dose_rate_rise',
        'wellbeing_decline_trend',
        'autonomy_escalation_under_delay',
        'physical_deconditioning_trend',
        'cabin_co2_elevated',
    ];

    public function supports(string $code): bool
    {
        return in_array($code, self::CODES, true);
    }

    public function evaluate(CrewProfile $crew, AlertRule $rule): array
    {
        return match ($rule->code) {
            'radiation_dose_rate_rise' => $this->radiation($crew, $rule),
            'wellbeing_decline_trend' => $this->wellbeing($crew, $rule),
            'autonomy_escalation_under_delay' => $this->autonomy($crew, $rule),
            'physical_deconditioning_trend' => $this->physical($crew, $rule),
            'cabin_co2_elevated' => $this->cabin($crew, $rule),
        };
    }

    private function radiation(CrewProfile $crew, AlertRule $rule): array
    {
        $recentCount = (int) ($rule->parameters['recent_samples'] ?? 3);
        $minBaseline = (int) ($rule->parameters['min_baseline_samples'] ?? 3);
        $ratio = (float) ($rule->parameters['rise_ratio'] ?? 1.5);

        $readings = RadiationReading::query()
            ->where('crew_profile_id', $crew->id)
            ->orderByDesc('recorded_at')
            ->get();

        if ($this->stale($readings, $rule) || $readings->count() < $recentCount + $minBaseline) {
            return $this->insufficient($rule, 'Radiation trend cannot be evaluated: too few or stale dose-rate readings.', $readings);
        }

        $recentAvg = round((float) $readings->take($recentCount)->avg('dose_rate'), 4);
        $baseline = round((float) $readings->slice($recentCount)->avg('dose_rate'), 4);

        if ($baseline <= 0 || $recentAvg < $baseline * $ratio) {
            return ['state' => 'clear'];
        }

        return [
            'state' => 'triggered',
            'severity' => $recentAvg >= $baseline * (float) ($rule->parameters['priority_ratio'] ?? 2.5) ? 'priority_review' : 'review',
            'summary' => 'Recent radiation dose rate is above the crew member baseline.',
            'explanation' => sprintf(
                'The last %d dose-rate readings average %.3f mSv/day, %.1fx the earlier baseline of %.3f mSv/day (alert at %.1fx). This is an experimental rule (%s), not a dose limit or diagnosis.',
                $recentCount, $recentAvg, $recentAvg / $baseline, $baseline, $ratio, $rule->version,
            ),
            'evidence' => [
                'recent_avg_dose_rate_msv_day' => $recentAvg,
                'baseline_dose_rate_msv_day' => $baseline,
                'ratio' => round($recentAvg / $baseline, 2),
                'recent_reading_ids' => $readings->take($recentCount)->pluck('id')->all(),
                'sample_count' => $readings->count(),
                'includes_simulated' => $readings->contains('is_simulated', true),
                'rule_version' => $rule->version,
            ],
        ];
    }

    private function wellbeing(CrewProfile $crew, AlertRule $rule): array
    {
        $steps = (int) ($rule->parameters['consecutive_declines'] ?? 3);
        $minDrop = (float) ($rule->parameters['min_total_drop'] ?? 1.0);

        $checkins = WellbeingCheckin::query()
            ->where('crew_profile_id', $crew->id)
            ->orderByDesc('recorded_at')
            ->limit($steps + 1)
            ->get();

        if ($this->stale($checkins, $rule) || $checkins->count() < $steps + 1) {
            return $this->insufficient($rule, 'Wellbeing trend cannot be evaluated: too few or stale check-ins.', $checkins);
        }

        $moods = $checkins->reverse()->pluck('mood')->map(fn ($v) => (int) $v)->values();
        $declining = $moods->every(fn ($mood, $i) => $i === 0 || $mood < $moods[$i - 1]);
        $drop = $moods->first() - $moods->last();

        // An isolated low reading is not a trend: every step must decline.
        if (! $declining || $drop < $minDrop) {
            return ['state' => 'clear'];
        }

        return [
            'state' => 'triggered',
            'severity' => $moods->last() <= 2 ? 'priority_review' : 'review',
            'summary' => 'Mood has declined across consecutive check-ins.',
            'explanation' => sprintf(
                'Mood fell at every one of the last %d check-ins (%s on a 1-5 scale). Isolation and confinement can affect wellbeing; this is an experimental trend rule (%s), not a diagnosis.',
                $steps, $moods->implode(' → '), $rule->version,
            ),
            'evidence' => [
                'mood_series_oldest_first' => $moods->all(),
                'total_drop' => $drop,
                'checkin_ids' => $checkins->pluck('id')->all(),
                'includes_simulated' => $checkins->contains('is_simulated', true),
                'rule_version' => $rule->version,
            ],
        ];
    }

    private function autonomy(CrewProfile $crew, AlertRule $rule): array
    {
        $windowDays = (int) ($rule->parameters['window_days'] ?? 14);
        $delayThreshold = (int) ($rule->parameters['delay_threshold_seconds'] ?? 600);
        $minEvents = (int) ($rule->parameters['min_events'] ?? 3);
        $shareLimit = (float) ($rule->parameters['escalation_share'] ?? 0.5);

        $events = AutonomyEvent::query()
            ->where('crew_profile_id', $crew->id)
            ->where('recorded_at', '>=', now()->subDays($windowDays))
            ->where('comms_delay_seconds', '>=', $delayThreshold)
            ->orderByDesc('recorded_at')
            ->get();

        if ($events->count() < $minEvents) {
            return $this->insufficient($rule, 'Autonomy pattern cannot be evaluated: too few delayed-communication events in the window.', $events);
        }

        $escalated = $events->where('escalated_to_earth', true)->count();
        $share = round($escalated / $events->count(), 2);

        if ($share < $shareLimit) {
            return ['state' => 'clear'];
        }

        return [
            'state' => 'triggered',
            'severity' => 'review',
            'summary' => 'Many delayed-link events were escalated to Earth.',
            'explanation' => sprintf(
                '%d of %d events with a one-way delay of %d s or more were escalated to Earth (%.0f%%, alert at %.0f%%). With long delays, onboard decision support matters; this is an experimental rule (%s).',
                $escalated, $events->count(), $delayThreshold, $share * 100, $shareLimit * 100, $rule->version,
            ),
            'evidence' => [
                'events_considered' => $events->count(),
                'escalated_to_earth' => $escalated,
                'escalation_share' => $share,
                'max_delay_seconds' => (int) $events->max('comms_delay_seconds'),
                'event_ids' => $events->pluck('id')->all(),
                'window_days' => $windowDays,
                'includes_simulated' => $events->contains('is_simulated', true),
                'rule_version' => $rule->version,
            ],
        ];
    }

    private function physical(CrewProfile $crew, AlertRule $rule): array
    {
        $samples = (int) ($rule->parameters['samples'] ?? 4);
        $declinePct = (float) ($rule->parameters['muscle_decline_pct'] ?? 2.0);
        $minExercise = (float) ($rule->parameters['min_avg_exercise_minutes'] ?? 60);

        $readings = PhysicalReading::query()
            ->where('crew_profile_id', $crew->id)
            ->whereNotNull('muscle_index')
            ->orderByDesc('recorded_at')
            ->limit($samples)
            ->get();

        if ($this->stale($readings, $rule) || $readings->count() < $samples) {
            return $this->insufficient($rule, 'Physical trend cannot be evaluated: too few or stale muscle-index readings.', $readings);
        }

        $ordered = $readings->reverse()->values();
        $first = (float) $ordered->first()->muscle_index;
        $last = (float) $ordered->last()->muscle_index;
        $changePct = $first > 0 ? round(($first - $last) / $first * 100, 2) : 0.0;
        $avgExercise = round((float) $readings->avg('exercise_minutes'), 1);

        if ($changePct < $declinePct || $avgExercise >= $minExercise) {
            return ['state' => 'clear'];
        }

        return [
            'state' => 'triggered',
            'severity' => 'review',
            'summary' => 'Muscle index is falling while exercise time is low.',
            'explanation' => sprintf(
                'Muscle index fell %.1f%% over the last %d readings (%.2f to %.2f) while exercise averaged %.0f min/day (below %.0f). Microgravity deconditioning is countered by exercise; this is an experimental rule (%s).',
                $changePct, $samples, $first, $last, $avgExercise, $minExercise, $rule->version,
            ),
            'evidence' => [
                'muscle_index_first' => $first,
                'muscle_index_last' => $last,
                'decline_pct' => $changePct,
                'avg_exercise_minutes' => $avgExercise,
                'reading_ids' => $readings->pluck('id')->all(),
                'includes_simulated' => $readings->contains('is_simulated', true),
                'rule_version' => $rule->version,
            ],
        ];
    }

    private function cabin(CrewProfile $crew, AlertRule $rule): array
    {
        $samples = (int) ($rule->parameters['samples'] ?? 3);
        $limit = (float) ($rule->parameters['co2_ppm_limit'] ?? 3000);
        $priorityLimit = (float) ($rule->parameters['co2_ppm_priority'] ?? 5000);

        $readings = CabinReading::query()
            ->where('mission_id', $crew->mission_id)
            ->orderByDesc('recorded_at')
            ->limit($samples)
            ->get();

        if ($this->stale($readings, $rule) || $readings->count() < $samples) {
            return $this->insufficient($rule, 'Cabin air cannot be evaluated: too few or stale CO2 readings.', $readings);
        }

        // Sustained means every reading in the window is at or above the limit.
        if ($readings->min('co2_level') < $limit) {
            return ['state' => 'clear'];
        }

        $avg = round((float) $readings->avg('co2_level'), 1);

        return [
            'state' => 'triggered',
            'severity' => $avg >= $priorityLimit ? 'priority_review' : 'review',
            'summary' => 'Cabin CO2 has stayed above the review level.',
            'explanation' => sprintf(
                'The last %d cabin CO2 readings all met or exceeded %.0f ppm (average %.0f ppm). The limit is a team assumption, not a NASA standard; this is an experimental rule (%s).',
                $samples, $limit, $avg, $rule->version,
            ),
            'evidence' => [
                'avg_co2_ppm' => $avg,
                'limit_ppm' => $limit,
                'reading_ids' => $readings->pluck('id')->all(),
                'includes_simulated' => $readings->contains('is_simulated', true),
                'rule_version' => $rule->version,
            ],
        ];
    }

    private function stale(Collection $readings, AlertRule $rule): bool
    {
        $latest = $readings->first()?->recorded_at;
        $freshness = (int) ($rule->parameters['freshness_hours'] ?? 72);

        return $latest === null || $latest->lt(now()->subHours($freshness));
    }

    private function insufficient(AlertRule $rule, string $explanation, Collection $readings): array
    {
        return [
            'state' => 'insufficient_data',
            'summary' => 'Insufficient data for experimental evaluation.',
            'explanation' => $explanation,
            'evidence' => ['sample_count' => $readings->count(), 'rule_version' => $rule->version],
        ];
    }
}
