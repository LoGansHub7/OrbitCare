@extends('layouts.app')
@section('title', 'Help and glossary')
@section('heading', 'Help & glossary')
@section('content')
<div class="row g-3"><div class="col-lg-7"><section class="oc-card"><h2 class="oc-card-title">Using OrbitCare</h2><ol class="mb-0"><li>Record a daily check-in and any observed readings. Entries are labelled with their source.</li><li>Review alerts as prompts for review, not diagnoses or treatment instructions.</li><li>Use reports to print a summary or export your recorded data as CSV.</li><li>Read the <a href="{{ route('research.index') }}">research and limitations</a> before drawing conclusions.</li></ol></section></div><div class="col-lg-5"><section class="oc-card"><h2 class="oc-card-title">Glossary</h2><dl class="mb-0"><dt>Baseline</dt><dd>An experimental reference value, not a clinical normal.</dd><dt>Evidence</dt><dd>The time window and values a rule recorded when it evaluated.</dd><dt>Freshness</dt><dd>How recently a record was observed; stale data is unavailable data.</dd><dt>Source</dt><dd>Manual, simulation, or import provenance for a record.</dd></dl></section></div></div>
@endsection
