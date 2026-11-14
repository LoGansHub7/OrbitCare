<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\CabinReading;
use App\Models\CrewProfile;
use App\Models\Mission;
use App\Models\RadiationReading;
use App\Models\User;
use App\Services\AlertEvaluationService;
use App\Services\HazardSimulator;
use Database\Seeders\AlertRuleSeeder;
use Database\Seeders\HazardSeeder;
use Database\Seeders\MetricDefinitionSeeder;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RiskSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HazardPhasesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed([HazardSeeder::class, RiskSeeder::class, ReferenceSeeder::class, MetricDefinitionSeeder::class, AlertRuleSeeder::class]);
    }

    private function crew(string $code = 'OC-P1'): CrewProfile
    {
        $mission = Mission::firstOrCreate(['name' => 'Test'], ['starts_on' => today()->subDays(30)]);
        $user = User::factory()->create(['role' => 'astronaut', 'is_active' => true]);

        return CrewProfile::create(['user_id' => $user->id, 'mission_id' => $mission->id, 'crew_code' => $code]);
    }

    private function officer(CrewProfile ...$assigned): User
    {
        $officer = User::factory()->create(['role' => 'medical_officer', 'is_active' => true]);
        foreach ($assigned as $crew) {
            $officer->assignedCrewProfiles()->attach($crew->id);
        }

        return $officer;
    }

    public static function scenarioRules(): array
    {
        return [
            'radiation' => ['radiation_event', 'radiation_dose_rate_rise'],
            'isolation' => ['isolation_decline', 'wellbeing_decline_trend'],
            'distance' => ['comms_delay', 'autonomy_escalation_under_delay'],
            'gravity' => ['deconditioning', 'physical_deconditioning_trend'],
            'environment' => ['cabin_co2', 'cabin_co2_elevated'],
        ];
    }

    /** @dataProvider scenarioRules */
    public function test_each_simulated_scenario_triggers_exactly_its_own_rule(string $scenario, string $ruleCode): void
    {
        $crew = $this->crew();
        app(HazardSimulator::class)->apply($crew, $scenario);
        app(AlertEvaluationService::class)->evaluateCrew($crew);

        $this->assertSame([$ruleCode], Alert::with('rule')->get()->pluck('rule.code')->all());
    }

    public function test_stable_scenario_is_clear_and_reapplying_does_not_duplicate(): void
    {
        $crew = $this->crew();
        $simulator = app(HazardSimulator::class);
        $service = app(AlertEvaluationService::class);

        $simulator->apply($crew, 'stable');
        $simulator->apply($crew, 'stable');
        $service->evaluateCrew($crew);

        $this->assertSame(0, Alert::count());
        $this->assertSame(10, RadiationReading::count());
        $this->assertSame(10, CabinReading::count());
        $this->assertSame(0, RadiationReading::where('is_simulated', false)->count());
    }

    public function test_unknown_scenario_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        app(HazardSimulator::class)->apply($this->crew(), 'nope');
    }

    public function test_simulate_command_requires_demo_mode(): void
    {
        $crew = $this->crew('OC-01');

        config(['orbitcare.demo_mode' => false]);
        $this->artisan('orbitcare:simulate', ['scenario' => 'cabin_co2'])->assertFailed();
        $this->assertSame(0, CabinReading::count());

        config(['orbitcare.demo_mode' => true]);
        $this->artisan('orbitcare:simulate', ['scenario' => 'cabin_co2'])->assertSuccessful();
        $this->assertSame(1, Alert::count());
        $this->assertSame($crew->id, Alert::sole()->crew_profile_id);
    }

    public function test_risk_register_and_evidence_library_filter_by_hazard(): void
    {
        $user = $this->crew()->user;

        $this->actingAs($user)->get(route('risks.index'))->assertOk()->assertSee('Radiation');
        $this->actingAs($user)->get(route('evidence.index'))->assertOk()->assertSee('NASA');
        $this->actingAs($user)->get(route('risks.index', ['hazard' => 'radiation']))->assertOk();
        $this->actingAs($user)->get(route('evidence.index', ['hazard' => 'not-a-hazard', 'q' => 'zzzz-no-match']))
            ->assertOk()->assertSee('No references match');
    }

    public function test_guests_cannot_open_reference_pages_or_reports(): void
    {
        $this->get(route('risks.index'))->assertRedirect(route('login'));
        $this->get(route('evidence.index'))->assertRedirect(route('login'));
        $this->get(route('hazard-report.self'))->assertRedirect(route('login'));
    }

    public function test_astronaut_hazard_report_and_csv_label_simulated_data(): void
    {
        $crew = $this->crew();
        app(HazardSimulator::class)->apply($crew, 'cabin_co2');
        app(AlertEvaluationService::class)->evaluateCrew($crew);

        $this->actingAs($crew->user)->get(route('hazard-report.self'))
            ->assertOk()->assertSee('Simulation')->assertSee('not a medical device');

        $csv = $this->actingAs($crew->user)->get(route('hazard-export.self'))->assertOk()->streamedContent();
        $this->assertStringStartsWith('hazard,record_type,crew_code,recorded_at_utc,metric,value,unit,source', $csv);
        $this->assertStringContainsString('environment,cabin_reading,OC-P1', $csv);
        $this->assertStringContainsString(',simulation', $csv);
    }

    public function test_medical_officer_sees_only_assigned_crew_hazard_data(): void
    {
        $assigned = $this->crew('OC-A');
        $other = $this->crew('OC-B');
        $officer = $this->officer($assigned);

        $this->actingAs($officer)->get(route('hazard-report.assigned', $assigned->id))->assertOk();
        $this->actingAs($officer)->get(route('hazard-export.assigned', $assigned->id))->assertOk();
        $this->actingAs($officer)->get(route('hazard-report.assigned', $other->id))->assertNotFound();
        $this->actingAs($officer)->get(route('hazard-export.assigned', $other->id))->assertNotFound();
        $this->actingAs($officer)->get(route('staff.crew.show', $assigned->id))->assertOk()->assertSee('Hazard status');
    }

    public function test_admin_and_astronaut_cannot_reach_other_roles_hazard_data(): void
    {
        $crew = $this->crew();
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)->get(route('hazard-report.self'))->assertForbidden();
        $this->actingAs($admin)->get(route('hazard-report.assigned', $crew->id))->assertForbidden();
        $this->actingAs($crew->user)->get(route('hazard-report.assigned', $crew->id))->assertForbidden();
        $this->actingAs($admin)->get(route('risks.index'))->assertOk();
    }

    public function test_hazard_dashboard_what_if_profile_changes_weights_only(): void
    {
        $crew = $this->crew();

        $this->actingAs($crew->user)->get(route('hazards.index', ['profile' => 'Mars']))
            ->assertOk()->assertSee('2.0x');
        $this->assertSame(Mission::weightsForProfile('Mars')['distance'], 2.0);
        $this->assertSame(0, Alert::count());
    }

    public function test_admin_demo_scenario_applies_hazard_data_when_demo_mode_is_on(): void
    {
        config(['orbitcare.demo_mode' => true]);
        $crew = $this->crew('OC-01');
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)->postJson(route('admin.demo.scenario'), ['scenario' => 'hazard_cabin_co2'])->assertOk();
        $this->assertSame('cabin_co2_elevated', Alert::with('rule')->sole()->rule->code);

        $this->actingAs($admin)->postJson(route('admin.demo.scenario'), ['scenario' => 'bogus'])->assertStatus(422);
        $this->assertNotNull($crew->fresh());
    }
}
