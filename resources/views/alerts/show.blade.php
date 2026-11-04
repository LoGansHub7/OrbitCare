@extends('layouts.app')
@section('title', 'Alert details')
@section('heading', 'Alert details')
@section('content')
    @php
        $statusLabels = [
            'open' => 'Open',
            'acknowledged' => 'Acknowledged',
            'under_review' => 'Under review',
            'closed' => 'Closed',
        ];
        $evidenceLabels = [
            'window_days' => 'Evaluation window',
            'avg_sleep_hours' => 'Average sleep',
            'avg_fatigue_score' => 'Average fatigue',
            'baseline_sleep_hours' => 'Sleep baseline',
            'baseline_fatigue_score' => 'Fatigue baseline',
            'sample_count' => 'Samples',
            'avg_resting_heart_rate_bpm' => 'Average resting heart rate',
            'baseline_resting_heart_rate_bpm' => 'Heart-rate baseline',
            'delta_bpm' => 'Difference from baseline',
        ];
        $evidenceUnits = [
            'window_days' => 'days',
            'avg_sleep_hours' => 'hours',
            'baseline_sleep_hours' => 'hours',
            'avg_fatigue_score' => '/ 5',
            'baseline_fatigue_score' => '/ 5',
            'avg_resting_heart_rate_bpm' => 'bpm',
            'baseline_resting_heart_rate_bpm' => 'bpm',
            'delta_bpm' => 'bpm',
        ];
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <a href="{{ route('alerts.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left" aria-hidden="true"></i> Alert history</a>
        <span class="oc-badge {{ $alert->status === 'closed' ? 'oc-badge-unknown' : 'oc-badge-review' }}">
            <i class="bi bi-circle-fill" aria-hidden="true"></i> {{ $statusLabels[$alert->status] ?? ucfirst($alert->status) }}
        </span>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-8">
            <section class="oc-card mb-3" aria-labelledby="alert-summary-heading">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <div class="small text-secondary mb-1">Experimental alert · {{ ucfirst(str_replace('_', ' ', $alert->severity)) }}</div>
                        <h2 id="alert-summary-heading" class="h4 mb-0">{{ $alert->summary }}</h2>
                    </div>
                    <div class="small text-secondary text-end">
                        <div>First detected: {{ $alert->first_detected_at?->format('M j, Y H:i') }} UTC</div>
                        <div>Last evaluated: {{ $alert->last_evaluated_at?->format('M j, Y H:i') }} UTC</div>
                    </div>
                </div>
                <p class="mb-0">{{ $alert->explanation }}</p>
            </section>

            <section class="oc-card mb-3" aria-labelledby="evidence-heading">
                <h2 id="evidence-heading" class="oc-card-title">Evidence snapshot</h2>
                @if (is_array($alert->evidence) && count($alert->evidence))
                    <div class="oc-evidence-grid">
                        @foreach ($alert->evidence as $key => $value)
                            @continue(! is_scalar($value))
                            <div class="oc-evidence-item">
                                <div class="small text-secondary">{{ $evidenceLabels[$key] ?? ucfirst(str_replace('_', ' ', $key)) }}</div>
                                <div class="fw-semibold">
                                    {{ is_numeric($value) && ! in_array($key, ['window_days', 'sample_count'], true) ? number_format((float) $value, 1) : $value }}
                                    {{ $evidenceUnits[$key] ?? '' }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-secondary mb-0">No evidence details were saved with this alert.</p>
                @endif
            </section>

            <section class="oc-card" aria-labelledby="timeline-heading">
                <h2 id="timeline-heading" class="oc-card-title">Follow-up timeline</h2>
                @forelse ($alert->followups->sortBy('created_at') as $followup)
                    <div class="oc-timeline">
                        <div class="oc-timeline-item">
                            <div class="fw-semibold">{{ ucfirst(str_replace('_', ' ', $followup->action)) }}</div>
                            <div class="small text-secondary">
                                {{ $followup->created_at->format('M j, Y H:i') }} UTC
                                @if ($followup->actor)
                                    · {{ $followup->actor->name }}
                                @endif
                            </div>
                            @if ($followup->notes)
                                <p class="mb-0 mt-2">{{ $followup->notes }}</p>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-secondary mb-0">No follow-up actions recorded yet.</p>
                @endforelse
            </section>
        </div>

        <div class="col-12 col-xl-4">
            <section class="oc-card mb-3" aria-labelledby="rule-heading">
                <h2 id="rule-heading" class="oc-card-title">Rule information</h2>
                <dl class="small mb-0">
                    <dt>Rule</dt><dd>{{ $alert->rule?->code ?? 'Unknown rule' }}</dd>
                    <dt>Version</dt><dd>{{ $alert->rule?->version ?? 'Not recorded' }}</dd>
                    <dt>Rule reference</dt><dd>{{ $alert->rule?->evidence_reference ?? 'Experimental team assumption' }}</dd>
                    <dt>Parameters</dt>
                    <dd class="mb-0">
                        @if ($alert->rule?->parameters)
                            <ul class="ps-3 mb-0">
                                @foreach ($alert->rule->parameters as $key => $value)
                                    <li>{{ ucfirst(str_replace('_', ' ', $key)) }}: {{ $value }}</li>
                                @endforeach
                            </ul>
                        @else
                            Not recorded
                        @endif
                    </dd>
                </dl>
            </section>

            <section class="oc-card" aria-labelledby="action-heading">
                <h2 id="action-heading" class="oc-card-title">Review action</h2>
                @if ($alert->status === 'open')
                    <p class="small text-secondary">Acknowledge this alert to record that you have seen it.</p>
                    <form method="POST" action="{{ route('alerts.update', ['alertId' => $alert->id]) }}">
                        @csrf
                        <input type="hidden" name="action" value="acknowledge">
                        <button class="btn btn-primary w-100" type="submit"><i class="bi bi-check2" aria-hidden="true"></i> Acknowledge alert</button>
                    </form>
                @elseif ($alert->status === 'acknowledged')
                    <p class="small text-secondary">Start a review to document follow-up actions.</p>
                    <form method="POST" action="{{ route('alerts.update', ['alertId' => $alert->id]) }}">
                        @csrf
                        <input type="hidden" name="action" value="start_review">
                        <button class="btn btn-primary w-100" type="submit"><i class="bi bi-search" aria-hidden="true"></i> Start review</button>
                    </form>
                @elseif ($alert->status === 'under_review')
                    <p class="small text-secondary">Record an observation or a re-check, then close with a note. Do not enter treatment instructions.</p>
                    <form method="POST" action="{{ route('alerts.update', ['alertId' => $alert->id]) }}" class="mb-3">
                        @csrf
                        <input type="hidden" name="action" value="log_followup">
                        <label for="followup_notes" class="form-label">Follow-up observation</label>
                        <textarea id="followup_notes" name="notes" class="form-control mb-2" rows="3" maxlength="2000" placeholder="For example: rechecked measurement or logged an observation."></textarea>
                        <label class="form-check small mb-2"><input class="form-check-input" type="checkbox" name="share_with_earth" value="1"> <span class="form-check-label">Queue this summary for Earth support</span></label>
                        <button class="btn btn-outline-primary w-100" type="submit"><i class="bi bi-journal-plus" aria-hidden="true"></i> Record follow-up</button>
                    </form>
                    <form method="POST" action="{{ route('alerts.update', ['alertId' => $alert->id]) }}">
                        @csrf
                        <input type="hidden" name="action" value="close">
                        <label for="notes" class="form-label">Closing note</label>
                        <textarea id="notes" name="notes" class="form-control mb-2" rows="4" maxlength="2000" required>{{ old('notes') }}</textarea>
                        <label class="form-check small mb-2"><input class="form-check-input" type="checkbox" name="share_with_earth" value="1"> <span class="form-check-label">Queue this closure summary for Earth support</span></label>
                        @error('notes')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                        <button class="btn btn-primary w-100" type="submit"><i class="bi bi-check-circle" aria-hidden="true"></i> Close alert</button>
                    </form>
                @else
                    <p class="mb-0 text-secondary">This alert is closed. Its evidence and follow-up history are retained.</p>
                @endif
            </section>
        </div>
    </div>

    <p class="small text-secondary mt-3">This experimental rule is not a diagnosis and does not provide medical advice. “No flag” does not mean medically healthy.</p>
@endsection
