<?php

namespace Database\Seeders;

use App\Models\Hazard;
use App\Models\Risk;
use Illuminate\Database\Seeder;

class RiskSeeder extends Seeder
{
    public function run(): void
    {
        // Radiation risks
        $radiation = Hazard::where('slug', 'radiation')->first();
        if ($radiation) {
            $radiationRisks = [
                ['name' => 'Cancer Risk', 'summary' => 'Increased risk of cancer from galactic cosmic radiation and solar particle events.', 'source_url' => 'https://www.nasa.gov/hrp/risks/'],
                ['name' => 'Cataract Risk', 'summary' => 'Elevated risk of cataract development from space radiation exposure.', 'source_url' => 'https://www.nasa.gov/hrp/risks/'],
                ['name' => 'Central Nervous System Effects', 'summary' => 'Potential cognitive and neurological effects from heavy ion radiation exposure.', 'source_url' => 'https://www.nasa.gov/hrp/risks/'],
                ['name' => 'Cardiovascular Radiation Risk', 'summary' => 'Increased cardiovascular disease risk from radiation exposure.', 'source_url' => 'https://www.nasa.gov/hrp/risks/'],
            ];

            foreach ($radiationRisks as $risk) {
                Risk::firstOrCreate(
                    ['name' => $risk['name'], 'hazard_id' => $radiation->id],
                    $risk
                );
            }
        }

        // Isolation risks
        $isolation = Hazard::where('slug', 'isolation')->first();
        if ($isolation) {
            $isolationRisks = [
                ['name' => 'Behavioral Health Deconditioning', 'summary' => 'Potential for depression, anxiety, and mood changes during prolonged isolation.', 'source_url' => 'https://www.nasa.gov/hrp/risks/'],
                ['name' => 'Crew Disharmony', 'summary' => 'Interpersonal conflicts and communication breakdown within small, confined crews.', 'source_url' => 'https://www.nasa.gov/hrp/risks/'],
                ['name' => 'Sleep Quality Degradation', 'summary' => 'Disrupted sleep patterns from circadian rhythm desynchronization in space.', 'source_url' => 'https://www.nasa.gov/hrp/risks/'],
                ['name' => 'Fatigue and Cognitive Impairment', 'summary' => 'Cumulative fatigue effects on decision-making and performance.', 'source_url' => 'https://www.nasa.gov/hrp/risks/'],
                ['name' => 'Reduced Social Support', 'summary' => 'Limited access to psychological support systems during long missions.', 'source_url' => 'https://www.nasa.gov/hrp/risks/'],
            ];

            foreach ($isolationRisks as $risk) {
                Risk::firstOrCreate(
                    ['name' => $risk['name'], 'hazard_id' => $isolation->id],
                    $risk
                );
            }
        }

        // Distance from Earth risks
        $distance = Hazard::where('slug', 'distance')->first();
        if ($distance) {
            $distanceRisks = [
                ['name' => 'Limited Rescue Options', 'summary' => 'Inability to rapidly return to Earth or receive emergency assistance.', 'source_url' => 'https://www.nasa.gov/hrp/risks/'],
                ['name' => 'Communication Delays', 'summary' => 'Extended signal delay affecting real-time medical guidance and decision-making.', 'source_url' => 'https://www.nasa.gov/hrp/risks/'],
                ['name' => 'Reduced Resupply Capability', 'summary' => 'Limited ability to resupply medications and emergency medical equipment.', 'source_url' => 'https://www.nasa.gov/hrp/risks/'],
                ['name' => 'Crew Autonomy Burden', 'summary' => 'Increased responsibility for crew to diagnose and manage health issues independently.', 'source_url' => 'https://www.nasa.gov/hrp/risks/'],
            ];

            foreach ($distanceRisks as $risk) {
                Risk::firstOrCreate(
                    ['name' => $risk['name'], 'hazard_id' => $distance->id],
                    $risk
                );
            }
        }

        // Gravity alteration risks
        $gravity = Hazard::where('slug', 'gravity')->first();
        if ($gravity) {
            $gravityRisks = [
                ['name' => 'Bone Loss', 'summary' => 'Accelerated bone mineral density loss (up to 1% per month) in microgravity.', 'source_url' => 'https://www.nasa.gov/hrp/risks/'],
                ['name' => 'Muscle Atrophy', 'summary' => 'Progressive loss of muscle mass and strength in microgravity environment.', 'source_url' => 'https://www.nasa.gov/hrp/risks/'],
                ['name' => 'Cardiovascular Deconditioning', 'summary' => 'Reduced cardiac output and orthostatic intolerance from microgravity exposure.', 'source_url' => 'https://www.nasa.gov/hrp/risks/'],
                ['name' => 'Fluid Shift Effects', 'summary' => 'Headward fluid shift causing increased intracranial pressure and facial edema.', 'source_url' => 'https://www.nasa.gov/hrp/risks/'],
                ['name' => 'Vestibular Dysfunction', 'summary' => 'Balance and spatial orientation disorders from altered gravity and fluid shifts.', 'source_url' => 'https://www.nasa.gov/hrp/risks/'],
                ['name' => 'Vision Impairment', 'summary' => 'Papilledema and vision changes potentially caused by elevated intracranial pressure.', 'source_url' => 'https://www.nasa.gov/hrp/risks/'],
            ];

            foreach ($gravityRisks as $risk) {
                Risk::firstOrCreate(
                    ['name' => $risk['name'], 'hazard_id' => $gravity->id],
                    $risk
                );
            }
        }

        // Environment risks
        $environment = Hazard::where('slug', 'environment')->first();
        if ($environment) {
            $environmentRisks = [
                ['name' => 'Life Support Failure', 'summary' => 'Potential failure of closed-loop life support systems affecting breathable air and water.', 'source_url' => 'https://www.nasa.gov/hrp/risks/'],
                ['name' => 'Contamination Risk', 'summary' => 'Microbial or chemical contamination of air, water, or food supplies.', 'source_url' => 'https://www.nasa.gov/hrp/risks/'],
                ['name' => 'Inadequate Nutrition', 'summary' => 'Limited food variety and nutrient availability affecting crew health.', 'source_url' => 'https://www.nasa.gov/hrp/risks/'],
                ['name' => 'Thermal Control Issues', 'summary' => 'Inability to adequately regulate cabin temperature affecting crew comfort and safety.', 'source_url' => 'https://www.nasa.gov/hrp/risks/'],
                ['name' => 'Trace Contaminant Exposure', 'summary' => 'Exposure to volatile organic compounds and other contaminants in closed environments.', 'source_url' => 'https://www.nasa.gov/hrp/risks/'],
            ];

            foreach ($environmentRisks as $risk) {
                Risk::firstOrCreate(
                    ['name' => $risk['name'], 'hazard_id' => $environment->id],
                    $risk
                );
            }
        }
    }
}
