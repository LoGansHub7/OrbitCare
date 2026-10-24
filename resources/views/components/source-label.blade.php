@props(['source'])
@php
    $map = [
        'simulation' => ['Simulation', 'oc-badge-sim', 'bi-cpu'],
        'manual' => ['Manual entry', 'oc-badge-manual', 'bi-pencil-square'],
        'csv_import' => ['CSV import', 'oc-badge-unknown', 'bi-filetype-csv'],
    ];
    [$label, $class, $icon] = $map[$source] ?? ['Unknown source', 'oc-badge-unknown', 'bi-question-circle'];
@endphp
<span class="oc-badge {{ $class }}"><i class="bi {{ $icon }}" aria-hidden="true"></i> {{ $label }}</span>
