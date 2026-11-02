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
        $metric = MetricDefinition::where('code', 'resting_heart_rate')->firstOrFail();

        $readings = $crew->readings()
            ->where('metric_definition_id', $metric->id)
            ->latest('observed_at')
            ->paginate(15);

        return view('readings.index', compact('metric', 'readings'));
    }

    public function store(Request $request)
    {
        $crew = $request->user()->crewProfile()->firstOrFail();
        $metric = MetricDefinition::where('code', 'resting_heart_rate')->firstOrFail();

        // Bounds are input plausibility checks (data quality), not medical thresholds.
        $data = $request->validate([
            'value' => ['required', 'numeric', 'between:'.$metric->min_value.','.$metric->max_value],
            'observed_at' => ['required', 'date', 'before_or_equal:now'],
            'source' => ['sometimes', 'string', 'in:manual,csv_import,simulation'],
        ]);

        $reading = HealthReading::create([
            'crew_profile_id' => $crew->id,
            'metric_definition_id' => $metric->id,
            'value' => $data['value'],
            'observed_at' => Carbon::parse($data['observed_at']),
            'source' => $data['source'] ?? 'manual',
        ]);
        AuditEvent::record('reading.created', $reading);
        app(AlertEvaluationService::class)->evaluateCrew($crew);

        return redirect()->route('readings.index')->with('status', 'Reading saved.');
    }
}
