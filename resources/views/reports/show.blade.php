@extends('layouts.app')
@section('title', 'Health summary')
@section('heading', $isStaff ? 'Crew summary report' : 'My health summary')
@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 no-print">
        <form method="GET" action="{{ $isStaff ? route('reports.assigned', ['crewProfileId' => $crew->id]) : route('reports.self') }}" class="d-flex align-items-center gap-2">
            <label for="report-range" class="form-label mb-0">Date range</label>
            <select id="report-range" name="range" class="form-select" onchange="this.form.submit()">
                <option value="7" @selected($range === 7)>7 days</option>
                <option value="14" @selected($range === 14)>14 days</option>
                <option value="30" @selected($range === 30)>30 days</option>
            </select>
        </form>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ $isStaff ? route('exports.assigned', ['crewProfileId' => $crew->id]) : route('exports.self') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-download" aria-hidden="true"></i> Export CSV</a>
            <button type="button" class="btn btn-primary btn-sm" onclick="window.print()"><i class="bi bi-printer" aria-hidden="true"></i> Print / Save PDF</button>
            @if ($isStaff)
                <a href="{{ route('staff.crew.show', ['crewProfileId' => $crew->id]) }}" class="btn btn-outline-secondary btn-sm">Crew details</a>
            @endif
        </div>
    </div>

    <header class="oc-card mb-3">
        <div class="small text-secondary mb-1">OrbitCare · experimental astronaut health tracking prototype</div>
        <h2 class="h4 mb-1">{{ $crew->user->name }} · {{ $crew->crew_code }}</h2>
        <div>{{ $crew->mission->name }}</div>
        <div class="small text-secondary mt-2">Report period: {{ $from->format('M j, Y') }} – {{ today()->format('M j, Y') }} · Generated {{ now()->format('M j, Y H:i') }} UTC</div>
    </header>

    <div class="alert alert-warning" role="note">This report contains recorded data and experimental rules only. It is not a diagnosis, medical clearance, or treatment recommendation.</div>

    <section class="oc-card mb-3" aria-labelledby="summary-heading">
        <h2 id="summary-heading" class="oc-card-title">Indicator summary</h2>
        <div class="row g-3">
            <div class="col-6 col-lg-3"><div class="oc-evidence-item"><div class="small text-secondary">Check-in days recorded</div><div class="fs-4 fw-semibold">{{ $summary['checkin_days'] }} / {{ $range }}</div></div></div>
            <div class="col-6 col-lg-3"><div class="oc-evidence-item"><div class="small text-secondary">Average sleep</div><div class="fs-4 fw-semibold">{{ $summary['average_sleep'] !== null ? number_format($summary['average_sleep'], 1).' h' : 'No data' }}</div></div></div>
            <div class="col-6 col-lg-3"><div class="oc-evidence-item"><div class="small text-secondary">Average fatigue</div><div class="fs-4 fw-semibold">{{ $summary['average_fatigue'] !== null ? number_format($summary['average_fatigue'], 1).' / 5' : 'No data' }}</div></div></div>
            <div class="col-6 col-lg-3"><div class="oc-evidence-item"><div class="small text-secondary">Average resting heart rate</div><div class="fs-4 fw-semibold">{{ $summary['average_heart_rate'] !== null ? number_format($summary['average_heart_rate'], 1).' bpm' : 'No data' }}</div></div></div>
        </div>
        <p class="small text-secondary mt-3 mb-0">Missing entries indicate no recorded data, not a normal value. Summary averages are descriptive only.</p>
    </section>

    <section class="oc-card mb-3" aria-labelledby="report-alerts-heading">
        <h2 id="report-alerts-heading" class="oc-card-title">Alerts and follow-ups</h2>
        @forelse ($alerts as $alert)
            <article class="border rounded-3 p-3 mb-2">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <strong>{{ $alert->summary }}</strong>
                    <span class="text-capitalize">{{ str_replace('_', ' ', $alert->status) }}</span>
                </div>
                <div class="small text-secondary">{{ $alert->rule?->code }} · {{ $alert->rule?->version }} · First detected {{ $alert->first_detected_at?->format('M j, Y H:i') }} UTC</div>
                <p class="mb-2 mt-2">{{ $alert->explanation }}</p>
                @if ($alert->followups->isNotEmpty())
                    <ul class="small mb-0">
                        @foreach ($alert->followups->sortBy('created_at') as $followup)
                            <li>
                                {{ ucfirst(str_replace('_', ' ', $followup->action)) }} · {{ $followup->created_at->format('M j, Y H:i') }} UTC
                                @if ($followup->actor) · {{ $followup->actor->name }} @endif
                                @if ($followup->notes) — {{ $followup->notes }} @endif
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="small text-secondary">No follow-ups recorded.</div>
                @endif
            </article>
        @empty
            <p class="text-secondary mb-0">No alerts detected in this report period. This is not a medical clearance statement.</p>
        @endforelse
    </section>

    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-6">
            <section class="oc-card h-100" aria-labelledby="checkin-heading">
                <h2 id="checkin-heading" class="oc-card-title">Daily check-ins</h2>
                @if ($checkins->isEmpty())
                    <p class="text-secondary mb-0">No check-ins recorded in this period.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Date</th><th>Sleep</th><th>Quality</th><th>Fatigue</th><th>Exercise</th><th>Source</th></tr></thead>
                            <tbody>
                            @foreach ($checkins as $checkin)
                                <tr>
                                    <td>{{ $checkin->mission_date->format('M j') }}</td>
                                    <td>{{ number_format((float) $checkin->sleep_hours, 1) }} h</td>
                                    <td>{{ $checkin->sleep_quality }} / 5</td>
                                    <td>{{ $checkin->fatigue_rating }} / 5</td>
                                    <td>{{ $checkin->exercise_minutes }} min</td>
                                    <td><x-source-label :source="$checkin->source" /></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>
        <div class="col-12 col-xl-6">
            <section class="oc-card h-100" aria-labelledby="reading-heading">
                <h2 id="reading-heading" class="oc-card-title">Resting heart-rate readings</h2>
                @if ($readings->isEmpty())
                    <p class="text-secondary mb-0">No heart-rate readings recorded in this period.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Measured (UTC)</th><th>Value</th><th>Source</th></tr></thead>
                            <tbody>
                            @foreach ($readings as $reading)
                                <tr>
                                    <td>{{ $reading->observed_at->format('M j, H:i') }}</td>
                                    <td>{{ number_format((float) $reading->value, 1) }} {{ $heartRateDefinition->unit }}</td>
                                    <td><x-source-label :source="$reading->source" /></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>
    </div>

    <footer class="small text-secondary">Data sources are labelled in each row. Simulation entries are fictional demonstration records. Baselines and alert thresholds are experimental assumptions and have not been clinically validated. See the <a href="{{ route('research.index') }}">research and limitations register</a>.</footer>
@endsection
