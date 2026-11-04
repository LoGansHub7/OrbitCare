@extends('layouts.app')
@section('title', 'How alerts work')
@section('heading', 'How alerts work')
@section('content')
<div class="alert alert-info" role="note">Rules are transparent experimental checks. An alert is not a diagnosis, medical clearance, or treatment recommendation.</div>
@forelse($rules as $rule)<section class="oc-card mb-3"><div class="d-flex justify-content-between gap-3"><h2 class="oc-card-title">{{ str_replace('_', ' ', $rule->code) }}</h2><span class="small text-secondary">Version {{ $rule->version }} · {{ $rule->is_active ? 'active' : 'inactive' }}</span></div><p>{{ $rule->description }}</p><details><summary>Parameters and interpretation</summary><pre class="small mt-2 mb-0">{{ json_encode($rule->parameters, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></details><p class="small text-secondary mt-3 mb-0">Missing or stale records produce insufficient-data status; a rule not triggering does not mean someone is healthy or safe.</p></section>@empty <p class="text-secondary">No alert rules are configured.</p>@endforelse
@endsection
