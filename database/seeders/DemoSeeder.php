<?php

namespace Database\Seeders;

use App\Models\BaselineProfile;
use App\Models\CrewProfile;
use App\Models\DailyCheckin;
use App\Models\HealthReading;
use App\Models\MetricDefinition;
use App\Models\Mission;
use App\Models\User;
use App\Services\AlertEvaluationService;
use Illuminate\Database\Seeder;

/**
 * Fictional crew + SYNTHETIC data only. Dates are relative to "today", so re-run
 * `php artisan migrate:fresh --seed` before a demo to keep readings current.
 */
class DemoSeeder extends Seeder
{
    public const PASSWORD = 'orbitcare-demo';

    public function run(): void
    {
        $mission = Mission::updateOrCreate(
            ['name' => 'OrbitCare Demo Mission'],
            ['description' => 'Fictional long-duration mission for demonstration only.', 'starts_on' => today()->subDays(30)]
        );

        User::updateOrCreate(['email' => 'admin@orbitcare.test'], [
            'name' => 'Demo Administrator', 'password' => self::PASSWORD, 'role' => User::ROLE_ADMIN, 'is_active' => true,
        ]);
        User::updateOrCreate(['email' => 'medic@orbitcare.test'], [
            'name' => 'Demo Medical Officer', 'password' => self::PASSWORD, 'role' => User::ROLE_MEDICAL_OFFICER, 'is_active' => true,
        ]);

        // code, name, email, scenario, days since the most recent data point
        $crew = [
            ['OC-01', 'Amara Banda', 'astro1@orbitcare.test', 'stable', 0],
            ['OC-02', 'Tendai Moyo', 'astro2@orbitcare.test', 'declining', 0],
            ['OC-03', 'Lindiwe Phiri', 'astro3@orbitcare.test', 'stale', 6],
        ];

        $hrId = MetricDefinition::where('code', 'resting_heart_rate')->value('id');
        $sleepId = MetricDefinition::where('code', 'sleep_duration')->value('id');
        $fatigueId = MetricDefinition::where('code', 'fatigue_rating')->value('id');

        foreach ($crew as [$code, $name, $email, $scenario, $lastDaysAgo]) {
            // Fixed per-scenario seeds make the fictional data varied but repeatable.
            mt_srand(crc32($code));
            $user = User::updateOrCreate(['email' => $email], [
                'name' => $name, 'password' => self::PASSWORD, 'role' => User::ROLE_ASTRONAUT, 'is_active' => true,
            ]);
            $profile = CrewProfile::updateOrCreate(['crew_code' => $code], ['user_id' => $user->id, 'mission_id' => $mission->id]);

            HealthReading::where('crew_profile_id', $profile->id)->delete();
            DailyCheckin::where('crew_profile_id', $profile->id)->delete();

            $baselineMap = [
                'stable' => ['sleep_duration' => 7.2, 'fatigue_rating' => 2.0, 'resting_heart_rate' => 58.0],
                'declining' => ['sleep_duration' => 7.5, 'fatigue_rating' => 2.3, 'resting_heart_rate' => 61.0],
                'stale' => ['sleep_duration' => 7.0, 'fatigue_rating' => 2.4, 'resting_heart_rate' => 60.0],
            ];

            foreach ($baselineMap[$scenario] as $metricCode => $value) {
                $metric = MetricDefinition::where('code', $metricCode)->first();

                if (! $metric) {
                    continue;
                }

                BaselineProfile::updateOrCreate(
                    ['crew_profile_id' => $profile->id, 'metric_definition_id' => $metric->id],
                    [
                        'baseline_value' => $value,
                        'method' => 'seeded',
                        'reference_period_start' => today()->subDays(30),
                        'reference_period_end' => today(),
                        'min_sample_count' => 3,
                    ]
                );
            }

            for ($i = 0; $i < 14; $i++) { // oldest -> newest
                $date = today()->subDays($lastDaysAgo + (13 - $i));
                $sleepNoise = mt_rand(-25, 25) / 100;
                $heartRateNoise = mt_rand(-18, 18) / 10;
                $exerciseNoise = mt_rand(-7, 7);
                $drift = max(0, ($i - 4) / 9); // 0 until day 4, then ramps to 1

                if ($scenario === 'declining') {
                    $sleep = 7.3 - 2.6 * $drift + $sleepNoise;
                    $quality = max(1, 4 - (int) floor(max(0, $i - 4) / 3));
                    $fatigue = min(5, 2 + (int) floor(max(0, $i - 4) / 3));
                    $hr = 58 + 10 * $drift + $heartRateNoise;
                    $exercise = max(10, 45 - 3 * max(0, $i - 4) + $exerciseNoise);
                    $notes = $i >= 11 ? 'Difficulty concentrating.' : null;
                } else {
                    $sleep = 7.3 + $sleepNoise;
                    $quality = $sleepNoise < -0.16 ? 3 : 4;
                    $fatigue = $sleepNoise > 0.18 ? 3 : 2;
                    $hr = 58 + $heartRateNoise;
                    $exercise = 45 + $exerciseNoise;
                    $notes = null;
                }

                HealthReading::create([
                    'crew_profile_id' => $profile->id,
                    'metric_definition_id' => $hrId,
                    'value' => round($hr, 1),
                    'observed_at' => $date->copy()->setTime(7, 30),
                    'source' => 'simulation',
                ]);

                DailyCheckin::create([
                    'crew_profile_id' => $profile->id,
                    'mission_date' => $date->toDateString(),
                    'sleep_hours' => round($sleep, 1),
                    'sleep_quality' => $quality,
                    'fatigue_rating' => $fatigue,
                    'exercise_minutes' => $exercise,
                    'symptom_notes' => $notes,
                    'source' => 'simulation',
                    'submitted_at' => $date->copy()->setTime(8, 0),
                ]);
            }

            app(AlertEvaluationService::class)->evaluateCrew($profile);
        }
    }
}
