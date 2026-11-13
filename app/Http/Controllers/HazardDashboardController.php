<?php

namespace App\Http\Controllers;

use App\Models\Hazard;
use App\Models\Mission;
use App\Models\CrewProfile;
use Illuminate\View\View;

class HazardDashboardController extends Controller
{
    /**
     * Show hazard-centered dashboard for authenticated crew.
     */
    public function index(): View
    {
        $user = auth()->user();

        if (!$user->isAstronaut()) {
            return view('hazards.unauthorized');
        }

        $crewProfile = $user->crewProfile;
        $mission = $crewProfile?->mission;

        $hazards = Hazard::with('risks', 'references')
            ->orderBy('sort_order')
            ->get();

        // Calculate hazard status for this crew member
        $hazardStatus = [];
        foreach ($hazards as $hazard) {
            $hazardStatus[$hazard->slug] = [
                'hazard' => $hazard,
                'status' => $this->calculateHazardStatus($hazard, $crewProfile),
                'alert_count' => $this->getAlertCount($hazard, $crewProfile),
                'weight' => $mission?->hazardWeights()[$hazard->slug] ?? 1.0,
            ];
        }

        // Prepare mission profile selector
        $missionProfiles = ['LEO', 'Moon', 'Mars'];

        return view('hazards.index', [
            'crewProfile' => $crewProfile,
            'mission' => $mission,
            'hazards' => $hazards,
            'hazardStatus' => $hazardStatus,
            'missionProfiles' => $missionProfiles,
        ]);
    }

    /**
     * Calculate status for a specific hazard.
     * Returns: 'no_flag', 'review', 'priority_review', 'insufficient_data'
     */
    protected function calculateHazardStatus(Hazard $hazard, ?CrewProfile $crewProfile): string
    {
        if (!$crewProfile) {
            return 'insufficient_data';
        }

        // TODO: Integrate with AlertRule evaluation
        // For now, return 'no_flag' as placeholder
        return 'no_flag';
    }

    /**
     * Count active alerts for a hazard.
     */
    protected function getAlertCount(Hazard $hazard, ?CrewProfile $crewProfile): int
    {
        if (!$crewProfile) {
            return 0;
        }

        // TODO: Query Alert model by hazard_id and crew_profile_id
        return 0;
    }

    /**
     * Get interaction data for two hazards (for Chart.js overlay).
     */
    public function getHazardInteractionData($hazard1, $hazard2)
    {
        $user = auth()->user();

        if (!$user->isAstronaut()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // TODO: Fetch time-series data for both hazards
        // Return JSON suitable for Chart.js overlay
        return response()->json([
            'labels' => [],
            'datasets' => [],
        ]);
    }
}
