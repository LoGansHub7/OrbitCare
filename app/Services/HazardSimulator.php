<?php

namespace App\Services;

use App\Models\AutonomyEvent;
use App\Models\CabinReading;
use App\Models\CrewProfile;
use App\Models\PhysicalReading;
use App\Models\RadiationReading;
use App\Models\WellbeingCheckin;
use InvalidArgumentException;

/**
 * Generates fictional, deterministic hazard data (always is_simulated = true).
 * Each scenario perturbs one hazard stream; the others stay stable.
 * Cabin readings are mission-level, so applying a scenario replaces the mission's simulated cabin rows.
 */
class HazardSimulator
{
    public const SCENARIOS = [
        'stable' => 'Stable baseline (all hazards)',
        'radiation_event' => 'Radiation dose-rate rise',
        'isolation_decline' => 'Mood decline under isolation',
        'comms_delay' => 'Escalations under long comms delay',
        'deconditioning' => 'Muscle decline with low exercise',
        'cabin_co2' => 'Sustained high cabin CO2',
    ];

    private const DAYS = 10;

    public function apply(CrewProfile $crew, string $scenario): void
    {
        if (! array_key_exists($scenario, self::SCENARIOS)) {
            throw new InvalidArgumentException("Unknown hazard scenario: {$scenario}");
        }

        $this->clear($crew);
        $this->radiation($crew, $scenario);
        $this->wellbeing($crew, $scenario);
        $this->autonomy($crew, $scenario);
        $this->physical($crew, $scenario);
        $this->cabin($crew, $scenario);
    }

    public function clear(CrewProfile $crew): void
    {
        foreach ([RadiationReading::class, WellbeingCheckin::class, AutonomyEvent::class, PhysicalReading::class] as $model) {
            $model::where('crew_profile_id', $crew->id)->where('is_simulated', true)->delete();
        }
        CabinReading::where('mission_id', $crew->mission_id)->where('is_simulated', true)->delete();
    }

    private function at(int $daysAgo)
    {
        return now()->subDays($daysAgo)->subMinutes(5);
    }

    private function radiation(CrewProfile $crew, string $scenario): void
    {
        $cumulative = 20.0;
        for ($i = self::DAYS - 1; $i >= 0; $i--) {
            $rate = 0.50 + ($i % 3) * 0.01;
            if ($scenario === 'radiation_event' && $i <= 2) {
                $rate = 1.30;
            }
            $cumulative += $rate;
            RadiationReading::create([
                'crew_profile_id' => $crew->id, 'mission_id' => $crew->mission_id,
                'dose_rate' => $rate, 'cumulative_dose' => round($cumulative, 4),
                'is_simulated' => true, 'recorded_at' => $this->at($i),
            ]);
        }
    }

    private function wellbeing(CrewProfile $crew, string $scenario): void
    {
        $stableMoods = [4, 3, 4, 4, 3, 4, 4, 3, 4, 4];
        $declining = [5, 4, 3, 2];
        for ($i = self::DAYS - 1; $i >= 0; $i--) {
            $mood = $stableMoods[self::DAYS - 1 - $i];
            $sleep = 7.2;
            $stress = 2;
            if ($scenario === 'isolation_decline' && $i <= 3) {
                $mood = $declining[3 - $i];
                $sleep = 5.5;
                $stress = 4;
            }
            WellbeingCheckin::create([
                'crew_profile_id' => $crew->id, 'mission_id' => $crew->mission_id,
                'mood' => $mood, 'stress' => $stress, 'sleep_hours' => $sleep, 'sleep_quality' => $mood,
                'crew_interaction_minutes' => $scenario === 'isolation_decline' && $i <= 3 ? 20 : 90,
                'workload' => 3, 'is_simulated' => true, 'recorded_at' => $this->at($i),
            ]);
        }
    }

    private function autonomy(CrewProfile $crew, string $scenario): void
    {
        $delay = $scenario === 'comms_delay' ? 1200 : 840;
        // Stable: 1 of 4 delayed events escalated (25%). comms_delay: 3 of 4 (75%).
        $escalations = $scenario === 'comms_delay' ? [true, true, true, false] : [false, true, false, false];
        foreach ($escalations as $index => $escalated) {
            AutonomyEvent::create([
                'crew_profile_id' => $crew->id, 'mission_id' => $crew->mission_id,
                'event_type' => 'decision_point', 'comms_delay_seconds' => $delay,
                'handled_onboard' => ! $escalated, 'escalated_to_earth' => $escalated,
                'description' => 'Simulated onboard decision point.',
                'is_simulated' => true, 'recorded_at' => $this->at($index * 2),
            ]);
        }
    }

    private function physical(CrewProfile $crew, string $scenario): void
    {
        for ($i = self::DAYS - 1; $i >= 0; $i--) {
            $muscle = 50.0 - ($i % 2) * 0.1;
            $exercise = 75;
            if ($scenario === 'deconditioning' && $i <= 3) {
                $muscle = 50.0 - (3 - $i) * 0.7;
                $exercise = 20;
            }
            PhysicalReading::create([
                'crew_profile_id' => $crew->id, 'mission_id' => $crew->mission_id,
                'exercise_minutes' => $exercise, 'muscle_index' => $muscle, 'bone_index' => 98.0, 'balance_score' => 8,
                'is_simulated' => true, 'recorded_at' => $this->at($i),
            ]);
        }
    }

    private function cabin(CrewProfile $crew, string $scenario): void
    {
        for ($i = self::DAYS - 1; $i >= 0; $i--) {
            $co2 = 900 + ($i % 3) * 40;
            if ($scenario === 'cabin_co2' && $i <= 2) {
                $co2 = 3400;
            }
            CabinReading::create([
                'mission_id' => $crew->mission_id, 'temperature' => 22.0, 'pressure' => 101.3, 'co2_level' => $co2,
                'light_level' => 300, 'noise_db' => 52, 'is_simulated' => true, 'recorded_at' => $this->at($i),
            ]);
        }
    }
}
