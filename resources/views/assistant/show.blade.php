@extends('layouts.app')

@section('title', 'ORBIT assistant')
@section('heading', 'ORBIT assistant')

@section('content')
    <p class="text-secondary small mb-3">
        Ask about your vitals, sleep, radiation, exercise, or emergency steps. ORBIT works offline and does not replace the mission medical team.
    </p>
    <x-orbit-assistant mode="page" />
@endsection
