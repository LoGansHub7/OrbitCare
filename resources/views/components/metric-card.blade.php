@props(['card'])
<div class="oc-card h-100">
    <div class="oc-card-label"><i class="bi {{ $card['icon'] }}" aria-hidden="true"></i> {{ $card['label'] }}</div>
    @if ($card['value'] !== null)
        <div class="oc-metric">{{ $card['value'] }}<span class="oc-unit">{{ $card['unit'] }}</span></div>
        <div class="oc-meta">{{ $card['observed_at']->format('M j, H:i') }} UTC</div>
        <div class="d-flex flex-wrap gap-2 mt-2">
            <x-status-badge :state="$card['freshness']" />
            <x-source-label :source="$card['source']" />
        </div>
    @else
        <div class="oc-metric oc-muted">—</div>
        <div class="oc-meta">No readings yet</div>
        <div class="mt-2"><x-status-badge state="no_data" /></div>
    @endif
    @if (! empty($card['hint']))
        <div class="oc-hint">{{ $card['hint'] }}</div>
    @endif
</div>
