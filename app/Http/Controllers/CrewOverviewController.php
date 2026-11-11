<?php

namespace App\Http\Controllers;

use App\Models\MetricDefinition;
use Illuminate\Http\Request;

class CrewOverviewController extends Controller
{
    public function index(Request $request)
    {
        if ($request->user()->isAdmin()) {
            return view('staff.home', ['isAdmin' => true, 'crewRows' => collect(), 'filter' => 'all']);
        }

        abort_unless($request->user()->isMedicalOfficer(), 403);

        $definitions = MetricDefinition::query()->whereIn('code', ['resting_heart_rate'])->get()->keyBy('code');
        $heartRateDefinition = $definitions->get('resting_heart_rate');
        $filter = $request->query('filter', 'all');
        $filter = in_array($filter, ['all', 'review', 'stale', 'no_data'], true) ? $filter : 'all';

        $crewRows = $request->user()->assignedCrewProfiles()
            ->with(['user', 'mission'])
            ->get()
            ->map(function ($crew) use ($heartRateDefinition): array {
                $latestCheckin = $crew->checkins()->latest('mission_date')->first();
                $latestReading = $heartRateDefinition
                    ? $crew->readings()
                        ->where('metric_definition_id', $heartRateDefinition->id)
                        ->latest('observed_at')
                        ->first()
                    : null;
                $activeAlerts = $crew->alerts()
                    ->where('status', '!=', 'closed')
                    ->where('severity', '!=', 'insufficient_data')
                    ->count();
                $hasCheckin = $latestCheckin !== null;
                $hasReading = $latestReading !== null;
                $isStale = ($latestCheckin && $latestCheckin->submitted_at->lt(now()->subHours(36)))
                    || ($latestReading && $heartRateDefinition && $latestReading->observed_at->lt(now()->subHours($heartRateDefinition->freshness_hours)));

                return [
                    'crew' => $crew,
                    'latest_checkin' => $latestCheckin,
                    'latest_reading' => $latestReading,
                    'active_alerts' => $activeAlerts,
                    'freshness' => ! $hasCheckin && ! $hasReading ? 'no_data' : ($isStale ? 'stale' : 'current'),
                ];
            });

        $crewRows = $crewRows->filter(fn (array $row): bool => match ($filter) {
            'review' => $row['active_alerts'] > 0,
            'stale' => $row['freshness'] === 'stale',
            'no_data' => $row['freshness'] === 'no_data',
            default => true,
        })->values();

        return view('staff.home', ['isAdmin' => false, 'crewRows' => $crewRows, 'filter' => $filter]);
    }

    public function show(Request $request, int $crewProfileId)
    {
        abort_unless($request->user()->isMedicalOfficer(), 403);

        $crew = $request->user()->assignedCrewProfiles()
            ->with(['user', 'mission'])
            ->whereKey($crewProfileId)
            ->firstOrFail();

        $heartRateDefinition = MetricDefinition::query()
            ->where('code', 'resting_heart_rate')
            ->firstOrFail();
        $checkins = $crew->checkins()->latest('mission_date')->take(7)->get();
        $readings = $crew->readings()
            ->where('metric_definition_id', $heartRateDefinition->id)
            ->latest('observed_at')
            ->take(7)
            ->get();
        $alerts = $crew->alerts()
            ->with(['rule', 'followups.actor'])
            ->orderByRaw("CASE WHEN status = 'closed' THEN 1 ELSE 0 END")
            ->orderByDesc('last_evaluated_at')
            ->get();

        return view('staff.crew-show', compact('crew', 'heartRateDefinition', 'checkins', 'readings', 'alerts'));
    }
}
