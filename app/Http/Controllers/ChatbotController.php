<?php

namespace App\Http\Controllers;

use App\Models\MetricDefinition;
use App\Services\AlertEvaluationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ChatbotController extends Controller
{
    public function session(Request $request): JsonResponse
    {
        return response()->json([
            'astronaut_id' => $request->user()->id,
            'csrf_token' => csrf_token(),
        ])->header('Cache-Control', 'no-store');
    }

    public function snapshot(Request $request): JsonResponse
    {
        $crew = $request->user()->crewProfile()->firstOrFail();
        $checkin = $crew->checkins()->latest('mission_date')->first();
        $checkinFreshness = MetricDefinition::query()
            ->whereIn('code', ['sleep_duration', 'fatigue_rating', 'exercise_duration'])
            ->pluck('freshness_hours', 'code');
        $readings = $crew->readings()->with('metric')->orderByDesc('observed_at')->orderByDesc('id')->get()
            ->unique('metric_definition_id')->map(fn ($reading) => [
                'code' => $reading->metric->code,
                'label' => $reading->metric->label,
                'unit' => $reading->metric->unit,
                'value' => (float) $reading->value,
                'observed_at' => $reading->observed_at->toIso8601String(),
                'source' => $reading->source,
                'freshness_hours' => (int) $reading->metric->freshness_hours,
            ])->values();

        return response()->json([
            'astronaut_id' => $request->user()->id,
            'captured_at' => now()->toIso8601String(),
            'rule_states' => app(AlertEvaluationService::class)->currentStates($crew),
            'readings' => $readings,
            'checkin' => $checkin ? [
                'mission_date' => $checkin->mission_date->toDateString(),
                'submitted_at' => $checkin->submitted_at->toIso8601String(),
                'sleep_hours' => (float) $checkin->sleep_hours,
                'fatigue_rating' => $checkin->fatigue_rating,
                'exercise_minutes' => $checkin->exercise_minutes,
                'source' => $checkin->source,
                'freshness_hours' => $checkinFreshness,
            ] : null,
        ])->header('Cache-Control', 'no-store');
    }

    public function sync(Request $request): JsonResponse
    {
        $data = $request->validate([
            'astronaut_id' => ['required', 'integer', Rule::in([$request->user()->id])],
            'messages' => ['required', 'array', 'min:1', 'max:50'],
            'messages.*.client_id' => ['required', 'uuid', 'distinct'],
            'messages.*.role' => ['required', Rule::in(['user', 'assistant'])],
            'messages.*.message' => ['required', 'string', 'max:4000'],
            'messages.*.intent' => ['nullable', 'string', 'max:80'],
            'messages.*.created_at' => ['required', 'date'],
        ]);

        DB::transaction(function () use ($request, $data): void {
            foreach ($data['messages'] as $message) {
                $request->user()->chatbotMessages()->firstOrCreate(
                    ['client_id' => $message['client_id']],
                    [
                        'role' => $message['role'],
                        'message' => $message['message'],
                        'intent' => $message['intent'] ?? null,
                        'created_at' => $message['created_at'],
                    ],
                );
            }
        });

        return response()->json(['synced' => array_column($data['messages'], 'client_id')])
            ->header('Cache-Control', 'no-store');
    }
}
