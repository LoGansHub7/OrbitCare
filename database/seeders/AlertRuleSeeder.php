<?php

namespace Database\Seeders;

use App\Models\AlertRule;
use App\Models\Hazard;
use App\Models\MetricDefinition;
use Illuminate\Database\Seeder;

class AlertRuleSeeder extends Seeder
{
    public function run(): void
    {
        $restingHeartRate = MetricDefinition::where('code', 'resting_heart_rate')->value('id');
        $hazard = fn (string $slug) => Hazard::where('slug', $slug)->value('id');

        AlertRule::updateOrCreate(
            ['code' => 'sleep_fatigue_review'],
            [
                'version' => 'v1',
                'metric_definition_id' => null,
                'hazard_id' => $hazard('isolation'),
                'severity' => 'review',
                'parameters' => [
                    'min_samples' => 3,
                    'sleep_delta_hours' => 1.0,
                    'fatigue_delta' => 1.0,
                ],
                'explanation_template' => 'Recent sleep and fatigue trend suggests a review. This is an experimental alert, not a medical diagnosis.',
                'evidence_reference' => 'docs/research-register.md',
                'is_active' => true,
            ]
        );

        AlertRule::updateOrCreate(
            ['code' => 'resting_heart_rate_change'],
            [
                'version' => 'v1',
                'metric_definition_id' => $restingHeartRate,
                'hazard_id' => $hazard('gravity'),
                'severity' => 'review',
                'parameters' => [
                    'min_samples' => 3,
                    'delta_bpm' => 8.0,
                ],
                'explanation_template' => 'Resting heart rate is trending above the crew member baseline. This is an experimental alert, not a medical diagnosis.',
                'evidence_reference' => 'docs/research-register.md',
                'is_active' => true,
            ]
        );

        $note = ' This is an experimental alert with team-assumed thresholds, not a diagnosis.';
        $hazardRules = [
            ['radiation_dose_rate_rise', 'radiation', 'review', ['recent_samples' => 3, 'min_baseline_samples' => 3, 'rise_ratio' => 1.5, 'priority_ratio' => 2.5, 'freshness_hours' => 72]],
            ['wellbeing_decline_trend', 'isolation', 'review', ['consecutive_declines' => 3, 'min_total_drop' => 1.0, 'freshness_hours' => 72]],
            ['autonomy_escalation_under_delay', 'distance', 'review', ['window_days' => 14, 'delay_threshold_seconds' => 600, 'min_events' => 3, 'escalation_share' => 0.5]],
            ['physical_deconditioning_trend', 'gravity', 'review', ['samples' => 4, 'muscle_decline_pct' => 2.0, 'min_avg_exercise_minutes' => 60, 'freshness_hours' => 72]],
            ['cabin_co2_elevated', 'environment', 'review', ['samples' => 3, 'co2_ppm_limit' => 3000, 'co2_ppm_priority' => 5000, 'freshness_hours' => 72]],
        ];

        foreach ($hazardRules as [$code, $slug, $severity, $parameters]) {
            AlertRule::updateOrCreate(
                ['code' => $code],
                [
                    'version' => 'v1',
                    'hazard_id' => $hazard($slug),
                    'severity' => $severity,
                    'parameters' => $parameters,
                    'explanation_template' => 'Hazard monitoring rule for '.$slug.'.'.$note,
                    'evidence_reference' => 'docs/research-register.md',
                    'is_active' => true,
                ]
            );
        }
    }
}
