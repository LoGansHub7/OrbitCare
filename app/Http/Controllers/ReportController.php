<?php

namespace App\Http\Controllers;

use App\Models\CrewProfile;
use App\Models\MetricDefinition;
use App\Models\AlertRule;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function research()
    {
        return view('research.index');
    }

    public function help()
    {
        return view('help.index');
    }

    public function alertCatalog()
    {
        return view('alerts.catalog', ['rules' => AlertRule::query()->orderBy('code')->get()]);
    }

    public function showSelf(Request $request)
    {
        $crew = $request->user()->crewProfile()->with(['user', 'mission'])->firstOrFail();

        return $this->showForCrew($request, $crew, false);
    }

    public function showAssigned(Request $request, int $crewProfileId)
    {
        abort_unless($request->user()->isMedicalOfficer(), 403);
        $crew = $request->user()->assignedCrewProfiles()
            ->with(['user', 'mission'])
            ->whereKey($crewProfileId)
            ->firstOrFail();

        return $this->showForCrew($request, $crew, true);
    }

    public function exportSelf(Request $request): StreamedResponse
    {
        $crew = $request->user()->crewProfile()->firstOrFail();

        return $this->exportCrew($crew);
    }

    public function exportAssigned(Request $request, int $crewProfileId): StreamedResponse
    {
        abort_unless($request->user()->isMedicalOfficer(), 403);
        $crew = $request->user()->assignedCrewProfiles()
            ->whereKey($crewProfileId)
            ->firstOrFail();

        return $this->exportCrew($crew);
    }

    private function showForCrew(Request $request, CrewProfile $crew, bool $isStaff)
    {
        $range = (int) $request->query('range', 14);
        $range = in_array($range, [7, 14, 30], true) ? $range : 14;
        $from = today()->subDays($range - 1)->startOfDay();
        $heartRateDefinition = MetricDefinition::query()
            ->where('code', 'resting_heart_rate')
            ->firstOrFail();
        $checkins = $crew->checkins()
            ->whereBetween('mission_date', [$from->toDateString(), today()->toDateString()])
            ->orderBy('mission_date')
            ->get();
        $readings = $crew->readings()
            ->where('metric_definition_id', $heartRateDefinition->id)
            ->whereBetween('observed_at', [$from, now()])
            ->orderBy('observed_at')
            ->get();
        $alerts = $crew->alerts()
            ->with(['rule', 'followups.actor'])
            ->where('first_detected_at', '>=', $from)
            ->orderByDesc('first_detected_at')
            ->get();
        $summary = [
            'checkin_days' => $checkins->count(),
            'heart_rate_readings' => $readings->count(),
            'average_sleep' => $checkins->isNotEmpty() ? round((float) $checkins->avg('sleep_hours'), 1) : null,
            'average_fatigue' => $checkins->isNotEmpty() ? round((float) $checkins->avg('fatigue_rating'), 1) : null,
            'average_heart_rate' => $readings->isNotEmpty() ? round((float) $readings->avg('value'), 1) : null,
        ];

        return view('reports.show', compact(
            'crew',
            'range',
            'from',
            'checkins',
            'readings',
            'alerts',
            'summary',
            'heartRateDefinition',
            'isStaff',
        ));
    }

    private function exportCrew(CrewProfile $crew): StreamedResponse
    {
        $checkins = $crew->checkins()->orderBy('mission_date')->get();
        $readings = $crew->readings()->with('metric')->orderBy('observed_at')->get();
        $filename = 'orbitcare-'.$crew->crew_code.'-health-data.csv';

        return response()->streamDownload(function () use ($checkins, $readings, $crew): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['record_type', 'crew_code', 'mission_date', 'metric_code', 'value', 'unit', 'observed_at_utc', 'source', 'notes']);

            foreach ($checkins as $checkin) {
                $metrics = [
                    ['sleep_duration', (float) $checkin->sleep_hours, 'hours'],
                    ['sleep_quality', (int) $checkin->sleep_quality, 'score_1_to_5'],
                    ['fatigue_rating', (int) $checkin->fatigue_rating, 'score_1_to_5'],
                    ['exercise_duration', (int) $checkin->exercise_minutes, 'min'],
                ];

                foreach ($metrics as [$code, $value, $unit]) {
                    fputcsv($output, [
                        'daily_checkin',
                        $this->safeCsvText($crew->crew_code),
                        $checkin->mission_date->toDateString(),
                        $code,
                        $value,
                        $unit,
                        $checkin->submitted_at->copy()->utc()->format('Y-m-d\TH:i:s\Z'),
                        $checkin->source,
                        $this->safeCsvText($checkin->symptom_notes),
                    ]);
                }
            }

            foreach ($readings as $reading) {
                fputcsv($output, [
                    'health_reading',
                    $this->safeCsvText($crew->crew_code),
                    $reading->observed_at->toDateString(),
                    $reading->metric?->code ?? 'unknown_metric',
                    (float) $reading->value,
                    $reading->metric?->unit ?? '',
                    $reading->observed_at->copy()->utc()->format('Y-m-d\TH:i:s\Z'),
                    $reading->source,
                    '',
                ]);
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function safeCsvText(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return preg_match('/^[\t\r ]*[=+\-@]/', $value) === 1 ? "'".$value : $value;
    }
}
