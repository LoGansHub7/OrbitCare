<?php

namespace App\Http\Controllers;

use App\Models\Hazard;
use App\Models\AutonomyEvent;
use Illuminate\View\View;

class AutonomyMonitorController extends Controller
{
    /**
     * Show autonomy & comms delay monitoring page.
     */
    public function index(): View
    {
        $user = auth()->user();

        if (!$user->isAstronaut()) {
            abort(403, 'Unauthorized');
        }

        $crewProfile = $user->crewProfile;
        $mission = $crewProfile?->mission;
        $hazard = Hazard::where('slug', 'distance')->first();

        // Fetch autonomy events (last 30 records)
        $events = AutonomyEvent::where('crew_profile_id', $crewProfile->id)
            ->orderBy('recorded_at', 'desc')
            ->limit(30)
            ->get()
            ->reverse()
            ->values();

        // Get latest event
        $latestEvent = AutonomyEvent::where('crew_profile_id', $crewProfile->id)
            ->orderBy('recorded_at', 'desc')
            ->first();

        // Calculate autonomy metrics
        $handledOnboard = $events->filter(fn($e) => $e->handled_onboard)->count();
        $escalatedToEarth = $events->filter(fn($e) => $e->escalated_to_earth)->count();

        // Prepare chart data (comms delay timeline)
        $chartData = [
            'labels' => $events->map(fn($e) => $e->recorded_at?->format('M d H:i')),
            'datasets' => [
                [
                    'label' => 'Comms Delay (seconds)',
                    'data' => $events->pluck('comms_delay_seconds'),
                    'borderColor' => '#7C3AED',
                    'backgroundColor' => 'rgba(124, 58, 237, 0.1)',
                    'tension' => 0.3,
                ],
            ],
        ];

        return view('monitors.autonomy', [
            'crewProfile' => $crewProfile,
            'mission' => $mission,
            'hazard' => $hazard,
            'events' => $events,
            'latestEvent' => $latestEvent,
            'handledOnboard' => $handledOnboard,
            'escalatedToEarth' => $escalatedToEarth,
            'chartData' => $chartData,
        ]);
    }
}
