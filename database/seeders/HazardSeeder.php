<?php

namespace Database\Seeders;

use App\Models\Hazard;
use Illuminate\Database\Seeder;

class HazardSeeder extends Seeder
{
    public function run(): void
    {
        $hazards = [
            [
                'slug' => 'radiation',
                'name' => 'Space Radiation',
                'description' => 'Exposure to high-energy particles and cosmic rays in space, increasing cancer risk and potential cognitive effects.',
                'icon' => 'bi-radiation',
                'color' => '#B91C1C',
                'sort_order' => 1,
            ],
            [
                'slug' => 'isolation',
                'name' => 'Isolation & Confinement',
                'description' => 'Prolonged separation from Earth and living in confined environments, affecting psychological well-being and crew dynamics.',
                'icon' => 'bi-person-lock',
                'color' => '#B45309',
                'sort_order' => 2,
            ],
            [
                'slug' => 'distance',
                'name' => 'Distance from Earth',
                'description' => 'Extended communication delays and inability to rapidly return home, requiring crew autonomy and self-reliance.',
                'icon' => 'bi-globe',
                'color' => '#7C3AED',
                'sort_order' => 3,
            ],
            [
                'slug' => 'gravity',
                'name' => 'Gravity Alterations',
                'description' => 'Microgravity or reduced gravity causing bone loss, muscle atrophy, fluid shifts, and cardiovascular deconditioning.',
                'icon' => 'bi-arrow-down-circle',
                'color' => '#0F766E',
                'sort_order' => 4,
            ],
            [
                'slug' => 'environment',
                'name' => 'Hostile Environments',
                'description' => 'Closed-loop life support, contamination risks, and extreme environmental conditions affecting crew health and safety.',
                'icon' => 'bi-exclamation-triangle',
                'color' => '#15803D',
                'sort_order' => 5,
            ],
        ];

        foreach ($hazards as $hazard) {
            Hazard::firstOrCreate(['slug' => $hazard['slug']], $hazard);
        }
    }
}
