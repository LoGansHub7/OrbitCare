@props(['state'])
@php
    $map = [
        'current' => ['Current', 'oc-badge-ok', 'bi-check-circle'],
        'stale' => ['Stale', 'oc-badge-unknown', 'bi-clock-history'],
        'no_data' => ['No data', 'oc-badge-unknown', 'bi-question-circle'],
    ];
    [$label, $class, $icon] = $map[$state] ?? $map['no_data'];
@endphp
<span class="oc-badge {{ $class }}"><i class="bi {{ $icon }}" aria-hidden="true"></i> {{ $label }}</span>
