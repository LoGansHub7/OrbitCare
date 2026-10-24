@extends('layouts.app')
@section('title', 'Readings')
@section('heading', 'Resting heart rate readings')
@section('content')
<div class="row g-3">
    <div class="col-12 col-lg-4">
        <div class="oc-card">
            <div class="oc-card-title">Add a reading</div>
            <form method="POST" action="{{ route('readings.store') }}" novalidate>
                @csrf
                <div class="mb-3">
                    <label for="value" class="form-label">Resting heart rate ({{ $metric->unit }})</label>
                    <input id="value" name="value" type="number" step="0.1" inputmode="decimal" value="{{ old('value') }}"
                           class="form-control @error('value') is-invalid @enderror" required>
                    <div class="form-text">Accepted range {{ (int) $metric->min_value }}–{{ (int) $metric->max_value }} (data-entry check only).</div>
                    @error('value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label for="observed_at" class="form-label">Measured at (UTC)</label>
                    <input id="observed_at" name="observed_at" type="datetime-local" value="{{ old('observed_at', now()->format('Y-m-d\TH:i')) }}"
                           class="form-control @error('observed_at') is-invalid @enderror" required>
                    @error('observed_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <button class="btn btn-primary w-100" type="submit">Save reading</button>
            </form>
        </div>
    </div>
    <div class="col-12 col-lg-8">
        <div class="oc-card">
            <div class="oc-card-title">History</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Measured (UTC)</th><th>Value</th><th>Source</th></tr></thead>
                    <tbody>
                    @forelse ($readings as $r)
                        <tr>
                            <td>{{ $r->observed_at->format('M j, Y H:i') }}</td>
                            <td>{{ number_format((float) $r->value, 1) }} {{ $metric->unit }}</td>
                            <td><x-source-label :source="$r->source" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-secondary">No readings yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $readings->links() }}</div>
        </div>
    </div>
</div>
@endsection
