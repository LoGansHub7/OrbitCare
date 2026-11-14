@extends('layouts.app')
@section('title', 'Risk register')
@section('heading', 'Risk register')
@section('content')
    <div class="alert alert-info" role="note">Risks are organised under NASA's five hazards of human spaceflight. Names follow NASA Human Research Program risk titles where a source is linked; this register is a reading aid, not a clinical risk assessment of any crew member.</div>

    <form method="GET" class="d-flex flex-wrap gap-2 mb-3" role="search">
        <label class="visually-hidden" for="risk-hazard">Hazard</label>
        <select id="risk-hazard" name="hazard" class="form-select w-auto">
            <option value="">All hazards</option>
            @foreach ($hazards as $hazard)
                <option value="{{ $hazard->slug }}" @selected($hazardSlug === $hazard->slug)>{{ $hazard->name }}</option>
            @endforeach
        </select>
        <label class="visually-hidden" for="risk-q">Search risks</label>
        <input id="risk-q" name="q" value="{{ $search }}" class="form-control w-auto" placeholder="Search risks">
        <button class="btn btn-primary">Filter</button>
        <a href="{{ route('risks.index') }}" class="btn btn-outline-secondary">Clear</a>
    </form>

    @forelse ($groups as $hazardName => $risks)
        <section class="oc-card mb-3" aria-labelledby="hz-{{ \Illuminate\Support\Str::slug($hazardName) }}">
            <h2 id="hz-{{ \Illuminate\Support\Str::slug($hazardName) }}" class="oc-card-title">{{ $hazardName }} <span class="text-secondary small">({{ $risks->count() }})</span></h2>
            <ul class="list-unstyled mb-0">
                @foreach ($risks as $risk)
                    <li class="border-bottom py-2">
                        <div class="fw-semibold">{{ $risk->name }}</div>
                        @if ($risk->summary)<div class="small text-secondary">{{ $risk->summary }}</div>@endif
                        @if ($risk->source_url)<a class="small" href="{{ $risk->source_url }}" target="_blank" rel="noopener noreferrer">NASA source</a>@endif
                    </li>
                @endforeach
            </ul>
        </section>
    @empty
        <x-empty-state title="No risks match" message="Try clearing the filter. If the register is empty, run the hazard seeders." />
    @endforelse
@endsection
