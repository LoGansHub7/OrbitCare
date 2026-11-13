<?php

namespace Database\Seeders;

use App\Models\Hazard;
use App\Models\Reference;
use Illuminate\Database\Seeder;

class ReferenceSeeder extends Seeder
{
    public function run(): void
    {
        // Radiation references
        $radiation = Hazard::where('slug', 'radiation')->first();
        if ($radiation) {
            $radiationRefs = [
                [
                    'title' => 'NASA Human Research Program - Space Radiation',
                    'publisher' => 'NASA',
                    'url' => 'https://www.nasa.gov/hrp',
                    'summary' => 'Comprehensive resource on NASA\'s research programs addressing hazards of human spaceflight, including space radiation effects.',
                    'published_at' => now()->subYears(2),
                ],
                [
                    'title' => 'Monitoring radiation for astronaut health',
                    'publisher' => 'CSA',
                    'url' => 'https://www.asc-csa.gc.ca/eng/news/articles/2026/2026-02-26-monitoring-radiation-astronaut-health.asp',
                    'summary' => 'CSA article on continuous, autonomous measurement of radiation exposure using CANS and MOSFET instruments.',
                    'published_at' => now()->subMonths(9),
                ],
                [
                    'title' => 'MOSFET Radiation Detector - Canadian Space Agency',
                    'publisher' => 'CSA',
                    'url' => 'https://www.asc-csa.gc.ca/eng/astronauts/space-medicine/mosfet.asp',
                    'summary' => 'Technical details on MOSFET technology for in-situ radiation measurement aboard ISS.',
                    'published_at' => now()->subYears(1),
                ],
            ];

            foreach ($radiationRefs as $ref) {
                Reference::firstOrCreate(
                    ['title' => $ref['title']],
                    array_merge($ref, ['hazard_id' => $radiation->id])
                );
            }
        }

        // Isolation references
        $isolation = Hazard::where('slug', 'isolation')->first();
        if ($isolation) {
            $isolationRefs = [
                [
                    'title' => 'HRP: Behavioral Health and Performance',
                    'publisher' => 'NASA',
                    'url' => 'https://www.nasa.gov/hrp',
                    'summary' => 'NASA\'s research on behavioral health, group dynamics, and psychological aspects of long-duration missions.',
                    'published_at' => now()->subYears(2),
                ],
                [
                    'title' => 'NASA Analog Missions - Understanding Extreme Environments',
                    'publisher' => 'NASA',
                    'url' => 'https://www.nasa.gov/analog-missions/',
                    'summary' => 'Analog missions that simulate isolation and confinement to prepare crews for space missions.',
                    'published_at' => now()->subYear(),
                ],
            ];

            foreach ($isolationRefs as $ref) {
                Reference::firstOrCreate(
                    ['title' => $ref['title']],
                    array_merge($ref, ['hazard_id' => $isolation->id])
                );
            }
        }

        // Distance from Earth references
        $distance = Hazard::where('slug', 'distance')->first();
        if ($distance) {
            $distanceRefs = [
                [
                    'title' => 'NASA Deep Space Missions and Communication',
                    'publisher' => 'NASA',
                    'url' => 'https://www.nasa.gov/international-space-station/',
                    'summary' => 'NASA\'s approach to mission planning, communication delays, and crew autonomy for distant missions.',
                    'published_at' => now()->subYears(2),
                ],
            ];

            foreach ($distanceRefs as $ref) {
                Reference::firstOrCreate(
                    ['title' => $ref['title']],
                    array_merge($ref, ['hazard_id' => $distance->id])
                );
            }
        }

        // Gravity references
        $gravity = Hazard::where('slug', 'gravity')->first();
        if ($gravity) {
            $gravityRefs = [
                [
                    'title' => 'HRP: Risks of Altered Gravity',
                    'publisher' => 'NASA',
                    'url' => 'https://www.nasa.gov/hrp/risks/',
                    'summary' => 'Comprehensive analysis of physiological changes in microgravity and reduced-gravity environments.',
                    'published_at' => now()->subYears(2),
                ],
                [
                    'title' => 'EVARM - ESA Experiment on Muscle and Bone',
                    'publisher' => 'CSA',
                    'url' => 'https://www.asc-csa.gc.ca/eng/iss/evarm.asp',
                    'summary' => 'European and Canadian research on bone and muscle adaptation to microgravity.',
                    'published_at' => now()->subYear(),
                ],
            ];

            foreach ($gravityRefs as $ref) {
                Reference::firstOrCreate(
                    ['title' => $ref['title']],
                    array_merge($ref, ['hazard_id' => $gravity->id])
                );
            }
        }

        // Environment references
        $environment = Hazard::where('slug', 'environment')->first();
        if ($environment) {
            $environmentRefs = [
                [
                    'title' => 'NASA ISS Life Support Systems',
                    'publisher' => 'NASA',
                    'url' => 'https://www.nasa.gov/international-space-station/',
                    'summary' => 'Information on closed-loop life support, air quality monitoring, and environmental control aboard the ISS.',
                    'published_at' => now()->subYears(2),
                ],
                [
                    'title' => 'Radi-N2: Radiation Dosimetry',
                    'publisher' => 'CSA',
                    'url' => 'https://www.asc-csa.gc.ca/eng/sciences/radi-n2.asp',
                    'summary' => 'Canadian radiation monitoring technology for ISS environmental and personal dosimetry.',
                    'published_at' => now()->subYear(),
                ],
            ];

            foreach ($environmentRefs as $ref) {
                Reference::firstOrCreate(
                    ['title' => $ref['title']],
                    array_merge($ref, ['hazard_id' => $environment->id])
                );
            }
        }
    }
}
