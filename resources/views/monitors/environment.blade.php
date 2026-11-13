@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1">
                        <i class="bi bi-exclamation-triangle" style="color: {{ $hazard->color }};"></i>
                        {{ $hazard->name }} Monitoring
                    </h1>
                    <small class="text-muted">{{ $hazard->description }}</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Latest Environmental Metrics -->
    @if($latestReading)
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <small class="text-muted d-block">Temperature</small>
                    <h5 class="mb-0">{{ $latestReading->temperature ?? 'N/A' }}°C</h5>
                    <small class="text-muted">{{ $latestReading->recorded_at?->format('M d H:i') ?? 'No data' }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <small class="text-muted d-block">Pressure</small>
                    <h5 class="mb-0">{{ $latestReading->pressure ?? 'N/A' }} kPa</h5>
                    <small class="text-muted">Current reading</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <small class="text-muted d-block">CO₂ Level</small>
                    <h5 class="mb-0">{{ $latestReading->co2_level ?? 'N/A' }} ppm</h5>
                    <small class="text-muted">Current reading</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <small class="text-muted d-block">Light Level</small>
                    <h5 class="mb-0">{{ $latestReading->light_level ?? 'N/A' }} lux</h5>
                    <small class="text-muted">Current reading</small>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Chart -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Environmental Conditions (30 readings)</h6>
                </div>
                <div class="card-body">
                    @if($readings->count() > 0)
                        <canvas id="environmentChart" height="80"></canvas>
                        <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
                        <script>
                            new Chart(document.getElementById('environmentChart'), {
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
                                        y: { beginAtZero: true }
                                    }
                                }
                            });
                        </script>
                    @else
                        <p class="text-muted mb-0">No environmental readings available yet.</p>
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
                        <strong>Hostile/Closed Environments</strong> is one of NASA's five hazards. Closed-loop life support, 
                        contamination risks, and extreme environmental conditions directly affect crew health and safety.
                    </p>
                    <p class="small mb-0">
                        <strong>Continuous environmental monitoring</strong> alerts crews to air quality, pressure, thermal, 
                        and microbial issues—enabling proactive intervention before they cascade into emergencies.
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
