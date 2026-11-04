@extends('layouts.app')
@section('title', 'Daily check-in')
@section('heading', 'Daily check-in')
@section('content')
<div class="row g-3">
    <div class="col-12 col-lg-8">
        <div class="oc-card">
            <p class="text-secondary">
                {{ $today->format('l, M j') }} · Mission day {{ $crew->missionDay() }}.
                @if ($existing) You already checked in today — saving will correct that entry (the change is logged). @endif
            </p>
            <form method="POST" action="{{ route('checkin.store') }}" novalidate>
                @csrf
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label for="sleep_hours" class="form-label">Sleep duration (hours)</label>
                        <input id="sleep_hours" name="sleep_hours" type="number" step="0.1" min="0" max="24" inputmode="decimal"
                               value="{{ old('sleep_hours', $existing?->sleep_hours) }}" class="form-control @error('sleep_hours') is-invalid @enderror" required>
                        @error('sleep_hours')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12"><hr><div class="fw-semibold mb-1">Wellbeing <span class="text-secondary fw-normal">(optional self-report)</span></div><p class="small text-secondary">These neutral observations support a trend view; they are not diagnostic.</p></div>
                    @foreach (['mood_rating' => 'Mood (5 = best)', 'stress_rating' => 'Stress (5 = highest)', 'social_connectedness_rating' => 'Connectedness (5 = most connected)'] as $field => $label)
                    <div class="col-12 col-md-4"><label for="{{ $field }}" class="form-label">{{ $label }}</label><select id="{{ $field }}" name="{{ $field }}" class="form-select"><option value="">Not recorded</option>@for($value = 1; $value <= 5; $value++)<option value="{{ $value }}" @selected((string)old($field, $existing?->$field) === (string)$value)>{{ $value }}</option>@endfor</select></div>
                    @endforeach
                    <div class="col-12"><hr><div class="fw-semibold mb-2">Exercise countermeasures <span class="text-secondary fw-normal">(optional)</span></div></div>
                    @foreach (['planned_aerobic_minutes' => 'Planned aerobic (min)', 'completed_aerobic_minutes' => 'Completed aerobic (min)', 'planned_resistive_minutes' => 'Planned resistive (min)', 'completed_resistive_minutes' => 'Completed resistive (min)'] as $field => $label)
                    <div class="col-6 col-md-3"><label for="{{ $field }}" class="form-label">{{ $label }}</label><input id="{{ $field }}" name="{{ $field }}" type="number" min="0" max="600" class="form-control" value="{{ old($field, $existing?->$field) }}"></div>
                    @endforeach
                    <div class="col-12"><label class="form-label d-block">Symptoms <span class="text-secondary fw-normal">(optional self-report)</span></label>@php($selectedTags = old('symptom_tags', $existing?->symptom_tags ?? []))<div class="d-flex flex-wrap gap-3">@foreach(['headache'=>'Headache','nausea'=>'Nausea','congestion'=>'Congestion','skin_irritation'=>'Skin irritation','vision_change'=>'Vision change','back_pain'=>'Back pain'] as $tag => $label)<label class="form-check"><input class="form-check-input" name="symptom_tags[]" value="{{ $tag }}" type="checkbox" @checked(in_array($tag, $selectedTags))> <span class="form-check-label">{{ $label }}</span></label>@endforeach</div></div>
                    <div class="col-12 col-md-6">
                        <label for="exercise_minutes" class="form-label">Exercise (minutes)</label>
                        <input id="exercise_minutes" name="exercise_minutes" type="number" step="1" min="0" max="600" inputmode="numeric"
                               value="{{ old('exercise_minutes', $existing?->exercise_minutes) }}" class="form-control @error('exercise_minutes') is-invalid @enderror" required>
                        @error('exercise_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="sleep_quality" class="form-label">Sleep quality</label>
                        <select id="sleep_quality" name="sleep_quality" class="form-select @error('sleep_quality') is-invalid @enderror" required>
                            <option value="">Select…</option>
                            @foreach ([1 => '1 – Very poor', 2 => '2 – Poor', 3 => '3 – Fair', 4 => '4 – Good', 5 => '5 – Very good'] as $v => $l)
                                <option value="{{ $v }}" @selected((string) old('sleep_quality', $existing?->sleep_quality) === (string) $v)>{{ $l }}</option>
                            @endforeach
                        </select>
                        @error('sleep_quality')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="fatigue_rating" class="form-label">Fatigue</label>
                        <select id="fatigue_rating" name="fatigue_rating" class="form-select @error('fatigue_rating') is-invalid @enderror" required>
                            <option value="">Select…</option>
                            @foreach ([1 => '1 – Not fatigued', 2 => '2 – Slightly fatigued', 3 => '3 – Moderately fatigued', 4 => '4 – Very fatigued', 5 => '5 – Extremely fatigued'] as $v => $l)
                                <option value="{{ $v }}" @selected((string) old('fatigue_rating', $existing?->fatigue_rating) === (string) $v)>{{ $l }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Higher number = more fatigued.</div>
                        @error('fatigue_rating')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label for="symptom_notes" class="form-label">Symptom notes <span class="text-secondary fw-normal">(optional)</span></label>
                        <textarea id="symptom_notes" name="symptom_notes" rows="3" maxlength="1000" class="form-control @error('symptom_notes') is-invalid @enderror">{{ old('symptom_notes', $existing?->symptom_notes) }}</textarea>
                        @error('symptom_notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <button class="btn btn-primary mt-4" type="submit"><i class="bi bi-check2" aria-hidden="true"></i> {{ $existing ? 'Update check-in' : 'Save check-in' }}</button>
            </form>
        </div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="oc-card">
            <div class="oc-card-title">Last completed check-in</div>
            @if ($last)
                <div class="mb-2"><x-source-label :source="$last->source" /></div>
                <dl class="row small mb-0">
                    <dt class="col-7">Date</dt><dd class="col-5">{{ $last->mission_date->format('M j') }}</dd>
                    <dt class="col-7">Sleep (h)</dt><dd class="col-5">{{ number_format((float) $last->sleep_hours, 1) }}</dd>
                    <dt class="col-7">Sleep quality</dt><dd class="col-5">{{ $last->sleep_quality }}</dd>
                    <dt class="col-7">Fatigue</dt><dd class="col-5">{{ $last->fatigue_rating }}</dd>
                    <dt class="col-7">Exercise (min)</dt><dd class="col-5">{{ $last->exercise_minutes }}</dd>
                </dl>
            @else
                <p class="text-secondary mb-0">None yet.</p>
            @endif
        </div>
    </div>
</div>
@endsection
