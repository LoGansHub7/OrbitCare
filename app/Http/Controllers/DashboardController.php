<?php

namespace App\Http\Controllers;

use App\Models\MetricDefinition;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        // Everything is scoped to the signed-in astronaut's own crew profile.
        // No record IDs are accepted from the URL, so other crew members' data cannot be reached.
        $crew = $request->user()->crewProfile()->with('mission')->firstOrFail();

        $range = (int) $request->query('range', 14);
        $range = in_array($range, [7, 14], true) ? $range : 14;

        $defs = MetricDefinition::all()->keyBy('code');
        abort_if($defs->isEmpty(), 500, 'Metric definitions missing. Run: php artisan db:seed');

        $hrDef = $defs['resting_heart_rate'];
        $latestCheckin = $crew->checkins()->latest('mission_date')->first();
        $latestHr = $crew->readings()->where('metric_definition_id', $hrDef->id)->latest('observed_at')->first();

        $cards = [
            $this->card($hrDef, 'bi-heart-pulse', $latestHr ? number_format((float) $latestHr->value, 0) : null,
                $latestHr?->observed_at, $latestHr?->source),
            $this->card($defs['sleep_duration'], 'bi-moon-stars', $latestCheckin ? number_format((float) $latestCheckin->sleep_hours, 1) : null,
                $latestCheckin?->submitted_at, $latestCheckin?->source),
            $this->card($defs['fatigue_rating'], 'bi-battery-half', $latestCheckin ? (string) $latestCheckin->fatigue_rating : null,
                $latestCheckin?->submitted_at, $latestCheckin?->source, 'Scale 1–5 · higher = more fatigued'),
            $this->card($defs['exercise_duration'], 'bi-activity', $latestCheckin ? (string) $latestCheckin->exercise_minutes : null,
                $latestCheckin?->submitted_at, $latestCheckin?->source),
        ];

        $days = collect(range($range - 1, 0))->map(fn ($n) => today()->subDays($n));
        $from = $days->first();

        $checkins = $crew->checkins()
            ->where('mission_date', '>=', $from->toDateString())
            ->get()
            ->keyBy(fn ($c) => $c->mission_date->toDateString());

        $hrByDay = $crew->readings()
            ->where('metric_definition_id', $hrDef->id)
            ->where('observed_at', '>=', $from->copy()->startOfDay())
            ->get()
            ->groupBy(fn ($r) => $r->observed_at->toDateString())
            ->map(fn ($group) => round((float) $group->avg('value'), 1));

        $rows = $days->map(function (Carbon $d) use ($checkins, $hrByDay) {
            $c = $checkins->get($d->toDateString());

            return [
                'date' => $d,
                'label' => $d->format('M j'),
                'sleep' => $c ? (float) $c->sleep_hours : null,
                'quality' => $c?->sleep_quality,
                'fatigue' => $c?->fatigue_rating,
                'exercise' => $c?->exercise_minutes,
                'hr' => $hrByDay->get($d->toDateString()),
            ];
        });

        $labels = $rows->pluck('label')->all();
        $charts = [
            'hr' => $this->chart('line', 'Resting heart rate (daily average)', 'bpm', '#B91C1C', 'rgba(185,28,28,.10)', $labels, $rows->pluck('hr')->all(), null, null),
            'sleep' => $this->chart('bar', 'Sleep duration', 'hours', '#2563EB', 'rgba(37,99,235,.65)', $labels, $rows->pluck('sleep')->all(), 0, 12),
            'fatigue' => $this->chart('line', 'Fatigue rating', 'score (1–5, higher = more fatigued)', '#0F766E', 'rgba(15,118,110,.10)', $labels, $rows->pluck('fatigue')->all(), 1, 5),
        ];

        return view('dashboard.index', [
            'crew' => $crew,
            'range' => $range,
            'cards' => $cards,
            'charts' => $charts,
            'rows' => $rows,
            'latestCheckin' => $latestCheckin,
            'checkedInToday' => $checkins->has(today()->toDateString()),
        ]);
    }

    private function card($def, string $icon, ?string $value, ?Carbon $at, ?string $source, ?string $hint = null): array
    {
        $freshness = 'no_data';
        if ($value !== null && $at) {
            $freshness = $at->lt(now()->subHours($def->freshness_hours)) ? 'stale' : 'current';
        }

        return [
            'label' => $def->label,
            'icon' => $icon,
            'value' => $value,
            'unit' => $def->unit,
            'observed_at' => $at,
            'source' => $source,
            'freshness' => $freshness,
            'hint' => $hint,
        ];
    }

    private function chart(string $type, string $label, string $unit, string $color, string $fill, array $labels, array $data, ?int $min, ?int $max): array
    {
        return compact('type', 'label', 'unit', 'color', 'fill', 'labels', 'data', 'min', 'max') + [
            'hasData' => collect($data)->contains(fn ($v) => $v !== null),
        ];
    }
}
