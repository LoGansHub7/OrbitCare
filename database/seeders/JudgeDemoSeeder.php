<?php

namespace Database\Seeders;

use App\Models\CrewProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Fictional, presentation-only records for a public judging environment.
 * It leaves only Tendai Moyo and the assigned medical officer active.
 */
class JudgeDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            HazardSeeder::class,
            RiskSeeder::class,
            ReferenceSeeder::class,
            MetricDefinitionSeeder::class,
            AlertRuleSeeder::class,
            DemoSeeder::class,
            DemoCareAssignmentSeeder::class,
            HazardDemoSeeder::class,
            BodyRegionSeeder::class,
        ]);

        $tendai = CrewProfile::query()->where('crew_code', 'OC-02')->firstOrFail();
        $medicalOfficer = User::query()->where('email', 'medic@orbitcare.test')->firstOrFail();

        // The judge account can review Tendai's fictional evidence only.
        $medicalOfficer->assignedCrewProfiles()->sync([$tendai->id]);

        // Retain the underlying synthetic scenarios, but prevent the unused
        // demonstration accounts (including the seeded administrator) signing in.
        User::query()
            ->whereIn('email', ['astro1@orbitcare.test', 'astro3@orbitcare.test', 'admin@orbitcare.test'])
            ->update(['is_active' => false]);
    }
}
