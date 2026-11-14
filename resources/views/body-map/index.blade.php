@extends('layouts.app')

@section('title', 'Body Map')
@section('heading', 'Body Map')

@section('content')
<link rel="stylesheet" href="{{ asset('css/bodymap.css') }}">
{{-- Holographic scan styling. Fonts are optional: system fonts are the fallback if they cannot load. --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@500;600;700&family=Share+Tech+Mono&display=swap">
<link rel="stylesheet" href="{{ asset('css/body-map-hologram.css') }}?v={{ @filemtime(public_path('css/body-map-hologram.css')) }}">

<x-page-header eyebrow="Health" title="Body Map" />
<p class="text-secondary small mb-3">
    Select a body region to see its current status, what's affecting it in space, and what you can do now.
    This is decision support only &mdash; ORBIT and OrbitCare support the mission medical team and do not replace them.
</p>

@if (! $hasCrew)
    <div class="alert alert-info" role="status">
        <i class="bi bi-info-circle" aria-hidden="true"></i>
        The Body Map is available to crew members. Sign in with a crew account to see body-system status.
    </div>
@else
<div class="row g-3" data-bodymap-root data-api="{{ route('body-map.api.all') }}">
    <div class="col-lg-5">
        <div class="card bm-figure bm-modern bm-holo" data-view="front" data-bodymap-figure>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-2">
                    <div class="btn-group btn-group-sm" role="group" aria-label="Body view">
                        <input type="radio" class="btn-check" name="bm-view" id="bm-view-front" value="front" checked>
                        <label class="btn btn-outline-secondary" for="bm-view-front">Front</label>
                        <input type="radio" class="btn-check" name="bm-view" id="bm-view-back" value="back">
                        <label class="btn btn-outline-secondary" for="bm-view-back">Back</label>
                    </div>

                    <div class="btn-group btn-group-sm" role="group" aria-label="Layers">
                        @foreach (['skin' => 'Skin', 'muscle' => 'Muscle', 'skeleton' => 'Skeleton', 'organs' => 'Organs'] as $layer => $label)
                            <input type="checkbox" class="btn-check" id="bm-layer-{{ $layer }}" data-layer-toggle="{{ $layer }}" checked>
                            <label class="btn btn-outline-secondary" for="bm-layer-{{ $layer }}">{{ $label }}</label>
                        @endforeach
                    </div>

                    <button type="button" class="btn btn-sm btn-outline-secondary" data-action="reset-view">
                        <i class="bi bi-arrows-angle-contract" aria-hidden="true"></i> Reset view
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-action="toggle-style" aria-pressed="true">
                        <i class="bi bi-stars" aria-hidden="true"></i> Modern view
                    </button>
                </div>

                <div class="bm-stage">
                    @include('body-map.partials.hologram-hud')
                    @include('body-map.partials.figure')
                    <div class="bm-tooltip" role="tooltip" hidden data-bodymap-tooltip></div>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-2" aria-label="Status legend">
                    <x-region-status-badge level="nominal" />
                    <x-region-status-badge level="watch" />
                    <x-region-status-badge level="review" />
                    <x-region-status-badge level="urgent" />
                </div>

                <div class="d-flex flex-wrap gap-2 mt-2" data-bodymap-extras></div>
            </div>
        </div>

        <details class="card mt-3">
            <summary class="card-body py-2 fw-semibold">All body regions (list)</summary>
            <div class="list-group list-group-flush" data-bodymap-list>
                @foreach ($regions as $region)
                    <button type="button"
                            class="list-group-item list-group-item-action d-flex align-items-center justify-content-between"
                            data-region-trigger
                            data-slug="{{ $region['slug'] }}">
                        <span>
                            <span class="fw-semibold d-block">{{ $region['name'] }}</span>
                            <span class="text-secondary small text-capitalize">{{ str_replace('_', ' ', $region['body_system']) }}</span>
                        </span>
                        <span data-status-slot="{{ $region['slug'] }}">
                            <x-region-status-badge :level="$region['status_level']" />
                        </span>
                    </button>
                @endforeach
            </div>
        </details>
    </div>

    <div class="col-lg-7">
        <div class="card bm-panel" data-bodymap-panel>
            <div class="card-body" data-bodymap-panel-body aria-live="polite">
                <div class="text-center text-secondary py-5">
                    <i class="bi bi-hand-index-thumb fs-2 d-block mb-2" aria-hidden="true"></i>
                    Select a body region on the figure or from the list to see details.
                </div>
            </div>
        </div>
    </div>
</div>

<template id="bodymap-panel-template">
    <h3 class="h5 mb-1" data-f="name"></h3>
    <div class="d-flex align-items-center gap-2 mb-2">
        <span class="text-secondary small text-capitalize" data-f="body_system"></span>
        <span data-f="status_badge"></span>
    </div>
    <p class="text-secondary small" data-f="as_of"></p>

    <h4 class="h6 mt-3">What it does</h4>
    <p data-f="function_text"></p>

    <h4 class="h6 mt-3">Current vitals</h4>
    <ul class="list-unstyled mb-0" data-f="metrics"></ul>

    <h4 class="h6 mt-3">What's affecting this in space</h4>
    <ul class="list-unstyled mb-0" data-f="hazards"></ul>

    <h4 class="h6 mt-3">What you can do now</h4>
    <ol data-f="self_care_steps"></ol>

    <h4 class="h6 mt-3 text-danger">When to escalate</h4>
    <ul data-f="red_flags"></ul>
    <p class="small" data-f="escalate_to"></p>

    <div class="d-flex gap-2 mt-3">
        <button type="button" class="btn btn-sm btn-outline-primary" data-action="ask-orbit">
            <i class="bi bi-stars" aria-hidden="true"></i> Ask ORBIT about this
        </button>
    </div>
</template>

<script>window.__bodyMapRegions = @json($regions);</script>
<script src="{{ asset('js/bodymap.js') }}" defer></script>
<script src="{{ asset('js/bodymap-hologram.js') }}?v={{ @filemtime(public_path('js/bodymap-hologram.js')) }}" defer></script>
@endif
@endsection
