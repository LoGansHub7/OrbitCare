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
