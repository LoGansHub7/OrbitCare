@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1">
                        <i class="bi bi-globe" style="color: {{ $hazard->color }};"></i>
                        {{ $hazard->name }} Monitoring
                    </h1>
                    <small class="text-muted">{{ $hazard->description }}</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Autonomy Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <small class="text-muted d-block">Events Handled Onboard</small>
                    <h5 class="mb-0">{{ $handledOnboard }}</h5>
                    <small class="text-muted">Crew resolved independently</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <small class="text-muted d-block">Escalated to Earth</small>
                    <h5 class="mb-0">{{ $escalatedToEarth }}</h5>
                    <small class="text-muted">Required Earth guidance</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <small class="text-muted d-block">Current Comms Delay</small>
                    <h5 class="mb-0">
                        @if($latestEvent)
                            {{ gmdate('H:i:s', $latestEvent->comms_delay_seconds) }}
                        @else
                            N/A
                        @endif
                    </h5>
                    <small class="text-muted">One-way to Earth</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Communications Delay Timeline (30 events)</h6>
                </div>
                <div class="card-body">
                    @if($events->count() > 0)
                        <canvas id="autonomyChart" height="80"></canvas>
                        <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
                        <script>
                            new Chart(document.getElementById('autonomyChart'), {
                                type: 'line',
                                data: {
                                    labels: @json($chartData['labels']),
                                    datasets: @json($chartData['datasets'])
                                },
                                options: {
                                    responsive: true,
                                    plugins: {
                                        legend: { display: true }
                                    },
                                    scales: {
                                        y: { beginAtZero: true, ticks: { callback: function(val) { return gmdate('H:i:s', val); } } }
                                    }
                                }
                            });
                        </script>
                    @else
                        <p class="text-muted mb-0">No autonomy events available yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Why This Matters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Why This Matters</h6>
                </div>
                <div class="card-body">
                    <p class="small mb-2">
                        <strong>Distance from Earth</strong> is one of NASA's five hazards. Extended communication delays (up to 22 minutes one-way to Mars) 
                        and inability to rapidly return home require crew <strong>autonomy and self-reliance</strong>.
                    </p>
                    <p class="small mb-0">
                        Tracking how many decisions crews make independently vs. escalate to Earth reveals crew confidence and readiness, 
                        and validates that OrbitCare's decision-support system works.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- References -->
    @if($hazard->references->count() > 0)
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Research References</h6>
                </div>
                <div class="card-body">
                    <ul class="small mb-0">
                        @foreach($hazard->references as $ref)
                            <li class="mb-2">
                                <strong>{{ $ref->title }}</strong> ({{ $ref->publisher }})
                                @if($ref->url)
                                    <a href="{{ $ref->url }}" target="_blank" rel="noopener" class="text-decoration-none">
                                        <i class="bi bi-box-arrow-up-right"></i>
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
