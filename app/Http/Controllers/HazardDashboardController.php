<?php

namespace App\Http\Controllers;

use App\Models\Hazard;
use App\Models\Mission;
use App\Services\AlertEvaluationService;
use Illuminate\View\View;

class HazardDashboardController extends Controller
{
    public function __construct(private readonly AlertEvaluationService $alerts)
    {
    }

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

        $statuses = $crewProfile ? $this->alerts->hazardStatuses($crewProfile) : [];

        // What-if preview: ?profile= changes the weighting display only, never stored data or thresholds.
        $missionProfiles = ['LEO', 'Moon', 'Mars'];
        $requested = request()->query('profile');
        $previewProfile = in_array($requested, $missionProfiles, true) ? $requested : null;
        $weights = $previewProfile ? Mission::weightsForProfile($previewProfile) : ($mission?->hazardWeights() ?? []);

        $hazardStatus = [];
        foreach ($hazards as $hazard) {
            $hazardStatus[$hazard->slug] = [
                'hazard' => $hazard,
                'status' => $statuses[$hazard->id]['status'] ?? 'insufficient_data',
                'alert_count' => $statuses[$hazard->id]['alert_count'] ?? 0,
                'weight' => $weights[$hazard->slug] ?? 1.0,
            ];
        }

        return view('hazards.index', [
            'crewProfile' => $crewProfile,
            'mission' => $mission,
            'hazards' => $hazards,
            'hazardStatus' => $hazardStatus,
            'missionProfiles' => $missionProfiles,
            'previewProfile' => $previewProfile,
        ]);
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
