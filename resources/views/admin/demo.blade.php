@extends('layouts.app')
@section('title', 'Demo controls')
@section('heading', 'Demo mission control')
@section('content')
<div class="alert alert-warning" role="note"><strong>Local demo mode.</strong> Every scenario creates fictional simulation data. Reset rebuilds only the configured local demo database.</div>
<div class="row g-3">
 <div class="col-lg-7"><section class="oc-card h-100"><h2 class="oc-card-title">Scenario player</h2><p class="text-secondary">Run a scenario, then use the supplied crew link to inspect the evidence and response workflow.</p><div class="d-flex flex-wrap gap-2">
 @foreach (['stable' => 'Stable baseline', 'sleep_fatigue' => 'Sleep + fatigue trend', 'heart_rate_shift' => 'Heart-rate shift', 'stale' => 'Stale data', 'exercise_gap' => 'Exercise gap', 'radiation_event' => 'Radiation event', 'earth_blackout' => 'Earth-link blackout'] as $code => $label)
 <button class="btn btn-outline-primary btn-sm" data-demo-scenario="{{ $code }}">{{ $label }}</button>
 @endforeach
 </div><p id="scenario-result" class="small mt-3 mb-0" aria-live="polite"></p></section></div>
 <div class="col-lg-5"><section class="oc-card h-100"><h2 class="oc-card-title">Mission clock and reset</h2><form method="POST" action="{{ route('admin.demo.mission-day') }}" class="d-flex gap-2 mb-3">@csrf <label class="visually-hidden" for="mission-day">Mission day</label><input id="mission-day" name="day" type="number" min="1" max="180" value="{{ $mission?->starts_on?->diffInDays(today()) + 1 ?? 1 }}" class="form-control"><button class="btn btn-outline-primary">Set day</button></form><form method="POST" action="{{ route('admin.demo.reset') }}" onsubmit="return confirm('Rebuild the local fictional demo database?')">@csrf <button class="btn btn-danger">Reset demo data</button></form><hr><button type="button" class="btn btn-link p-0" data-demo-tour>Show guided demo steps</button></section></div>
</div>
<dialog class="oc-card" id="demo-tour"><h2 class="h5">Guided demo</h2><ol><li>Run a fictional scenario above.</li><li>Open its crew record and inspect the evidence, source, and rule version.</li><li>Record a follow-up only after review; alerts are not diagnoses.</li><li>Use the report to print or export labelled data.</li></ol><button class="btn btn-primary" onclick="this.closest('dialog').close()">Close</button></dialog>
@push('scripts')
<script>document.querySelector('[data-demo-tour]').addEventListener('click',()=>document.querySelector('#demo-tour').showModal());document.querySelectorAll('[data-demo-scenario]').forEach(button=>button.addEventListener('click',async()=>{button.disabled=true;let r=await fetch('{{ route('admin.demo.scenario') }}',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},body:JSON.stringify({scenario:button.dataset.demoScenario})});let d=await r.json();document.querySelector('#scenario-result').innerHTML=`${d.message}. <a href="${d.crew_url}">Open crew record</a>`;button.disabled=false;}));</script>
@endpush
@endsection
