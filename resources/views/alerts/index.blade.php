@extends('layouts.app')
@section('title', 'Alerts')
@section('heading', 'My alerts')
@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <p class="text-secondary mb-0">Experimental rule results and their review history for your records.</p>
        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left" aria-hidden="true"></i> Dashboard</a>
    </div>

    <nav class="d-flex flex-wrap gap-2 mb-3" aria-label="Filter alerts by hazard">
        <a href="{{ route('alerts.index') }}" class="btn btn-sm {{ $hazardSlug ? 'btn-outline-secondary' : 'btn-secondary' }}">All hazards</a>
        @foreach ($hazards as $hazard)
            <a href="{{ route('alerts.index', ['hazard' => $hazard->slug]) }}" class="btn btn-sm {{ $hazardSlug === $hazard->slug ? 'btn-secondary' : 'btn-outline-secondary' }}">{{ $hazard->name }}</a>
        @endforeach
    </nav>

    <div class="oc-card">
        @if ($alerts->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-bell-slash fs-2 text-secondary" aria-hidden="true"></i>
                <h2 class="h5 mt-3">No alert history yet</h2>
                <p class="text-secondary mb-0">“No flag” only means that no implemented rule triggered. It is not a medical clearance statement.</p>
            </div>
        @else
            <div class="d-md-none">
                @foreach ($alerts as $alert)
                    <article class="oc-alert-link border rounded-3 p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <span class="fw-semibold">{{ $alert->summary }}</span>
                            <span class="badge text-bg-light text-capitalize">{{ str_replace('_', ' ', $alert->status) }}</span>
                        </div>
                        <div class="small text-secondary mb-3">
                            {{ $alert->rule?->code ?? 'Experimental rule' }} · {{ $alert->rule?->version ?? 'unknown version' }}<br>
                            Last evaluated {{ $alert->last_evaluated_at?->format('M j, Y H:i') ?? '—' }} UTC
                        </div>
                        <a href="{{ route('alerts.show', ['alertId' => $alert->id]) }}" class="btn btn-sm btn-outline-primary">View alert details</a>
                    </article>
                @endforeach
            </div>
            <div class="table-responsive d-none d-md-block">
                <table class="table align-middle mb-0">
                    <thead>
                    <tr><th>Alert</th><th>Rule</th><th>Status</th><th>Last evaluated (UTC)</th><th><span class="visually-hidden">Details</span></th></tr>
                    </thead>
                    <tbody>
                    @foreach ($alerts as $alert)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $alert->summary }}</div>
                                <div class="small text-secondary">{{ ucfirst(str_replace('_', ' ', $alert->severity)) }}</div>
                            </td>
                            <td>{{ $alert->rule?->code ?? 'Experimental rule' }} <span class="text-secondary">({{ $alert->rule?->version ?? 'unknown version' }})</span>
                                @if ($alert->rule?->hazard)<div class="small text-secondary">{{ $alert->rule->hazard->name }}</div>@endif
                            </td>
                            <td><span class="text-capitalize">{{ str_replace('_', ' ', $alert->status) }}</span></td>
                            <td>{{ $alert->last_evaluated_at?->format('M j, Y H:i') ?? '—' }}</td>
                            <td><a href="{{ route('alerts.show', ['alertId' => $alert->id]) }}" class="btn btn-sm btn-outline-primary">View details</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $alerts->links() }}</div>
        @endif
    </div>

    <p class="small text-secondary mt-3">Alert rules and thresholds are experimental assumptions, not a diagnosis or treatment recommendation.</p>
@endsection
