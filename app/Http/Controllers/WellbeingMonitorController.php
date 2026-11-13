<?php

namespace App\Http\Controllers;

use App\Models\Hazard;
use App\Models\WellbeingCheckin;
use Illuminate\View\View;

class WellbeingMonitorController extends Controller
{
    /**
     * Show wellbeing monitoring page for authenticated crew.
     */
    public function index(): View
    {
        $user = auth()->user();

        if (!$user->isAstronaut()) {
            abort(403, 'Unauthorized');
        }

        $crewProfile = $user->crewProfile;
        $mission = $crewProfile?->mission;
        $hazard = Hazard::where('slug', 'isolation')->first();

        // Fetch latest wellbeing checkins (last 30 days or 30 records)
        $checkins = WellbeingCheckin::where('crew_profile_id', $crewProfile->id)
            ->orderBy('recorded_at', 'desc')
            ->limit(30)
            ->get()
            ->reverse()
            ->values();

        // Get latest checkin
        $latestCheckin = WellbeingCheckin::where('crew_profile_id', $crewProfile->id)
            ->orderBy('recorded_at', 'desc')
            ->first();

        // Prepare chart data
        $chartData = [
            'labels' => $checkins->map(fn($c) => $c->recorded_at?->format('M d')),
            'datasets' => [
                [
                    'label' => 'Sleep Quality (1-5)',
                    'data' => $checkins->pluck('sleep_quality'),
                    'borderColor' => '#0F766E',
                    'backgroundColor' => 'rgba(15, 118, 110, 0.1)',
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Mood (1-5)',
                    'data' => $checkins->pluck('mood'),
                    'borderColor' => '#7C3AED',
                    'backgroundColor' => 'rgba(124, 58, 237, 0.1)',
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Stress (1-5)',
                    'data' => $checkins->pluck('stress'),
                    'borderColor' => '#B45309',
                    'backgroundColor' => 'rgba(180, 83, 9, 0.1)',
                    'tension' => 0.3,
                ],
            ],
        ];

        return view('monitors.wellbeing', [
            'crewProfile' => $crewProfile,
            'mission' => $mission,
            'hazard' => $hazard,
            'checkins' => $checkins,
            'latestCheckin' => $latestCheckin,
            'chartData' => $chartData,
        ]);
    }
}
