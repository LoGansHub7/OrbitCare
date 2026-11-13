@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Mission Profile Selector -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Mission Profile</h6>
                    @if($mission)
                        <div class="alert alert-info mb-0">
                            <small>
                                <strong>{{ $mission->name }}</strong> • {{ $mission->mission_profile }}
                                @if($mission->mission_profile === 'Mars')
                                    <span class="badge bg-danger">High Distance Risk</span>
                                @elseif($mission->mission_profile === 'Moon')
                                    <span class="badge bg-warning text-dark">Moderate Distance Risk</span>
                                @else
                                    <span class="badge bg-success">Low Distance Risk</span>
                                @endif
                            </small>
                        </div>
                    @else
                        <small class="text-muted">No active mission</small>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Crew Member</h6>
                    @if($crewProfile)
                        <small>
                            <strong>{{ $crewProfile->name }}</strong><br>
                            Crew Code: <code>{{ $crewProfile->crew_code }}</code>
                        </small>
                    @else
                        <small class="text-muted">Not assigned to a crew</small>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Hazard Cards -->
    <div class="row">
        @foreach($hazards as $hazard)
            @php
                $status = $hazardStatus[$hazard->slug] ?? null;
            @endphp
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 border-start" style="border-left-color: {{ $hazard->color }} !important; border-left-width: 4px;">
                    <div class="card-body">
                        <!-- Hazard Icon & Name -->
                        <div class="d-flex align-items-start mb-3">
                            <div class="flex-grow-1">
                                <h5 class="card-title mb-1">
                                    <i class="bi {{ $hazard->icon }}"></i> {{ $hazard->name }}
                                </h5>
                                <small class="text-muted">{{ $hazard->description }}</small>
                            </div>
                        </div>

                        <!-- Status Badge -->
                        @if($status)
                            <div class="mb-3">
                                @php
                                    $statusColors = [
                                        'no_flag' => ['bg' => '#15803D', 'label' => 'Nominal'],
                                        'review' => ['bg' => '#B45309', 'label' => 'Watch'],
                                        'priority_review' => ['bg' => '#B91C1C', 'label' => 'Alert'],
                                        'insufficient_data' => ['bg' => '#64748B', 'label' => 'Monitoring'],
                                    ];
                                    $statusInfo = $statusColors[$status['status']] ?? $statusColors['insufficient_data'];
                                @endphp
                                <span class="badge" style="background-color: {{ $statusInfo['bg'] }};">
                                    {{ $statusInfo['label'] }}
                                </span>
                                @if($status['alert_count'] > 0)
                                    <span class="badge bg-danger ms-1">
                                        {{ $status['alert_count'] }} alert{{ $status['alert_count'] !== 1 ? 's' : '' }}
                                    </span>
                                @endif
                            </div>
                        @endif

                        <!-- Mission Weighting -->
                        @if($mission && isset($status['weight']))
                            <div class="mb-3">
                                <small class="d-block text-muted">
                                    Mission weight: <strong>{{ number_format($status['weight'], 1) }}x</strong>
                                </small>
                                <div class="progress" style="height: 6px;">
                                    @php
                                        $weightPercent = min(($status['weight'] / 2) * 100, 100);
                                    @endphp
                                    <div class="progress-bar" role="progressbar" style="width: {{ $weightPercent }}%; background-color: {{ $hazard->color }};" aria-valuenow="{{ $weightPercent }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        @endif

                        <!-- Linked Risks Count -->
                        <div class="mb-3">
                            <small class="text-muted">
                                <i class="bi bi-exclamation-circle"></i> {{ $hazard->risks->count() }} identified risks
                            </small>
                        </div>

                        <!-- Link to Hazard Details -->
                        <a href="{{ route('hazards.show', $hazard->slug) }}" class="btn btn-sm btn-outline-primary w-100">
                            View Details
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Hazard Interaction Panel (TODO) -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Hazard Interactions</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-0">
                        <i class="bi bi-info-circle"></i>
                        <small>Hazard interaction visualization — how multiple hazards affect crew health together — coming in Phase B.</small>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- About Hazards -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">About NASA's Five Hazards</h6>
                </div>
                <div class="card-body">
                    <p class="small mb-0">
                        OrbitCare is structured around <strong>NASA's five hazards of human spaceflight</strong>. Each hazard has associated risks, monitoring indicators, 
                        and decision support. This framework helps crews understand how multiple factors interact to affect their health over long-duration missions.
                    </p>
                    <p class="small mt-2 mb-0">
                        <a href="{{ route('research.index') }}" class="text-decoration-none">
                            <i class="bi bi-book"></i> View research references
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .card {
        transition: box-shadow 0.3s ease;
    }
    .card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
</style>
@endsection
