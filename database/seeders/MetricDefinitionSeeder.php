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
            ['resting_heart_rate', 'Resting heart rate', 'bpm', 30, 200, 36, 'cardiovascular', 'higher', 'Personal trend; not a population target'],
            ['blood_pressure_systolic', 'Blood pressure — systolic', 'mmHg', 70, 220, 36, 'cardiovascular', 'higher', 'Manual measurement'],
            ['blood_pressure_diastolic', 'Blood pressure — diastolic', 'mmHg', 40, 140, 36, 'cardiovascular', 'higher', 'Manual measurement'],
            ['body_temperature', 'Body temperature', '°C', 34, 42, 24, 'immune_general', 'higher', 'Manual measurement'],
            ['spo2', 'Oxygen saturation', '%', 70, 100, 24, 'immune_general', 'lower', 'Manual measurement'],
            ['body_mass', 'Body mass', 'kg', 30, 200, 168, 'body_mass', 'either', 'Manual measurement'],
            ['cabin_co2_sim', 'Cabin CO₂', 'ppm', 0, 20000, 24, 'environment', 'higher', 'Simulation only'],
            ['radiation_dose_sim', 'Radiation dose', 'µSv/day', 0, 100000, 24, 'environment', 'higher', 'Simulation only'],
            ['sleep_duration', 'Sleep duration', 'hours', 0, 24, 36, 'sleep_circadian', 'lower', 'Daily self-report'],
            ['sleep_quality', 'Sleep quality', 'score', 1, 5, 36, 'sleep_circadian', 'lower', '1 = very poor; 5 = very good'],
            ['fatigue_rating', 'Fatigue rating', 'score', 1, 5, 36, 'behavioural', 'higher', '1 = not fatigued; 5 = extremely fatigued'],
            ['exercise_duration', 'Exercise duration', 'min', 0, 600, 36, 'musculoskeletal', 'lower', 'Daily total'],
        ];

        foreach ($defs as [$code, $label, $unit, $min, $max, $fresh, $category, $direction, $scale]) {
            MetricDefinition::updateOrCreate(['code' => $code], [
                'label' => $label, 'unit' => $unit,
                'min_value' => $min, 'max_value' => $max, 'freshness_hours' => $fresh,
                'category' => $category, 'direction_of_concern' => $direction, 'scale_description' => $scale,
            ]);
        }
    }
}
