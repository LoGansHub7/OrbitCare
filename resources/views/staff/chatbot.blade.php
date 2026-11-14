@extends('layouts.app')
@section('title', 'Assistant activity')
@section('heading', 'ORBIT assistant activity')
@section('content')
    @if ($isAdmin)
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <p class="text-secondary mb-0">Totals across all crew from the last {{ $windowDays }} days. Names, per-person records and message text are not shown here.</p>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-6 col-lg-3"><section class="oc-card h-100"><div class="small text-secondary">Crew with assistant access</div><div class="fs-3 fw-semibold">{{ $aggregate['crew_count'] }}</div></section></div>
            <div class="col-6 col-lg-3"><section class="oc-card h-100"><div class="small text-secondary">Questions asked</div><div class="fs-3 fw-semibold">{{ $aggregate['questions'] }}</div></section></div>
            <div class="col-6 col-lg-3"><section class="oc-card h-100"><div class="small text-secondary">Emergency prompts</div><div class="fs-3 fw-semibold">{{ $aggregate['emergencies'] }}</div></section></div>
            <div class="col-12"><section class="oc-card">
                <div class="oc-card-title">Top topics</div>
                @forelse ($aggregate['topics'] as $topic)
                    <span class="oc-badge oc-badge-unknown me-1 mb-1">{{ $topic['label'] }} · {{ $topic['count'] }}</span>
                @empty
                    <span class="text-secondary small">No topics yet</span>
                @endforelse
            </section></div>
        </div>
        <p class="small text-secondary">Emergency prompts show that the assistant returned emergency guidance. They do not confirm a medical event.</p>
    @else
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <p class="text-secondary mb-0">Assigned crew only. Topic counts and emergency prompts from the last {{ $windowDays }} days. Message text is not shown here.</p>
        </div>

        <div class="oc-card">
            @if ($rows->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-chat-square-text fs-2 text-secondary" aria-hidden="true"></i>
                    <h2 class="h5 mt-3">No assigned crew</h2>
                    <p class="text-secondary mb-0">Assistant activity appears here for assigned crew members.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                        <tr><th>Crew member</th><th>Questions asked</th><th>Top topics</th><th>Emergency prompts</th><th>Last emergency prompt</th></tr>
                        </thead>
                        <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td><span class="fw-semibold">{{ $row['crew']->crew_code }}</span><div class="small text-secondary">{{ $row['crew']->user->name }}</div></td>
                                <td>{{ $row['questions'] }}</td>
                                <td>
                                    @forelse ($row['topics'] as $topic)
                                        <span class="oc-badge oc-badge-unknown me-1 mb-1">{{ $topic['label'] }} · {{ $topic['count'] }}</span>
                                    @empty
                                        <span class="text-secondary small">No topics yet</span>
                                    @endforelse
                                </td>
                                <td>
                                    @if ($row['emergency_count'] > 0)
                                        <span class="oc-badge oc-badge-priority-review">{{ $row['emergency_count'] }}</span>
                                    @else
                                        <span class="text-secondary">0</span>
                                    @endif
                                </td>
                                <td>{{ $row['last_emergency_at'] ? \Illuminate\Support\Carbon::parse($row['last_emergency_at'])->format('M j, H:i') : 'None' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        <p class="small text-secondary mt-3">Emergency prompts show that the assistant returned emergency guidance. They do not confirm a medical event. Follow the crew escalation procedure.</p>
    @endif
@endsection
