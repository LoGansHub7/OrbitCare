<?php

namespace App\Http\Controllers;

use App\Models\Hazard;
use App\Models\RadiationReading;
use Illuminate\View\View;

class RadiationMonitorController extends Controller
{
    /**
     * Show radiation monitoring page for authenticated crew.
     */
    public function index(): View
    {
        $user = auth()->user();

        if (!$user->isAstronaut()) {
            abort(403, 'Unauthorized');
        }

        $crewProfile = $user->crewProfile;
        $mission = $crewProfile?->mission;
        $hazard = Hazard::where('slug', 'radiation')->first();

        // Fetch latest radiation readings (last 30 days or 30 records)
        $readings = RadiationReading::where('crew_profile_id', $crewProfile->id)
            ->orderBy('recorded_at', 'desc')
            ->limit(30)
            ->get()
            ->reverse()
            ->values();

        // Get latest reading
        $latestReading = RadiationReading::where('crew_profile_id', $crewProfile->id)
            ->orderBy('recorded_at', 'desc')
            ->first();

        // Prepare chart data
        $chartData = [
            'labels' => $readings->map(fn($r) => $r->recorded_at?->format('M d')),
            'datasets' => [
                [
                    'label' => 'Dose Rate (mSv/day)',
                    'data' => $readings->pluck('dose_rate'),
                    'borderColor' => '#B91C1C',
                    'backgroundColor' => 'rgba(185, 28, 28, 0.1)',
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Cumulative Dose (mSv)',
                    'data' => $readings->pluck('cumulative_dose'),
                    'borderColor' => '#7C3AED',
                    'backgroundColor' => 'rgba(124, 58, 237, 0.1)',
                    'tension' => 0.3,
                ],
            ],
        ];

        return view('monitors.radiation', [
            'crewProfile' => $crewProfile,
            'mission' => $mission,
            'hazard' => $hazard,
            'readings' => $readings,
            'latestReading' => $latestReading,
            'chartData' => $chartData,
        ]);
    }
}
