@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1">
                        <i class="bi bi-person-lock" style="color: {{ $hazard->color }};"></i>
                        {{ $hazard->name }} Monitoring
                    </h1>
                    <small class="text-muted">{{ $hazard->description }}</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Latest Checkin Summary -->
    @if($latestCheckin)
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <small class="text-muted d-block">Sleep Quality</small>
                    <h5 class="mb-0">{{ $latestCheckin->sleep_quality ?? 'N/A' }}/5</h5>
                    <small class="text-muted">{{ $latestCheckin->recorded_at?->format('M d') ?? 'No data' }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <small class="text-muted d-block">Mood</small>
                    <h5 class="mb-0">{{ $latestCheckin->mood ?? 'N/A' }}/5</h5>
                    <small class="text-muted">Current state</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <small class="text-muted d-block">Stress Level</small>
                    <h5 class="mb-0">{{ $latestCheckin->stress ?? 'N/A' }}/5</h5>
                    <small class="text-muted">Current state</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <small class="text-muted d-block">Sleep Hours</small>
                    <h5 class="mb-0">{{ $latestCheckin->sleep_hours ?? 'N/A' }}h</h5>
                    <small class="text-muted">Last night</small>
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
                    <h6 class="mb-0">Wellbeing Indicators (30 days)</h6>
                </div>
                <div class="card-body">
                    @if($checkins->count() > 0)
                        <canvas id="wellbeingChart" height="80"></canvas>
                        <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
                        <script>
                            new Chart(document.getElementById('wellbeingChart'), {
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
                                        y: { min: 1, max: 5, ticks: { stepSize: 1 } }
                                    }
                                }
                            });
                        </script>
                    @else
                        <p class="text-muted mb-0">No wellbeing checkins available yet.</p>
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
                        <strong>Isolation & Confinement</strong> is one of NASA's five hazards. Prolonged separation from Earth and living in confined environments 
                        affects psychological well-being, sleep quality, and crew dynamics.
                    </p>
                    <p class="small mb-0">
                        Regular <strong>wellbeing checkins</strong> help crews self-monitor mood, stress, and sleep patterns—early signals for intervention before small issues compound.
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
