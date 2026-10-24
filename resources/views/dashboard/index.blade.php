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
        <div class="col-12 col-xl-8">
            <div class="oc-card h-100">
                <div class="oc-card-title">Resting heart rate <span class="oc-unit">daily average · last {{ $range }} days</span></div>
                @include('dashboard._chart', ['chart' => $charts['hr']])
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="oc-card h-100">
                <div class="oc-card-title">Alerts</div>
                <p class="mb-2"><span class="oc-badge oc-badge-unknown"><i class="bi bi-hourglass-split" aria-hidden="true"></i> Alert engine not active yet</span></p>
                <p class="small text-secondary mb-0">Explainable alert rules arrive in Phase 2. When they do, “no flag” will only mean that no implemented rule triggered — never that you are medically healthy.</p>
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
