<?php

namespace App\Services;

use App\Models\AutonomyEvent;
use App\Models\CabinReading;
use App\Models\CrewProfile;
use App\Models\Hazard;
use App\Models\PhysicalReading;
use App\Models\RadiationReading;
use App\Models\WellbeingCheckin;
use Illuminate\Support\Collection;

/** Builds the per-hazard summary used by the printable report and the CSV export. */
class HazardReportService
{
    public function __construct(private readonly AlertEvaluationService $alerts) {}

    /** @return Collection<int, array<string, mixed>> */
    public function summary(CrewProfile $crew): Collection
    {
        $statuses = $this->alerts->hazardStatuses($crew);
        $openAlerts = $crew->alerts()->with('rule')->where('status', '!=', 'closed')->get()
            ->groupBy(fn ($alert) => $alert->rule?->hazard_id);

        return Hazard::with(['references' => fn ($q) => $q->limit(3)])->orderBy('sort_order')->get()
            ->map(function (Hazard $hazard) use ($crew, $statuses, $openAlerts): array {
                [$records, $latest] = $this->latest($hazard->slug, $crew);

                return [
                    'hazard' => $hazard,
                    'status' => $statuses[$hazard->id]['status'] ?? 'insufficient_data',
                    'alerts' => $openAlerts->get($hazard->id, collect()),
                    'record_count' => $records->count(),
                    'includes_simulated' => $records->contains('is_simulated', true),
                    'latest' => $latest,
                    'latest_at' => $records->first()?->recorded_at,
                ];
            });
    }

    /** @return array<int, array<int, string|int|float|null>> CSV rows without header */
    public function csvRows(CrewProfile $crew): array
    {
        $rows = [];
        $add = function (string $hazard, string $type, $at, string $metric, $value, string $unit, bool $simulated) use (&$rows, $crew): void {
            $rows[] = [$hazard, $type, $crew->crew_code, $at?->copy()->utc()->format('Y-m-d\TH:i:s\Z'), $metric, $value, $unit, $simulated ? 'simulation' : 'recorded'];
        };

        foreach (RadiationReading::where('crew_profile_id', $crew->id)->orderBy('recorded_at')->get() as $r) {
            $add('radiation', 'radiation_reading', $r->recorded_at, 'dose_rate', $r->dose_rate, 'mSv/day', $r->is_simulated);
            $add('radiation', 'radiation_reading', $r->recorded_at, 'cumulative_dose', $r->cumulative_dose, 'mSv', $r->is_simulated);
        }
        foreach (WellbeingCheckin::where('crew_profile_id', $crew->id)->orderBy('recorded_at')->get() as $r) {
            $add('isolation', 'wellbeing_checkin', $r->recorded_at, 'mood', $r->mood, 'score_1_to_5', $r->is_simulated);
            $add('isolation', 'wellbeing_checkin', $r->recorded_at, 'stress', $r->stress, 'score_1_to_5', $r->is_simulated);
            $add('isolation', 'wellbeing_checkin', $r->recorded_at, 'sleep_hours', $r->sleep_hours, 'hours', $r->is_simulated);
        }
        foreach (AutonomyEvent::where('crew_profile_id', $crew->id)->orderBy('recorded_at')->get() as $r) {
            $add('distance', 'autonomy_event', $r->recorded_at, 'comms_delay', $r->comms_delay_seconds, 'seconds', $r->is_simulated);
            $add('distance', 'autonomy_event', $r->recorded_at, 'escalated_to_earth', (int) $r->escalated_to_earth, 'flag', $r->is_simulated);
        }
        foreach (PhysicalReading::where('crew_profile_id', $crew->id)->orderBy('recorded_at')->get() as $r) {
            $add('gravity', 'physical_reading', $r->recorded_at, 'exercise_minutes', $r->exercise_minutes, 'min', $r->is_simulated);
            $add('gravity', 'physical_reading', $r->recorded_at, 'muscle_index', $r->muscle_index, 'index', $r->is_simulated);
        }
        foreach (CabinReading::where('mission_id', $crew->mission_id)->orderBy('recorded_at')->get() as $r) {
            $add('environment', 'cabin_reading', $r->recorded_at, 'co2', $r->co2_level, 'ppm', $r->is_simulated);
            $add('environment', 'cabin_reading', $r->recorded_at, 'temperature', $r->temperature, 'celsius', $r->is_simulated);
        }

        return $rows;
    }

    /** @return array{0: Collection, 1: string} newest-first records and a one-line plain-language summary */
    private function latest(string $slug, CrewProfile $crew): array
    {
        $none = 'No readings recorded.';

        return match ($slug) {
            'radiation' => $this->describe(
                RadiationReading::where('crew_profile_id', $crew->id)->orderByDesc('recorded_at')->get(),
                fn ($r) => sprintf('Latest dose rate %.3f mSv/day; cumulative %.2f mSv.', $r->dose_rate, $r->cumulative_dose), $none),
            'isolation' => $this->describe(
                WellbeingCheckin::where('crew_profile_id', $crew->id)->orderByDesc('recorded_at')->get(),
                fn ($r) => sprintf('Latest mood %d/5, stress %d/5, sleep %.1f h.', $r->mood, $r->stress, $r->sleep_hours), $none),
            'distance' => $this->describe(
                AutonomyEvent::where('crew_profile_id', $crew->id)->orderByDesc('recorded_at')->get(),
                fn ($r) => sprintf('Latest event: %d s one-way delay, %s.', $r->comms_delay_seconds, $r->escalated_to_earth ? 'escalated to Earth' : 'handled onboard'), $none),
            'gravity' => $this->describe(
                PhysicalReading::where('crew_profile_id', $crew->id)->orderByDesc('recorded_at')->get(),
                fn ($r) => sprintf('Latest muscle index %s; exercise %d min.', $r->muscle_index ?? 'n/a', $r->exercise_minutes), $none),
            default => $this->describe(
                CabinReading::where('mission_id', $crew->mission_id)->orderByDesc('recorded_at')->get(),
                fn ($r) => sprintf('Latest cabin CO2 %.0f ppm, %.1f °C.', $r->co2_level, $r->temperature), $none),
        };
    }

    private function describe(Collection $records, callable $line, string $empty): array
    {
        return [$records, $records->isEmpty() ? $empty : $line($records->first())];
    }
}
