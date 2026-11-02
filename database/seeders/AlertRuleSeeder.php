<?php

namespace Database\Seeders;

use App\Models\AlertRule;
use App\Models\MetricDefinition;
use Illuminate\Database\Seeder;

class AlertRuleSeeder extends Seeder
{
    public function run(): void
    {
        $restingHeartRate = MetricDefinition::where('code', 'resting_heart_rate')->value('id');

        AlertRule::updateOrCreate(
            ['code' => 'sleep_fatigue_review'],
            [
                'version' => 'v1',
                'metric_definition_id' => null,
                'severity' => 'review',
                'parameters' => [
                    'min_samples' => 3,
                    'sleep_threshold_hours' => 6.5,
                    'fatigue_threshold' => 3.5,
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
    }
}
