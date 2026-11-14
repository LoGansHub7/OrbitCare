@extends('layouts.app')
@section('title', 'Evidence library')
@section('heading', 'Evidence library')
@section('content')
    <div class="alert alert-info" role="note">Every entry links to the original NASA or CSA publication. Sources give context for why a hazard matters; they do not validate OrbitCare's experimental thresholds.</div>

    <form method="GET" class="d-flex flex-wrap gap-2 mb-3" role="search">
        <label class="visually-hidden" for="ev-hazard">Hazard</label>
        <select id="ev-hazard" name="hazard" class="form-select w-auto">
            <option value="">All hazards</option>
            @foreach ($hazards as $hazard)
                <option value="{{ $hazard->slug }}" @selected($hazardSlug === $hazard->slug)>{{ $hazard->name }}</option>
            @endforeach
        </select>
        <label class="visually-hidden" for="ev-q">Search evidence</label>
        <input id="ev-q" name="q" value="{{ $search }}" class="form-control w-auto" placeholder="Search title, publisher, summary">
        <button class="btn btn-primary">Filter</button>
        <a href="{{ route('evidence.index') }}" class="btn btn-outline-secondary">Clear</a>
    </form>

    <div class="oc-card">
        @forelse ($references as $reference)
            <article class="border-bottom py-3">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <a class="fw-semibold" href="{{ $reference->url }}" target="_blank" rel="noopener noreferrer">{{ $reference->title }}</a>
                    @if ($reference->hazard)<span class="badge text-bg-light">{{ $reference->hazard->name }}</span>@endif
                </div>
                <div class="small text-secondary">{{ $reference->publisher }}@if ($reference->published_at) · {{ $reference->published_at->format('Y') }}@endif</div>
                @if ($reference->summary)<p class="mb-0 mt-1">{{ $reference->summary }}</p>@endif
            </article>
        @empty
            <x-empty-state title="No references match" message="Try clearing the filter." />
        @endforelse
    </div>
@endsection
