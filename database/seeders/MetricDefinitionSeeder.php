<?php

namespace Database\Seeders;

use App\Models\MetricDefinition;
use Illuminate\Database\Seeder;

class MetricDefinitionSeeder extends Seeder
{
    public function run(): void
    {
        // min/max = input plausibility bounds (data quality), NOT medical alert thresholds.
        $defs = [
            ['resting_heart_rate', 'Resting heart rate', 'bpm', 30, 200, 36],
            ['sleep_duration', 'Sleep duration', 'hours', 0, 24, 36],
            ['sleep_quality', 'Sleep quality', 'score', 1, 5, 36],
            ['fatigue_rating', 'Fatigue rating', 'score', 1, 5, 36],
            ['exercise_duration', 'Exercise duration', 'min', 0, 600, 36],
        ];

        foreach ($defs as [$code, $label, $unit, $min, $max, $fresh]) {
            MetricDefinition::updateOrCreate(['code' => $code], [
                'label' => $label, 'unit' => $unit,
                'min_value' => $min, 'max_value' => $max, 'freshness_hours' => $fresh,
            ]);
        }
    }
}
