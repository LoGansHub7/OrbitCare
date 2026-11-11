<?php

namespace App\Http\Controllers;

use App\Models\AuditEvent;
use App\Models\DailyCheckin;
use App\Services\AlertEvaluationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckinController extends Controller
{
    public function create(Request $request)
    {
        $crew = $request->user()->crewProfile()->with('mission')->firstOrFail();
        $today = today();

        $existing = $crew->checkins()->whereDate('mission_date', $today->toDateString())->first();
        $last = $crew->checkins()->where('mission_date', '<', $today->toDateString())->latest('mission_date')->first();

        return view('checkin.create', compact('crew', 'existing', 'last', 'today'));
    }

    public function store(Request $request)
    {
        $crew = $request->user()->crewProfile()->firstOrFail();

        $data = $request->validate([
            'sleep_hours' => ['required', 'numeric', 'min:0', 'max:24'],
            'sleep_quality' => ['required', 'integer', 'between:1,5'],
            'fatigue_rating' => ['required', 'integer', 'between:1,5'],
            'exercise_minutes' => ['required', 'integer', 'between:0,600'],
            'mood_rating' => ['nullable', 'integer', 'between:1,5'],
            'stress_rating' => ['nullable', 'integer', 'between:1,5'],
            'social_connectedness_rating' => ['nullable', 'integer', 'between:1,5'],
            'planned_aerobic_minutes' => ['nullable', 'integer', 'between:0,600'],
            'completed_aerobic_minutes' => ['nullable', 'integer', 'between:0,600'],
            'planned_resistive_minutes' => ['nullable', 'integer', 'between:0,600'],
            'completed_resistive_minutes' => ['nullable', 'integer', 'between:0,600'],
            'symptom_tags' => ['nullable', 'array'],
            'symptom_tags.*' => ['string', 'in:headache,nausea,congestion,skin_irritation,vision_change,back_pain'],
            'symptom_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $data['source'] = 'manual';

        $date = today()->toDateString();

        $corrected = DB::transaction(function () use ($crew, $data, $date) {
            $existing = DailyCheckin::where('crew_profile_id', $crew->id)->whereDate('mission_date', $date)->lockForUpdate()->first();

            if ($existing) {
                $before = $existing->only(['sleep_hours', 'sleep_quality', 'fatigue_rating', 'exercise_minutes', 'symptom_notes']);
                $existing->update($data + ['submitted_at' => now()]);
                AuditEvent::record('checkin.corrected', $existing, ['before' => $before]);

                return true;
            }

            $checkin = DailyCheckin::create($data + [
                'crew_profile_id' => $crew->id,
                'mission_date' => $date,
                'source' => 'manual',
                'submitted_at' => now(),
            ]);
            foreach ($data['symptom_tags'] ?? [] as $tag) {
                $checkin->symptomLogs()->create(['crew_profile_id' => $crew->id, 'tag' => $tag, 'source' => $data['source']]);
            }
            AuditEvent::record('checkin.created', $checkin);

            return false;
        });

        app(AlertEvaluationService::class)->evaluateCrew($crew);

        return redirect()->route('dashboard')->with('status', $corrected ? 'Today’s check-in was updated.' : 'Check-in saved.');
    }
}
