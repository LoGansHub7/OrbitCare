@extends('layouts.app')
@section('title', 'Overview')
@section('heading', 'Crew overview')
@section('content')
    @if ($isAdmin)
        <section class="oc-admin-hero mb-4" aria-labelledby="admin-overview-title">
            <div class="oc-admin-hero-copy">
                <span class="oc-admin-kicker"><i class="bi bi-broadcast-pin" aria-hidden="true"></i> MISSION CONTROL</span>
                <h2 id="admin-overview-title">Keep the mission<br>running smoothly.</h2>
                <p>Manage access, shape the demo mission, and explore the evidence behind OrbitCare.</p>
                <a class="btn btn-light" href="{{ route('admin.index') }}"><i class="bi bi-sliders" aria-hidden="true"></i> Open administration</a>
            </div>
            <div class="oc-orbit-art" aria-hidden="true">
                <span class="oc-orbit oc-orbit-one"></span><span class="oc-orbit oc-orbit-two"></span>
                <span class="oc-orbit oc-orbit-three"></span><span class="oc-orbit-planet"><i class="bi bi-globe-americas"></i></span>
                <span class="oc-orbit-satellite"><i class="bi bi-satellite"></i></span>
            </div>
        </section>

        <div class="row g-3">
            <div class="col-12 col-md-6 col-xl-4">
                <a class="oc-admin-action" href="{{ route('admin.index') }}">
                    <span class="oc-admin-action-icon"><i class="bi bi-people" aria-hidden="true"></i></span>
                    <span class="oc-admin-action-title">Accounts & mission links</span>
                    <span class="oc-admin-action-copy">Manage roles, account access, and simulated Earth-link status.</span>
                    <span class="oc-admin-action-cta">Manage settings <i class="bi bi-arrow-right" aria-hidden="true"></i></span>
                </a>
            </div>
            <div class="col-12 col-md-6 col-xl-4">
                <a class="oc-admin-action" href="{{ route('admin.demo') }}">
                    <span class="oc-admin-action-icon"><i class="bi bi-play-circle" aria-hidden="true"></i></span>
                    <span class="oc-admin-action-title">Demo mission control</span>
                    <span class="oc-admin-action-copy">Set the mission day or load a fictional scenario for a walkthrough.</span>
                    <span class="oc-admin-action-cta">Configure demo <i class="bi bi-arrow-right" aria-hidden="true"></i></span>
                </a>
            </div>
            <div class="col-12 col-md-6 col-xl-4">
                <a class="oc-admin-action" href="{{ route('research.index') }}">
                    <span class="oc-admin-action-icon"><i class="bi bi-journal-text" aria-hidden="true"></i></span>
                    <span class="oc-admin-action-title">Research & limits</span>
                    <span class="oc-admin-action-copy">Review source notes and the boundaries of this experimental prototype.</span>
                    <span class="oc-admin-action-cta">Explore evidence <i class="bi bi-arrow-right" aria-hidden="true"></i></span>
                </a>
            </div>
        </div>
        <div class="oc-admin-privacy mt-3"><i class="bi bi-shield-lock" aria-hidden="true"></i><span><strong>Privacy stays crew centered.</strong> Administrator access covers system settings and does not grant access to individual health records.</span></div>
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
