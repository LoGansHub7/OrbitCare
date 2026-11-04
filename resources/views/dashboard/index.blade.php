@extends('layouts.app')
@section('title', 'Dashboard')
@section('heading', 'My dashboard')
@section('content')

    <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
        <div class="btn-group" role="group" aria-label="Date range">
            <a href="{{ route('dashboard', ['range' => 7]) }}" class="btn btn-sm {{ $range === 7 ? 'btn-primary' : 'btn-outline-secondary' }}">7 days</a>
            <a href="{{ route('dashboard', ['range' => 14]) }}" class="btn btn-sm {{ $range === 14 ? 'btn-primary' : 'btn-outline-secondary' }}">14 days</a>
        </div>
        @unless ($checkedInToday)
            <a href="{{ route('checkin.create') }}" class="btn btn-primary btn-sm ms-auto"><i class="bi bi-clipboard2-pulse" aria-hidden="true"></i> Complete today’s check-in</a>
        @else
            <span class="oc-badge oc-badge-ok ms-auto"><i class="bi bi-check-circle" aria-hidden="true"></i> Today’s check-in done</span>
        @endunless
    </div>

    <div class="row g-3 mb-3">
        @foreach ($cards as $card)
            <div class="col-12 col-sm-6 col-xl-3"><x-metric-card :card="$card" /></div>
        @endforeach
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-8"><div class="oc-card h-100"><div class="oc-card-title">Body-system readiness map <span class="oc-unit">review signals, not a health score</span></div><div class="row g-2 text-center">@foreach(['Cardiovascular'=>'bi-heart-pulse','Sleep & circadian'=>'bi-moon-stars','Musculoskeletal'=>'bi-person-walking','Behavioural'=>'bi-chat-heart','Immune / general'=>'bi-shield-plus','Radiation / environment'=>'bi-broadcast'] as $system=>$icon)<div class="col-6 col-md-4"><a href="{{ route('alerts.index') }}" class="border rounded-3 p-3 d-block text-decoration-none text-reset h-100"><i class="bi {{ $icon }} fs-3 text-primary" aria-hidden="true"></i><div class="small fw-semibold mt-2">{{ $system }}</div><span class="oc-badge {{ $alerts->isNotEmpty() ? 'oc-badge-review' : 'oc-badge-ok' }} mt-2"><i class="bi {{ $alerts->isNotEmpty() ? 'bi-eye' : 'bi-check-circle' }}" aria-hidden="true"></i>{{ $alerts->isNotEmpty() ? 'Review signals' : 'No flag' }}</span></a></div>@endforeach</div></div></div>
        <div class="col-12 col-xl-4"><div class="oc-card h-100 d-flex align-items-center justify-content-center"><x-stat-ring :value="$completeness" label="Today’s input completeness"/><p class="small text-secondary text-center mt-3 mb-0">Completeness measures recorded inputs only; it is not a health score.</p></div></div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-8">
            <div class="oc-card h-100">
                <div class="oc-card-title">Resting heart rate <span class="oc-unit">daily average · last {{ $range }} days</span></div>
                @include('dashboard._chart', ['chart' => $charts['hr']])
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="oc-card h-100">
                <div class="oc-card-title">Alerts</div>
                @forelse ($alerts as $alert)
                    @php
                        $badgeClass = $alert->severity === 'priority_review' ? 'oc-badge-priority-review' : 'oc-badge-review';
                    @endphp
                    <a href="{{ route('alerts.show', $alert) }}" class="oc-alert-link border rounded-3 p-3 mb-2 d-block text-decoration-none text-reset">
                        <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                            <span class="oc-badge {{ $badgeClass }}">
                                <i class="bi {{ $alert->severity === 'priority_review' ? 'bi-exclamation-triangle-fill' : 'bi-eye' }}" aria-hidden="true"></i>
                                {{ $alert->severity === 'priority_review' ? 'Priority review' : 'Review' }}
                            </span>
                            <small class="text-secondary text-capitalize">{{ str_replace('_', ' ', $alert->status) }}</small>
                        </div>
                        <div class="fw-semibold mb-1">{{ $alert->summary }}</div>
                        <div class="small text-secondary">{{ $alert->rule?->code ?? 'experimental_rule' }} · {{ $alert->last_evaluated_at?->format('M j') }}</div>
                    </a>
                @empty
                    <p class="mb-2"><span class="oc-badge oc-badge-ok"><i class="bi bi-check-circle" aria-hidden="true"></i> No flag</span></p>
                    <p class="small text-secondary mb-0">“No flag” only means no implemented rule triggered. It is never a medical clearance statement.</p>
                @endforelse
                <a href="{{ route('alerts.index') }}" class="btn btn-outline-secondary btn-sm mt-2">View alert history</a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-6">
            <div class="oc-card h-100">
                <div class="oc-card-title">Sleep duration</div>
                @include('dashboard._chart', ['chart' => $charts['sleep']])
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="oc-card h-100">
                <div class="oc-card-title">Fatigue rating</div>
                @include('dashboard._chart', ['chart' => $charts['fatigue']])
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-4">
            <div class="oc-card h-100">
                <div class="oc-card-title">Latest check-in</div>
                @if ($latestCheckin)
                    <div class="mb-2"><x-source-label :source="$latestCheckin->source" /></div>
                    <dl class="row mb-0 small">
                        <dt class="col-7">Date</dt><dd class="col-5">{{ $latestCheckin->mission_date->format('M j') }}</dd>
                        <dt class="col-7">Sleep quality (5 = best)</dt><dd class="col-5">{{ $latestCheckin->sleep_quality }}</dd>
                        <dt class="col-7">Fatigue (5 = worst)</dt><dd class="col-5">{{ $latestCheckin->fatigue_rating }}</dd>
                        <dt class="col-12">Notes</dt><dd class="col-12">{{ $latestCheckin->symptom_notes ?: '—' }}</dd>
                    </dl>
                @else
                    <p class="text-secondary mb-0">No check-ins yet.</p>
                @endif
            </div>
        </div>
        <div class="col-12 col-xl-8">
            <div class="oc-card h-100">
                <div class="oc-card-title">Data table <span class="oc-unit">accessible alternative to the charts</span></div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Date</th><th>Sleep (h)</th><th>Quality</th><th>Fatigue</th><th>Exercise (min)</th><th>Resting HR (bpm)</th></tr></thead>
                        <tbody>
                        @foreach ($rows->reverse() as $r)
                            <tr>
                                <td>{{ $r['label'] }}</td>
                                <td>{{ $r['sleep'] !== null ? number_format($r['sleep'], 1) : '—' }}</td>
                                <td>{{ $r['quality'] ?? '—' }}</td>
                                <td>{{ $r['fatigue'] ?? '—' }}</td>
                                <td>{{ $r['exercise'] ?? '—' }}</td>
                                <td>{{ $r['hr'] !== null ? number_format($r['hr'], 1) : '—' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="small text-secondary mt-3 mb-0">“—” means no data was recorded, not a normal value. Rows labelled <em>Simulation</em> are synthetic demonstration data, not NASA measurements.</p>
            </div>
        </div>
    </div>
@endsection
