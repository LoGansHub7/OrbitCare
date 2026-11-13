@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1">
                        <i class="bi bi-radiation" style="color: {{ $hazard->color }};"></i>
                        {{ $hazard->name }} Monitoring
                    </h1>
                    <small class="text-muted">{{ $hazard->description }}</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Latest Reading Cards -->
    @if($latestReading)
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <small class="text-muted d-block">Dose Rate</small>
                    <h5 class="mb-0">{{ $latestReading->dose_rate ?? 'N/A' }} mSv/day</h5>
                    <small class="text-muted">{{ $latestReading->recorded_at?->format('M d, H:i') ?? 'No data' }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <small class="text-muted d-block">Neutron Dose</small>
                    <h5 class="mb-0">{{ $latestReading->neutron_dose ?? 'N/A' }} mSv</h5>
                    <small class="text-muted">Current reading</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <small class="text-muted d-block">Other Dose</small>
                    <h5 class="mb-0">{{ $latestReading->other_dose ?? 'N/A' }} mSv</h5>
                    <small class="text-muted">Current reading</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <small class="text-muted d-block">Cumulative</small>
                    <h5 class="mb-0">{{ $latestReading->cumulative_dose ?? 'N/A' }} mSv</h5>
                    <small class="text-muted">Total mission dose</small>
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
                    <h6 class="mb-0">Radiation Dose Trend (30 days)</h6>
                </div>
                <div class="card-body">
                    @if($readings->count() > 0)
                        <canvas id="radiationChart" height="80"></canvas>
                        <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
                        <script>
                            new Chart(document.getElementById('radiationChart'), {
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
                        <p class="text-muted mb-0">No radiation readings available yet.</p>
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
                        <strong>Space Radiation Exposure</strong> is one of NASA's five hazards of human spaceflight. 
                        Galactic cosmic rays and solar particle events increase cancer risk and can affect the central nervous system.
                    </p>
                    <p class="small mb-0">
                        <strong>Continuous measurement</strong> (like the CSA CANS instrument) allows crews to track cumulative exposure 
                        and optimize shielding strategies during long missions.
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
