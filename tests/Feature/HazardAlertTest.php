<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\CabinReading;
use App\Models\CrewProfile;
use App\Models\Hazard;
use App\Models\Mission;
use App\Models\RadiationReading;
use App\Models\User;
use App\Models\WellbeingCheckin;
use App\Services\AlertEvaluationService;
use Database\Seeders\AlertRuleSeeder;
use Database\Seeders\HazardSeeder;
use Database\Seeders\MetricDefinitionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HazardAlertTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed([HazardSeeder::class, MetricDefinitionSeeder::class, AlertRuleSeeder::class]);
    }

    private function crew(): CrewProfile
    {
        $mission = Mission::create(['name' => 'Test', 'starts_on' => today()->subDays(30)]);
        $user = User::factory()->create(['role' => 'astronaut', 'is_active' => true]);

        return CrewProfile::create(['user_id' => $user->id, 'mission_id' => $mission->id, 'crew_code' => 'OC-H1']);
    }

    private function cabin(CrewProfile $crew, array $co2): void
    {
        foreach ($co2 as $i => $ppm) {
            CabinReading::create([
                'mission_id' => $crew->mission_id, 'temperature' => 22, 'pressure' => 101, 'co2_level' => $ppm,
                'light_level' => 300, 'noise_db' => 50, 'recorded_at' => now()->subHours(count($co2) - $i),
            ]);
        }
    }

    private function moods(CrewProfile $crew, array $moods): void
    {
        foreach ($moods as $i => $mood) {
            WellbeingCheckin::create([
                'crew_profile_id' => $crew->id, 'mission_id' => $crew->mission_id, 'mood' => $mood,
                'recorded_at' => now()->subDays(count($moods) - $i),
            ]);
        }
    }

    private function hazardId(string $slug): int
    {
        return Hazard::where('slug', $slug)->value('id');
    }

    public function test_sustained_high_co2_creates_one_explained_alert_linked_to_environment_hazard(): void
    {
        $crew = $this->crew();
        $this->cabin($crew, [3200, 3400, 3600]);
        $service = app(AlertEvaluationService::class);

        $service->evaluateCrew($crew);
        $service->evaluateCrew($crew);

        $alert = Alert::with('rule')->where('crew_profile_id', $crew->id)->sole();
        $this->assertSame('cabin_co2_elevated', $alert->rule->code);
        $this->assertSame($this->hazardId('environment'), $alert->rule->hazard_id);
        $this->assertSame('review', $alert->severity);
        $this->assertStringContainsString('experimental', $alert->explanation);
        $this->assertSame('review', $service->hazardStatuses($crew)[$this->hazardId('environment')]['status']);
    }

    public function test_missing_data_is_insufficient_not_no_flag(): void
    {
        $crew = $this->crew();
        $service = app(AlertEvaluationService::class);

        $service->evaluateCrew($crew);

        $this->assertSame(0, Alert::count());
        $this->assertSame('insufficient_data', $service->hazardStatuses($crew)[$this->hazardId('radiation')]['status']);
    }

    public function test_one_high_co2_reading_is_not_a_trend(): void
    {
        $crew = $this->crew();
        $this->cabin($crew, [2000, 4000, 2100]);

        app(AlertEvaluationService::class)->evaluateCrew($crew);

        $this->assertSame(0, Alert::count());
    }

    public function test_stale_cabin_data_is_insufficient(): void
    {
        $crew = $this->crew();
        foreach ([3500, 3500, 3500] as $ppm) {
            CabinReading::create([
                'mission_id' => $crew->mission_id, 'temperature' => 22, 'pressure' => 101, 'co2_level' => $ppm,
                'light_level' => 300, 'noise_db' => 50, 'recorded_at' => now()->subDays(10),
            ]);
        }

        app(AlertEvaluationService::class)->evaluateCrew($crew);

        $this->assertSame(0, Alert::count());
    }

    public function test_mood_must_decline_at_every_step(): void
    {
        $crew = $this->crew();
        $service = app(AlertEvaluationService::class);

        $this->moods($crew, [4, 2, 4, 3]);
        $service->evaluateCrew($crew);
        $this->assertSame(0, Alert::count());

        WellbeingCheckin::query()->delete();
        $this->moods($crew, [5, 4, 3, 2]);
        $service->evaluateCrew($crew);

        $alert = Alert::sole();
        $this->assertSame('priority_review', $alert->severity);
        $this->assertSame([5, 4, 3, 2], $alert->evidence['mood_series_oldest_first']);
    }

    public function test_radiation_rise_over_baseline_triggers_and_stable_does_not(): void
    {
        $crew = $this->crew();
        $service = app(AlertEvaluationService::class);
        $rates = [0.5, 0.5, 0.5, 0.5, 0.5, 0.5];

        foreach ($rates as $i => $rate) {
            RadiationReading::create([
                'crew_profile_id' => $crew->id, 'mission_id' => $crew->mission_id, 'dose_rate' => $rate,
                'cumulative_dose' => 10, 'recorded_at' => now()->subHours(20 - $i),
            ]);
        }
        $service->evaluateCrew($crew);
        $this->assertSame(0, Alert::count());

        foreach ([0.9, 0.9, 0.9] as $i => $rate) {
            RadiationReading::create([
                'crew_profile_id' => $crew->id, 'mission_id' => $crew->mission_id, 'dose_rate' => $rate,
                'cumulative_dose' => 12, 'recorded_at' => now()->subMinutes(10 - $i),
            ]);
        }
        $service->evaluateCrew($crew);

        $this->assertSame('radiation_dose_rate_rise', Alert::with('rule')->sole()->rule->code);
    }

    public function test_open_alert_keeps_hazard_flagged_after_values_recover(): void
    {
        $crew = $this->crew();
        $this->cabin($crew, [3500, 3500, 3500]);
        $service = app(AlertEvaluationService::class);
        $service->evaluateCrew($crew);

        $this->cabin($crew, [1500, 1500, 1500]);
        $service->evaluateCrew($crew);

        $this->assertSame('open', Alert::sole()->status);
        $this->assertSame('review', $service->hazardStatuses($crew)[$this->hazardId('environment')]['status']);
    }

    public function test_all_monitor_pages_render_empty_and_with_data(): void
    {
        $crew = $this->crew();
        $routes = ['radiation', 'wellbeing', 'autonomy', 'physical', 'environment'];

        foreach ($routes as $name) {
            $this->actingAs($crew->user)->get(route("monitor.$name"))->assertOk();
        }

        $this->cabin($crew, [3500, 3500, 3500]);
        $this->moods($crew, [5, 4, 3, 2]);
        RadiationReading::create(['crew_profile_id' => $crew->id, 'mission_id' => $crew->mission_id, 'dose_rate' => 0.5, 'cumulative_dose' => 10, 'recorded_at' => now()]);

        foreach ($routes as $name) {
            $this->actingAs($crew->user)->get(route("monitor.$name"))->assertOk();
        }
    }

    public function test_hazard_dashboard_and_alert_filter_render_for_astronaut(): void
    {
        $crew = $this->crew();
        $this->cabin($crew, [3500, 3500, 3500]);
        app(AlertEvaluationService::class)->evaluateCrew($crew);

        $this->actingAs($crew->user)->get(route('hazards.index'))->assertOk()->assertSee('1 alert');
        $this->actingAs($crew->user)->get(route('alerts.index', ['hazard' => 'environment']))
            ->assertOk()->assertSee('Cabin CO2');
        $this->actingAs($crew->user)->get(route('alerts.index', ['hazard' => 'radiation']))
            ->assertOk()->assertDontSee('Cabin CO2');
    }
}
