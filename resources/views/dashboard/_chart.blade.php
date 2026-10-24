@if ($chart['hasData'])
    <div class="oc-chart">
        <canvas role="img" aria-label="{{ $chart['label'] }} chart, see data table below" data-chart="{{ json_encode($chart) }}"></canvas>
    </div>
@else
    <div class="oc-meta py-5 text-center"><i class="bi bi-question-circle" aria-hidden="true"></i> Insufficient data in this range.</div>
@endif
