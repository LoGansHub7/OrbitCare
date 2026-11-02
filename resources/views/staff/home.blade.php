@extends('layouts.app')
@section('title', 'Overview')
@section('heading', 'Crew overview')
@section('content')
    @if ($isAdmin)
        <div class="oc-card">
            <div class="oc-card-title">Administrator workspace</div>
            <p class="mb-2">Account and assignment management is not available in this prototype yet.</p>
            <p class="mb-0 text-secondary">Administrator access does not automatically grant access to private health records.</p>
        </div>
    @else
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <p class="text-secondary mb-0">Assigned crew only. Review indicators are data freshness and outstanding experimental alerts, not a health ranking.</p>
            <form method="GET" action="{{ route('staff.home') }}" class="d-flex align-items-center gap-2">
                <label for="crew-filter" class="form-label mb-0">Show</label>
                <select id="crew-filter" name="filter" class="form-select" onchange="this.form.submit()">
                    <option value="all" @selected($filter === 'all')>All assigned crew</option>
                    <option value="review" @selected($filter === 'review')>Outstanding alerts</option>
                    <option value="stale" @selected($filter === 'stale')>Stale data</option>
                    <option value="no_data" @selected($filter === 'no_data')>No data</option>
                </select>
            </form>
        </div>

        <div class="oc-card">
            @if ($crewRows->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-people fs-2 text-secondary" aria-hidden="true"></i>
                    <h2 class="h5 mt-3">No matching assigned crew</h2>
                    <p class="text-secondary mb-0">Try another filter or ask an administrator to review assignments.</p>
                </div>
            @else
                <div class="d-md-none">
                    @foreach ($crewRows as $row)
                        <article class="border rounded-3 p-3 mb-3">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <div class="fw-semibold">{{ $row['crew']->crew_code }}</div>
                                    <div>{{ $row['crew']->user->name }}</div>
                                </div>
                                <span class="oc-badge {{ $row['freshness'] === 'current' ? 'oc-badge-ok' : 'oc-badge-unknown' }}">
                                    {{ $row['freshness'] === 'current' ? 'Current' : ucfirst(str_replace('_', ' ', $row['freshness'])) }}
                                </span>
                            </div>
                            <dl class="row small my-3 mb-2">
                                <dt class="col-7">Outstanding alerts</dt><dd class="col-5">{{ $row['active_alerts'] }}</dd>
                                <dt class="col-7">Last check-in</dt><dd class="col-5">{{ $row['latest_checkin']?->submitted_at?->format('M j, H:i') ?? 'No data' }}</dd>
                                <dt class="col-7">Resting heart rate</dt><dd class="col-5">{{ $row['latest_reading'] ? number_format((float) $row['latest_reading']->value, 1).' bpm' : 'No data' }}</dd>
                            </dl>
                            <a href="{{ route('staff.crew.show', ['crewProfileId' => $row['crew']->id]) }}" class="btn btn-sm btn-outline-primary w-100">Review crew member</a>
                        </article>
                    @endforeach
                </div>
                <div class="table-responsive d-none d-md-block">
                    <table class="table align-middle mb-0">
                        <thead>
                        <tr><th>Crew member</th><th>Outstanding alerts</th><th>Last check-in</th><th>Resting heart rate</th><th>Data freshness</th><th><span class="visually-hidden">Details</span></th></tr>
                        </thead>
                        <tbody>
                        @foreach ($crewRows as $row)
                            <tr>
                                <td><span class="fw-semibold">{{ $row['crew']->crew_code }}</span><div class="small text-secondary">{{ $row['crew']->user->name }}</div></td>
                                <td>{{ $row['active_alerts'] }}</td>
                                <td>{{ $row['latest_checkin']?->submitted_at?->format('M j, H:i') ?? 'No data' }}</td>
                                <td>{{ $row['latest_reading'] ? number_format((float) $row['latest_reading']->value, 1).' bpm' : 'No data' }}</td>
                                <td>
                                    <span class="oc-badge {{ $row['freshness'] === 'current' ? 'oc-badge-ok' : 'oc-badge-unknown' }}">
                                        {{ $row['freshness'] === 'current' ? 'Current' : ucfirst(str_replace('_', ' ', $row['freshness'])) }}
                                    </span>
                                </td>
                                <td><a href="{{ route('staff.crew.show', ['crewProfileId' => $row['crew']->id]) }}" class="btn btn-sm btn-outline-primary">Review</a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        <p class="small text-secondary mt-3">No medical conclusions are drawn from missing, stale, or alerting data. The prototype does not provide diagnosis or treatment instructions.</p>
    @endif
@endsection
