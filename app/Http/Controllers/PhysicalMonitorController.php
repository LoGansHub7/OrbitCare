<?php

namespace App\Http\Controllers;

use App\Models\Hazard;
use App\Models\PhysicalReading;
use Illuminate\View\View;

class PhysicalMonitorController extends Controller
{
    /**
     * Show physical health monitoring page.
     */
    public function index(): View
    {
        $user = auth()->user();

        if (!$user->isAstronaut()) {
            abort(403, 'Unauthorized');
        }

        $crewProfile = $user->crewProfile;
        $mission = $crewProfile?->mission;
        $hazard = Hazard::where('slug', 'gravity')->first();

        // Fetch physical readings (last 30 days or 30 records)
        $readings = PhysicalReading::where('crew_profile_id', $crewProfile->id)
            ->orderBy('recorded_at', 'desc')
            ->limit(30)
            ->get()
            ->reverse()
            ->values();

        // Get latest reading
        $latestReading = PhysicalReading::where('crew_profile_id', $crewProfile->id)
            ->orderBy('recorded_at', 'desc')
            ->first();

        // Prepare chart data
        $chartData = [
            'labels' => $readings->map(fn($r) => $r->recorded_at?->format('M d')),
            'datasets' => [
                [
                    'label' => 'Muscle Index',
                    'data' => $readings->pluck('muscle_index'),
                    'borderColor' => '#15803D',
                    'backgroundColor' => 'rgba(21, 128, 61, 0.1)',
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Bone Index',
                    'data' => $readings->pluck('bone_index'),
                    'borderColor' => '#0F766E',
                    'backgroundColor' => 'rgba(15, 118, 110, 0.1)',
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Balance Score (1-10)',
                    'data' => $readings->pluck('balance_score'),
                    'borderColor' => '#7C3AED',
                    'backgroundColor' => 'rgba(124, 58, 237, 0.1)',
                    'tension' => 0.3,
                ],
            ],
        ];

        return view('monitors.physical', [
            'crewProfile' => $crewProfile,
            'mission' => $mission,
            'hazard' => $hazard,
            'readings' => $readings,
            'latestReading' => $latestReading,
            'chartData' => $chartData,
        ]);
    }
}
