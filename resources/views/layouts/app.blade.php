<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'OrbitCare') · OrbitCare</title>
    @vite(['resources/js/app.js'])
</head>
<body>
@php $navCrew = auth()->user()->isAstronaut() ? auth()->user()->crewProfile?->load('mission') : null; $navMission = $navCrew?->mission ?? \App\Models\Mission::query()->orderByDesc('starts_on')->first(); @endphp
<div class="oc-shell">
    <aside class="oc-sidebar oc-sidebar-fixed d-none d-lg-flex flex-column">
        @include('partials.sidebar')
    </aside>

    <div class="offcanvas offcanvas-start oc-sidebar" tabindex="-1" id="mobileNav" aria-label="Navigation menu">
        <div class="d-flex flex-column h-100">
            @include('partials.sidebar')
        </div>
    </div>

    <div class="oc-main">
        <header class="oc-topbar">
            <button class="btn btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNav" aria-label="Open menu">
                <i class="bi bi-list" aria-hidden="true"></i>
            </button>
            <div>
                <h1 class="oc-page-title">@yield('heading')</h1>
                @if ($navCrew)<div class="small text-secondary d-none d-md-block">Mission day {{ $navCrew->missionDay() }} of 180 <span class="oc-mission-progress d-inline-block ms-2"><span></span></span></div>@endif
            </div>
            @if ($navCrew)
                <div class="ms-auto text-end small text-secondary d-none d-sm-block">
                    <div class="fw-semibold">{{ $navCrew->crew_code }}</div>
                    <div>{{ $navCrew->mission->name }}</div>
                </div>
            @endif
            @if ($navMission)
                <span class="oc-link-chip d-none d-sm-inline">
                    <i class="bi bi-broadcast" aria-hidden="true"></i> Earth link {{ $navMission->earth_link_status }}
                    @if ($navMission->earth_link_status === 'delayed')
                        · {{ $navMission->earth_link_delay_minutes }}m
                    @endif
                </span>
            @endif
            <button type="button" class="btn btn-outline-secondary btn-sm" data-theme-toggle></button>
        </header>

        <main class="oc-content">
            @if (session('status'))
                <div class="alert alert-success" role="status"><i class="bi bi-check-circle" aria-hidden="true"></i> {{ session('status') }}</div>
            @endif
            @yield('content')
        </main>
    </div>
</div>
@stack('scripts')
</body>
</html>
