<div class="oc-brand"><x-orbit-logo /> <span>OrbitCare</span></div>
<nav class="oc-nav" aria-label="Main navigation">
    @if (auth()->user()->isAstronaut())
        <div class="small text-uppercase text-white-50 px-2 mt-2">Overview</div>
        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}"><i class="bi bi-speedometer2" aria-hidden="true"></i> Dashboard</a>
        <a href="{{ route('assistant.show') }}" class="{{ request()->routeIs('assistant.*') ? 'active' : '' }}"><i class="bi bi-stars" aria-hidden="true"></i> ORBIT assistant</a>
        <a href="{{ route('alerts.index') }}" class="{{ request()->routeIs('alerts.*') ? 'active' : '' }}"><i class="bi bi-bell" aria-hidden="true"></i> Alerts</a>
        <div class="small text-uppercase text-white-50 px-2 mt-3">Hazards</div>
        <a href="{{ route('hazards.index') }}" class="{{ request()->routeIs('hazards.*') ? 'active' : '' }}"><i class="bi bi-globe2" aria-hidden="true"></i> Five hazards</a>
        <a href="{{ route('monitor.radiation') }}" class="{{ request()->routeIs('monitor.radiation') ? 'active' : '' }}"><i class="bi bi-radioactive" aria-hidden="true"></i> Radiation</a>
        <a href="{{ route('monitor.wellbeing') }}" class="{{ request()->routeIs('monitor.wellbeing') ? 'active' : '' }}"><i class="bi bi-emoji-neutral" aria-hidden="true"></i> Isolation &amp; wellbeing</a>
        <a href="{{ route('monitor.autonomy') }}" class="{{ request()->routeIs('monitor.autonomy') ? 'active' : '' }}"><i class="bi bi-broadcast" aria-hidden="true"></i> Distance &amp; autonomy</a>
        <a href="{{ route('monitor.physical') }}" class="{{ request()->routeIs('monitor.physical') ? 'active' : '' }}"><i class="bi bi-person-arms-up" aria-hidden="true"></i> Gravity &amp; physical</a>
        <a href="{{ route('monitor.environment') }}" class="{{ request()->routeIs('monitor.environment') ? 'active' : '' }}"><i class="bi bi-thermometer-half" aria-hidden="true"></i> Environment</a>
        <div class="small text-uppercase text-white-50 px-2 mt-3">Health</div>
        <a href="{{ route('checkin.create') }}" class="{{ request()->routeIs('checkin.*') ? 'active' : '' }}"><i class="bi bi-clipboard2-pulse" aria-hidden="true"></i> Daily check-in</a>
        <a href="{{ route('readings.index') }}" class="{{ request()->routeIs('readings.*') ? 'active' : '' }}"><i class="bi bi-heart-pulse" aria-hidden="true"></i> Readings</a>
        <div class="small text-uppercase text-white-50 px-2 mt-3">Reports</div>
        <a href="{{ route('reports.self') }}" class="{{ request()->routeIs('reports.*') ? 'active' : '' }}"><i class="bi bi-file-earmark-text" aria-hidden="true"></i> Reports</a>
        <a href="{{ route('hazard-report.self') }}" class="{{ request()->routeIs('hazard-report.*') ? 'active' : '' }}"><i class="bi bi-file-earmark-bar-graph" aria-hidden="true"></i> Hazard report</a>
    @else
        <a href="{{ route('staff.home') }}" class="{{ request()->routeIs('staff.home', 'staff.crew.*') ? 'active' : '' }}"><i class="bi bi-people" aria-hidden="true"></i> Overview</a>
        <a href="{{ route('staff.chatbot') }}" class="{{ request()->routeIs('staff.chatbot') ? 'active' : '' }}"><i class="bi bi-chat-square-text" aria-hidden="true"></i> Assistant activity</a>
        @if(auth()->user()->isAdmin())<a href="{{ route('admin.index') }}" class="{{ request()->routeIs('admin.index') ? 'active' : '' }}"><i class="bi bi-sliders" aria-hidden="true"></i> Administration</a><a href="{{ route('admin.demo') }}" class="{{ request()->routeIs('admin.demo*') ? 'active' : '' }}"><i class="bi bi-play-circle" aria-hidden="true"></i> Demo mission control</a>@endif
    @endif
    <div class="small text-uppercase text-white-50 px-2 mt-3">Mission</div>
    <a href="{{ route('research.index') }}" class="{{ request()->routeIs('research.*') ? 'active' : '' }}"><i class="bi bi-journal-text" aria-hidden="true"></i> Research & limits</a>
    <a href="{{ route('risks.index') }}" class="{{ request()->routeIs('risks.*') ? 'active' : '' }}"><i class="bi bi-shield-exclamation" aria-hidden="true"></i> Risk register</a>
    <a href="{{ route('evidence.index') }}" class="{{ request()->routeIs('evidence.*') ? 'active' : '' }}"><i class="bi bi-book" aria-hidden="true"></i> Evidence library</a>
    <a href="{{ route('alerts.catalog') }}" class="{{ request()->routeIs('alerts.catalog') ? 'active' : '' }}"><i class="bi bi-info-circle" aria-hidden="true"></i> How alerts work</a>
    <a href="{{ route('help.index') }}" class="{{ request()->routeIs('help.*') ? 'active' : '' }}"><i class="bi bi-question-circle" aria-hidden="true"></i> Help & glossary</a>
</nav>
<div class="oc-sidebar-foot">
    <div class="text-white fw-semibold">{{ auth()->user()->name }}</div>
    <div class="mb-3 text-capitalize">{{ str_replace('_', ' ', auth()->user()->role) }}</div>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="btn btn-outline-light btn-sm w-100"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Sign out</button>
    </form>
</div>
