@props(['level'])
@php
    $map = [
        'nominal' => ['bg' => '#15803D', 'label' => 'Nominal', 'icon' => 'bi-check-circle'],
        'watch' => ['bg' => '#64748B', 'label' => 'Monitoring', 'icon' => 'bi-eye'],
        'review' => ['bg' => '#B45309', 'label' => 'Review', 'icon' => 'bi-exclamation-circle'],
        'urgent' => ['bg' => '#B91C1C', 'label' => 'Priority review', 'icon' => 'bi-exclamation-triangle-fill'],
    ];
    $info = $map[$level] ?? $map['watch'];
@endphp
<span class="badge d-inline-flex align-items-center gap-1" style="background-color: {{ $info['bg'] }};">
    <i class="bi {{ $info['icon'] }}" aria-hidden="true"></i> {{ $info['label'] }}
</span>
