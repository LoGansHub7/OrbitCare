@props(['card'])
<div class="oc-card h-100">
    <div class="oc-card-label"><i class="bi {{ $card['icon'] }}" aria-hidden="true"></i> {{ $card['label'] }}</div>
    @if ($card['value'] !== null)
        <div class="oc-metric">{{ $card['value'] }}<span class="oc-unit">{{ $card['unit'] }}</span></div>
        @if (isset($card['delta']) && $card['delta'] !== null)
            <div class="small {{ $card['delta'] > 0 ? 'text-danger' : 'text-success' }}"><i class="bi {{ $card['delta'] > 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-right' }}" aria-hidden="true"></i> {{ number_format(abs($card['delta']), 1) }} vs baseline</div>
        @endif
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
