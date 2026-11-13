@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1">
                        <i class="bi bi-arrow-down-circle" style="color: {{ $hazard->color }};"></i>
                        {{ $hazard->name }} Monitoring
                    </h1>
                    <small class="text-muted">{{ $hazard->description }}</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Latest Physical Metrics -->
    @if($latestReading)
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <small class="text-muted d-block">Exercise Today</small>
                    <h5 class="mb-0">{{ $latestReading->exercise_minutes ?? 'N/A' }} min</h5>
                    <small class="text-muted">Countermeasure time</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <small class="text-muted d-block">Muscle Index</small>
                    <h5 class="mb-0">{{ $latestReading->muscle_index ?? 'N/A' }}</h5>
                    <small class="text-muted">{{ $latestReading->recorded_at?->format('M d') ?? 'No data' }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <small class="text-muted d-block">Bone Index</small>
                    <h5 class="mb-0">{{ $latestReading->bone_index ?? 'N/A' }}</h5>
                    <small class="text-muted">{{ $latestReading->recorded_at?->format('M d') ?? 'No data' }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <small class="text-muted d-block">Balance Score</small>
                    <h5 class="mb-0">{{ $latestReading->balance_score ?? 'N/A' }}/10</h5>
                    <small class="text-muted">Vestibular function</small>
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
                    <h6 class="mb-0">Physical Health Indicators (30 days)</h6>
                </div>
                <div class="card-body">
                    @if($readings->count() > 0)
                        <canvas id="physicalChart" height="80"></canvas>
                        <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
                        <script>
                            new Chart(document.getElementById('physicalChart'), {
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
                        <p class="text-muted mb-0">No physical readings available yet.</p>
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
                        <strong>Gravity Alterations</strong> is one of NASA's five hazards. Microgravity causes bone loss (~1% per month), 
                        muscle atrophy, cardiovascular deconditioning, and vestibular dysfunction.
                    </p>
                    <p class="small mb-0">
                        <strong>Countermeasures</strong> (exercise) and regular monitoring help crews detect and slow physiological changes. 
                        This data informs return-to-gravity preparation and post-mission recovery planning.
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
