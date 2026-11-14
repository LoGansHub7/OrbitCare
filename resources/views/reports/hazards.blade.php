@extends('layouts.app')
@section('title', 'Hazard report')
@section('heading', 'Hazard report')
@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 no-print">
        <div>
            <div class="h5 mb-1">{{ $crew->user->name }} <span class="text-secondary">· {{ $crew->crew_code }}</span></div>
            <div class="text-secondary">{{ $crew->mission->name }} · {{ $crew->mission->mission_profile ?? 'LEO' }} profile</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ $isStaff ? route('hazard-export.assigned', $crew->id) : route('hazard-export.self') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-filetype-csv" aria-hidden="true"></i> Download CSV</a>
            <button type="button" class="btn btn-primary btn-sm" onclick="window.print()"><i class="bi bi-printer" aria-hidden="true"></i> Print / Save PDF</button>
        </div>
    </div>

    <div class="alert alert-secondary" role="note">Statuses come from experimental rules with team-assumed thresholds. "No flag" means no implemented rule triggered; it is not a medical clearance. Rows marked Simulation are synthetic demonstration data, not NASA measurements.</div>

    @foreach ($rows as $row)
        <section class="oc-card mb-3" aria-labelledby="h-{{ $row['hazard']->slug }}">
            <div class="d-flex flex-wrap justify-content-between gap-2">
                <h2 id="h-{{ $row['hazard']->slug }}" class="oc-card-title mb-0">{{ $row['hazard']->name }}</h2>
                <span class="badge text-bg-light text-capitalize">{{ str_replace('_', ' ', $row['status']) }}@if ($row['alerts']->isNotEmpty()) · {{ $row['alerts']->count() }} open @endif</span>
            </div>
            <p class="mb-1 mt-2">{{ $row['latest'] }}</p>
            <p class="small text-secondary mb-2">
                {{ $row['record_count'] }} record(s)@if ($row['latest_at']) · latest {{ $row['latest_at']->utc()->format('M j, Y H:i') }} UTC @endif
                @if ($row['includes_simulated']) · <x-source-label source="simulation" /> @endif
            </p>
            @foreach ($row['alerts'] as $alert)
                <div class="border rounded-3 p-2 mb-2 small"><strong>{{ $alert->summary }}</strong> ({{ $alert->rule?->code }} {{ $alert->rule?->version }}, {{ str_replace('_', ' ', $alert->status) }})<br>{{ $alert->explanation }}</div>
            @endforeach
            @if ($row['hazard']->references->isNotEmpty())
                <div class="small">Sources:
                    @foreach ($row['hazard']->references as $reference)
                        <a href="{{ $reference->url }}" target="_blank" rel="noopener noreferrer">{{ $reference->title }}</a>@if (! $loop->last), @endif
                    @endforeach
                </div>
            @endif
        </section>
    @endforeach

    <p class="small text-secondary">Generated {{ $generatedAt->format('M j, Y H:i') }} UTC. OrbitCare is a prototype, not a medical device, and gives no diagnosis or treatment advice.</p>
@endsection
