<div class="oc-brand"><i class="bi bi-globe-americas" aria-hidden="true"></i> OrbitCare</div>
<nav class="oc-nav" aria-label="Main navigation">
    @if (auth()->user()->isAstronaut())
        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}"><i class="bi bi-speedometer2" aria-hidden="true"></i> Dashboard</a>
        <a href="{{ route('checkin.create') }}" class="{{ request()->routeIs('checkin.*') ? 'active' : '' }}"><i class="bi bi-clipboard2-pulse" aria-hidden="true"></i> Daily check-in</a>
        <a href="{{ route('readings.index') }}" class="{{ request()->routeIs('readings.*') ? 'active' : '' }}"><i class="bi bi-heart-pulse" aria-hidden="true"></i> Readings</a>
    @else
        <a href="{{ route('staff.home') }}" class="{{ request()->routeIs('staff.*') ? 'active' : '' }}"><i class="bi bi-people" aria-hidden="true"></i> Overview</a>
    @endif
</nav>
<div class="oc-sidebar-foot">
    <div class="text-white fw-semibold">{{ auth()->user()->name }}</div>
    <div class="mb-3 text-capitalize">{{ str_replace('_', ' ', auth()->user()->role) }}</div>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="btn btn-outline-light btn-sm w-100"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Sign out</button>
    </form>
</div>
