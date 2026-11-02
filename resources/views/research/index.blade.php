@extends('layouts.app')
@section('title', 'Research and limitations')
@section('heading', 'Research & limitations')
@section('content')
    <div class="alert alert-info" role="note">Research references explain why OrbitCare tracks these indicators. They do not validate this prototype’s rules, thresholds, or individual-level conclusions.</div>

    <section class="oc-card mb-3" aria-labelledby="sources-heading">
        <h2 id="sources-heading" class="oc-card-title">Research references</h2>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Reference</th><th>What it supports</th><th>Limitation</th></tr></thead>
                <tbody>
                <tr>
                    <td><a href="https://www.nasa.gov/reference/risk-from-inadequate-sleep-and-irregular-schedules/" target="_blank" rel="noopener noreferrer">Risk from Inadequate Sleep and Irregular Schedules</a><div class="small text-secondary">NASA Human Research Program</div></td>
                    <td>Spaceflight context for tracking sleep, circadian schedule, and fatigue.</td>
                    <td>Not a patient-specific prediction or a rule for interpreting an individual’s records.</td>
                </tr>
                <tr>
                    <td><a href="https://www.nasa.gov/humans-in-space/science-in-space-week-of-sept-15-2023-sleep-on-station/" target="_blank" rel="noopener noreferrer">Science in Space: Sleep on Station</a><div class="small text-secondary">NASA</div></td>
                    <td>Background for viewing sleep duration and quality trends together.</td>
                    <td>Does not establish OrbitCare’s sample count, cut-off, or alert severity.</td>
                </tr>
                <tr>
                    <td><a href="https://www.nasa.gov/wp-content/uploads/2023/03/sleep-accomodations-technical-brief-ochmo.pdf" target="_blank" rel="noopener noreferrer">NASA-STD-3001 Technical Brief — Sleep Accommodations</a><div class="small text-secondary">NASA OCHMO</div></td>
                    <td>Spaceflight context for sleep duration.</td>
                    <td>Population averages are not individual normal values or clinical thresholds.</td>
                </tr>
                <tr>
                    <td><a href="https://www.nasa.gov/human-systems-integration-division/human-performance/fatigue-countermeasures-laboratory/" target="_blank" rel="noopener noreferrer">Fatigue Countermeasures Laboratory</a><div class="small text-secondary">NASA Ames Research Center</div></td>
                    <td>Research context for sleep loss, circadian timing, and performance.</td>
                    <td>Does not supply production-ready medical alert logic.</td>
                </tr>
                <tr>
                    <td><a href="https://www.nasa.gov/ochmo-tb-031-exercise-overview-2/" target="_blank" rel="noopener noreferrer">Exercise Overview (OCHMO-TB-031)</a><div class="small text-secondary">NASA OCHMO</div></td>
                    <td>Context for recording exercise and cardiovascular indicators.</td>
                    <td>Does not validate a personal resting-heart-rate baseline or alert threshold.</td>
                </tr>
                </tbody>
            </table>
        </div>
    </section>

    <div class="row g-3">
        <div class="col-12 col-lg-6">
            <section class="oc-card h-100" aria-labelledby="provenance-heading">
                <h2 id="provenance-heading" class="oc-card-title">Data provenance</h2>
                <ul class="mb-0">
                    <li><strong>Imported research data:</strong> none. Research publications are background references only.</li>
                    <li><strong>Simulation:</strong> seeded crew scenarios are fictional and must not be presented as NASA or real astronaut measurements.</li>
                    <li><strong>Manual:</strong> entries are user-submitted and labelled accordingly.</li>
                    <li><strong>Experimental assumptions:</strong> baselines, windows, rule parameters, and alert statuses are prototype assumptions.</li>
                </ul>
            </section>
        </div>
        <div class="col-12 col-lg-6">
            <section class="oc-card h-100" aria-labelledby="limits-heading">
                <h2 id="limits-heading" class="oc-card-title">Known limitations</h2>
                <ul class="mb-0">
                    <li>OrbitCare is a demonstration prototype, not a medical device or clinical decision system.</li>
                    <li>Alerts are not diagnoses and do not provide treatment instructions.</li>
                    <li>Input plausibility bounds are data-quality checks, not medical thresholds.</li>
                    <li>“No flag” means no implemented rule triggered; it does not mean someone is healthy or safe.</li>
                    <li>Missing or stale records mean data is unavailable or old; they do not imply absence of concern.</li>
                    <li>Validation with qualified clinicians and representative research data has not been performed.</li>
                </ul>
            </section>
        </div>
    </div>

    <p class="small text-secondary mt-3">The full team research register is maintained in <code>docs/research-register.md</code>.</p>
@endsection
