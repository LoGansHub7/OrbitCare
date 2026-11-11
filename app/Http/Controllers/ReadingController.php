<?php

namespace App\Http\Controllers;

use App\Models\AuditEvent;
use App\Models\HealthReading;
use App\Models\MetricDefinition;
use App\Services\AlertEvaluationService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReadingController extends Controller
{
    public function index(Request $request)
    {
        $crew = $request->user()->crewProfile()->firstOrFail();
        $metricCode = $request->query('metric', 'resting_heart_rate');
        $metric = MetricDefinition::where('code', $metricCode)->firstOrFail();
        $metrics = MetricDefinition::whereNotIn('code', ['sleep_duration', 'sleep_quality', 'fatigue_rating', 'exercise_duration'])->orderBy('category')->orderBy('label')->get();

        $readings = $crew->readings()
            ->where('metric_definition_id', $metric->id)
            ->latest('observed_at')
            ->paginate(15);

        return view('readings.index', compact('metric', 'metrics', 'readings'));
    }

    public function store(Request $request)
    {
        $crew = $request->user()->crewProfile()->firstOrFail();
        $metric = MetricDefinition::where('code', $request->string('metric_code', 'resting_heart_rate')->toString())->firstOrFail();

        // Bounds are input plausibility checks (data quality), not medical thresholds.
        $data = $request->validate([
            'value' => ['required', 'numeric', 'between:'.$metric->min_value.','.$metric->max_value],
            'observed_at' => ['required', 'date', 'before_or_equal:now'],
        ]);

        $reading = HealthReading::create([
            'crew_profile_id' => $crew->id,
            'metric_definition_id' => $metric->id,
            'value' => $data['value'],
            'observed_at' => Carbon::parse($data['observed_at']),
            'source' => 'manual',
        ]);
        AuditEvent::record('reading.created', $reading);
        app(AlertEvaluationService::class)->evaluateCrew($crew);

        return redirect()->route('readings.index', ['metric' => $metric->code])->with('status', 'Reading saved.');
    }
}
