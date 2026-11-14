@extends('layouts.app')
@section('title', 'Crew member review')
@section('heading', 'Crew member review')
@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <div class="h4 mb-1">{{ $crew->user->name }} <span class="text-secondary">· {{ $crew->crew_code }}</span></div>
            <div class="text-secondary">{{ $crew->mission->name }} · Mission day {{ $crew->missionDay() }}</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('reports.assigned', ['crewProfileId' => $crew->id]) }}" class="btn btn-primary btn-sm"><i class="bi bi-file-earmark-text" aria-hidden="true"></i> Printable report</a>
            <a href="{{ route('hazard-report.assigned', ['crewProfileId' => $crew->id]) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-globe2" aria-hidden="true"></i> Hazard report</a>
            <a href="{{ route('staff.home') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left" aria-hidden="true"></i> Crew overview</a>
        </div>
    </div>

    <div class="alert alert-secondary" role="note">This page presents recorded data and experimental rules only. It is not a diagnosis or treatment recommendation.</div>

    <section class="oc-card mb-3" aria-labelledby="staff-hazards-heading">
        <h2 id="staff-hazards-heading" class="oc-card-title">Hazard status</h2>
        <ul class="list-unstyled mb-0">
            @foreach ($hazardRows as $row)
                <li class="d-flex flex-wrap justify-content-between gap-2 border-bottom py-2">
                    <span><strong>{{ $row['hazard']->name }}</strong> <span class="text-secondary small">{{ $row['latest'] }}</span></span>
                    <span class="text-capitalize">{{ str_replace('_', ' ', $row['status']) }}@if ($row['alerts']->isNotEmpty()) · {{ $row['alerts']->count() }} open @endif</span>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="oc-card mb-3" aria-labelledby="staff-alerts-heading">
        <h2 id="staff-alerts-heading" class="oc-card-title">Alert history</h2>
        @forelse ($alerts as $alert)
            <article class="border rounded-3 p-3 mb-2">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <div class="fw-semibold">{{ $alert->summary }}</div>
                    <span class="text-capitalize">{{ str_replace('_', ' ', $alert->status) }}</span>
                </div>
                <div class="small text-secondary mt-1">{{ $alert->rule?->code }} · {{ $alert->rule?->version }} · Last evaluated {{ $alert->last_evaluated_at?->format('M j, Y H:i') }} UTC</div>
                <p class="mt-2 mb-2">{{ $alert->explanation }}</p>
                @if (is_array($alert->evidence) && count($alert->evidence))
                    <dl class="row small mb-0">
                        @foreach ($alert->evidence as $key => $value)
                            @continue(! is_scalar($value))
                            <dt class="col-sm-5 text-capitalize">{{ str_replace('_', ' ', $key) }}</dt>
                            <dd class="col-sm-7">{{ $value }}</dd>
                        @endforeach
                    </dl>
                @endif
                @if ($alert->followups->isNotEmpty())
                    <div class="small border-top pt-2 mt-2">
                        <strong>Latest follow-up:</strong>
                        {{ ucfirst(str_replace('_', ' ', $alert->followups->sortByDesc('created_at')->first()->action)) }}
                        @if ($alert->followups->sortByDesc('created_at')->first()->notes)
                            — {{ $alert->followups->sortByDesc('created_at')->first()->notes }}
                        @endif
                    </div>
                @endif
            </article>
        @empty
            <p class="text-secondary mb-0">No alert records are available.</p>
        @endforelse
    </section>

    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <section class="oc-card h-100" aria-labelledby="checkins-heading">
                <h2 id="checkins-heading" class="oc-card-title">Recent daily check-ins</h2>
                @if ($checkins->isEmpty())
                    <p class="text-secondary mb-0">No check-ins recorded.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Date</th><th>Sleep</th><th>Fatigue</th><th>Exercise</th><th>Source</th></tr></thead>
                            <tbody>
                            @foreach ($checkins as $checkin)
                                <tr>
                                    <td>{{ $checkin->mission_date->format('M j') }}</td>
                                    <td>{{ number_format((float) $checkin->sleep_hours, 1) }} h</td>
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
            <section class="oc-card h-100" aria-labelledby="readings-heading">
                <h2 id="readings-heading" class="oc-card-title">Recent resting heart rate</h2>
                @if ($readings->isEmpty())
                    <p class="text-secondary mb-0">No heart-rate readings recorded.</p>
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
@endsection
