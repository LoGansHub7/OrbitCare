<?php

namespace Database\Seeders;

use App\Models\CrewProfile;
use App\Services\AlertEvaluationService;
use App\Services\HazardSimulator;
use Illuminate\Database\Seeder;

/** Fictional hazard monitoring data for the demo crew. OC-03 is left empty to show "insufficient data". */
class HazardDemoSeeder extends Seeder
{
    public function run(): void
    {
        $simulator = app(HazardSimulator::class);
        $alerts = app(AlertEvaluationService::class);

        foreach (['OC-01' => 'stable', 'OC-02' => 'isolation_decline'] as $code => $scenario) {
            $crew = CrewProfile::where('crew_code', $code)->first();
            if (! $crew) {
                continue;
            }
            $simulator->apply($crew, $scenario);
            $alerts->evaluateCrew($crew);
        }
    }
}
