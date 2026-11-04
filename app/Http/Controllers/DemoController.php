<?php

namespace App\Http\Controllers;

use App\Models\CrewProfile;
use App\Models\DailyCheckin;
use App\Models\HealthReading;
use App\Models\MetricDefinition;
use App\Models\Mission;
use App\Models\MissionEvent;
use App\Services\AlertEvaluationService;
use App\Services\DemoMode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use LogicException;

class DemoController extends Controller
{
    public function index()
    {
        abort_unless(DemoMode::enabled(), 404);

        return view('admin.demo', ['mission' => Mission::query()->latest('starts_on')->first()]);
    }

    public function reset()
    {
        try {
            DemoMode::assertResetIsSafe();
        } catch (LogicException) {
            abort(403);
        }

        Artisan::call('orbitcare:demo-reset');

        return redirect()->route('admin.demo')->with('status', 'The fictional demo data has been reset.');
    }

    public function scenario(Request $request, AlertEvaluationService $alerts)
    {
        abort_unless(DemoMode::enabled(), 404);
        $data = $request->validate(['scenario' => ['required', 'in:stable,sleep_fatigue,heart_rate_shift,stale,exercise_gap,radiation_event,earth_blackout']]);
        $mission = Mission::query()->latest('starts_on')->firstOrFail();
        $crew = CrewProfile::query()->where('crew_code', $data['scenario'] === 'stale' ? 'OC-03' : 'OC-01')->firstOrFail();

        if ($data['scenario'] === 'sleep_fatigue') {
            $crew = CrewProfile::query()->where('crew_code', 'OC-02')->firstOrFail();
            foreach ([0, 1, 2] as $daysAgo) {
                DailyCheckin::updateOrCreate(['crew_profile_id' => $crew->id, 'mission_date' => today()->subDays($daysAgo)->toDateString()], [
                    'sleep_hours' => 4.8, 'sleep_quality' => 2, 'fatigue_rating' => 5, 'exercise_minutes' => 20,
                    'source' => 'simulation', 'submitted_at' => now()->subDays($daysAgo),
                ]);
            }
            $alerts->evaluateCrew($crew);
        }
        if ($data['scenario'] === 'heart_rate_shift') {
            $metric = MetricDefinition::query()->where('code', 'resting_heart_rate')->firstOrFail();
            foreach ([0 => 77, 1 => 76, 2 => 78] as $daysAgo => $value) {
                HealthReading::create(['crew_profile_id' => $crew->id, 'metric_definition_id' => $metric->id, 'value' => $value, 'observed_at' => now()->subDays($daysAgo), 'source' => 'simulation']);
            }
            $alerts->evaluateCrew($crew);
        }
        if ($data['scenario'] === 'exercise_gap') {
            DailyCheckin::updateOrCreate(['crew_profile_id' => $crew->id, 'mission_date' => today()->toDateString()], ['sleep_hours' => 7.2, 'sleep_quality' => 4, 'fatigue_rating' => 2, 'exercise_minutes' => 5, 'planned_aerobic_minutes' => 45, 'completed_aerobic_minutes' => 5, 'source' => 'simulation', 'submitted_at' => now()]);
        }
        if ($data['scenario'] === 'radiation_event') {
            MissionEvent::create(['mission_id' => $mission->id, 'name' => 'Simulated radiation event', 'occurred_at' => now(), 'notes' => 'Fictional demonstration event only; no radiation rule is implemented.']);
        }
        if ($data['scenario'] === 'earth_blackout') {
            $mission->update(['earth_link_status' => 'offline', 'earth_link_delay_minutes' => null]);
        }

        return response()->json(['message' => 'Scenario applied: '.str_replace('_', ' ', $data['scenario']), 'crew_url' => route('staff.crew.show', $crew->id)]);
    }

    public function missionDay(Request $request)
    {
        abort_unless(DemoMode::enabled(), 404);
        $data = $request->validate(['day' => ['required', 'integer', 'min:1', 'max:180']]);
        $mission = Mission::query()->latest('starts_on')->firstOrFail();
        $mission->update(['starts_on' => today()->subDays($data['day'] - 1)]);

        return back()->with('status', 'Mission clock set to day '.$data['day'].'.');
    }
}
