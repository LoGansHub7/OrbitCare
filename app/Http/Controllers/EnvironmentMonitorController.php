<?php

namespace App\Http\Controllers;

use App\Models\Hazard;
use App\Models\CabinReading;
use Illuminate\View\View;

class EnvironmentMonitorController extends Controller
{
    /**
     * Show environmental monitoring page.
     */
    public function index(): View
    {
        $user = auth()->user();

        if (!$user->isAstronaut()) {
            abort(403, 'Unauthorized');
        }

        $crewProfile = $user->crewProfile;
        $mission = $crewProfile?->mission;
        $hazard = Hazard::where('slug', 'environment')->first();

        // Fetch cabin readings (last 30 records, mission-level only)
        $readings = CabinReading::where('mission_id', $mission->id)
            ->orderBy('recorded_at', 'desc')
            ->limit(30)
            ->get()
            ->reverse()
            ->values();

        // Get latest reading
        $latestReading = CabinReading::where('mission_id', $mission->id)
            ->orderBy('recorded_at', 'desc')
            ->first();

        // Prepare chart data
        $chartData = [
            'labels' => $readings->map(fn($r) => $r->recorded_at?->format('M d H:i')),
            'datasets' => [
                [
                    'label' => 'Temperature (°C)',
                    'data' => $readings->pluck('temperature'),
                    'borderColor' => '#DC2626',
                    'backgroundColor' => 'rgba(220, 38, 38, 0.1)',
                    'tension' => 0.3,
                ],
                [
                    'label' => 'CO2 Level (ppm)',
                    'data' => $readings->pluck('co2_level'),
                    'borderColor' => '#B45309',
                    'backgroundColor' => 'rgba(180, 83, 9, 0.1)',
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Pressure (kPa)',
                    'data' => $readings->pluck('pressure'),
                    'borderColor' => '#0F766E',
                    'backgroundColor' => 'rgba(15, 118, 110, 0.1)',
                    'tension' => 0.3,
                ],
            ],
        ];

        return view('monitors.environment', [
            'crewProfile' => $crewProfile,
            'mission' => $mission,
            'hazard' => $hazard,
            'readings' => $readings,
            'latestReading' => $latestReading,
            'chartData' => $chartData,
        ]);
    }
}
