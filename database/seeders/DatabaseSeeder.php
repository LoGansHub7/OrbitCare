<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Phase A: NASA Five Hazards Framework
            HazardSeeder::class,
            RiskSeeder::class,
            ReferenceSeeder::class,
            // Existing demo data
            MetricDefinitionSeeder::class,
            AlertRuleSeeder::class,
            DemoSeeder::class,
            DemoCareAssignmentSeeder::class,
            HazardDemoSeeder::class,
        ]);
    }
}
