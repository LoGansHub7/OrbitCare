<?php

namespace App\Console\Commands;

use App\Models\CrewProfile;
use App\Services\AlertEvaluationService;
use App\Services\DemoMode;
use App\Services\HazardSimulator;
use Illuminate\Console\Command;

class SimulateHazards extends Command
{
    protected $signature = 'orbitcare:simulate {scenario : stable|radiation_event|isolation_decline|comms_delay|deconditioning|cabin_co2} {--crew=OC-01 : Crew code}';

    protected $description = 'Insert fictional hazard monitoring data for a crew member and evaluate alerts (demo mode only).';

    public function handle(HazardSimulator $simulator, AlertEvaluationService $alerts): int
    {
        if (! DemoMode::enabled()) {
            $this->error('Simulation is disabled. Enable ORBITCARE_DEMO_MODE (never in production).');

            return self::FAILURE;
        }

        $scenario = (string) $this->argument('scenario');
        if (! array_key_exists($scenario, HazardSimulator::SCENARIOS)) {
            $this->error('Unknown scenario. Choose one of: '.implode(', ', array_keys(HazardSimulator::SCENARIOS)));

            return self::FAILURE;
        }

        $crew = CrewProfile::where('crew_code', $this->option('crew'))->first();
        if (! $crew) {
            $this->error('Crew code not found.');

            return self::FAILURE;
        }

        $simulator->apply($crew, $scenario);
        $alerts->evaluateCrew($crew);
        $this->info("Applied '{$scenario}' to {$crew->crew_code} (simulated data) and evaluated alerts.");

        return self::SUCCESS;
    }
}
