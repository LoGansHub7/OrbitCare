<?php

namespace App\Http\Controllers;

use App\Models\CrewProfile;
use App\Services\HazardReportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HazardReportController extends Controller
{
    public function __construct(private readonly HazardReportService $reports) {}

    public function showSelf(Request $request)
    {
        return $this->show($request->user()->crewProfile()->with(['user', 'mission'])->firstOrFail(), false);
    }

    public function showAssigned(Request $request, int $crewProfileId)
    {
        return $this->show($this->assigned($request, $crewProfileId), true);
    }

    public function exportSelf(Request $request): StreamedResponse
    {
        return $this->export($request->user()->crewProfile()->firstOrFail());
    }

    public function exportAssigned(Request $request, int $crewProfileId): StreamedResponse
    {
        return $this->export($this->assigned($request, $crewProfileId));
    }

    private function assigned(Request $request, int $crewProfileId): CrewProfile
    {
        abort_unless($request->user()->isMedicalOfficer(), 403);

        return $request->user()->assignedCrewProfiles()->with(['user', 'mission'])->whereKey($crewProfileId)->firstOrFail();
    }

    private function show(CrewProfile $crew, bool $isStaff)
    {
        return view('reports.hazards', [
            'crew' => $crew,
            'isStaff' => $isStaff,
            'rows' => $this->reports->summary($crew),
            'generatedAt' => now()->utc(),
        ]);
    }

    private function export(CrewProfile $crew): StreamedResponse
    {
        $rows = $this->reports->csvRows($crew);

        return response()->streamDownload(function () use ($rows): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['hazard', 'record_type', 'crew_code', 'recorded_at_utc', 'metric', 'value', 'unit', 'source']);
            foreach ($rows as $row) {
                fputcsv($output, $row);
            }
            fclose($output);
        }, 'orbitcare-'.$crew->crew_code.'-hazard-data.csv', ['Content-Type' => 'text/csv']);
    }
}
