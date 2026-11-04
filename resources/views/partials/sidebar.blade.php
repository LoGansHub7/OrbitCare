<div class="oc-brand"><x-orbit-logo /> <span>OrbitCare</span></div>
<nav class="oc-nav" aria-label="Main navigation">
    @if (auth()->user()->isAstronaut())
        <div class="small text-uppercase text-white-50 px-2 mt-2">Overview</div>
        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}"><i class="bi bi-speedometer2" aria-hidden="true"></i> Dashboard</a>
        <a href="{{ route('alerts.index') }}" class="{{ request()->routeIs('alerts.*') ? 'active' : '' }}"><i class="bi bi-bell" aria-hidden="true"></i> Alerts</a>
        <div class="small text-uppercase text-white-50 px-2 mt-3">Health</div>
        <a href="{{ route('checkin.create') }}" class="{{ request()->routeIs('checkin.*') ? 'active' : '' }}"><i class="bi bi-clipboard2-pulse" aria-hidden="true"></i> Daily check-in</a>
        <a href="{{ route('readings.index') }}" class="{{ request()->routeIs('readings.*') ? 'active' : '' }}"><i class="bi bi-heart-pulse" aria-hidden="true"></i> Readings</a>
        <div class="small text-uppercase text-white-50 px-2 mt-3">Reports</div>
        <a href="{{ route('reports.self') }}" class="{{ request()->routeIs('reports.*') ? 'active' : '' }}"><i class="bi bi-file-earmark-text" aria-hidden="true"></i> Reports</a>
    @else
        <a href="{{ route('staff.home') }}" class="{{ request()->routeIs('staff.*') ? 'active' : '' }}"><i class="bi bi-people" aria-hidden="true"></i> Overview</a>
    @endif
    <div class="small text-uppercase text-white-50 px-2 mt-3">Mission</div>
    <a href="{{ route('research.index') }}" class="{{ request()->routeIs('research.*') ? 'active' : '' }}"><i class="bi bi-journal-text" aria-hidden="true"></i> Research & limits</a>
</nav>
<div class="oc-sidebar-foot">
    <div class="text-white fw-semibold">{{ auth()->user()->name }}</div>
    <div class="mb-3 text-capitalize">{{ str_replace('_', ' ', auth()->user()->role) }}</div>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="btn btn-outline-light btn-sm w-100"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Sign out</button>
    </form>
</div>
