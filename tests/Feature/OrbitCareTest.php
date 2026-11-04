<?php

namespace Tests\Feature;

use App\Models\CrewProfile;
use App\Models\DailyCheckin;
use App\Models\Alert;
use App\Models\HealthReading;
use App\Models\MetricDefinition;
use App\Models\Mission;
use App\Models\User;
use App\Services\AlertEvaluationService;
use Database\Seeders\AlertRuleSeeder;
use Database\Seeders\MetricDefinitionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrbitCareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(MetricDefinitionSeeder::class);
        $this->seed(AlertRuleSeeder::class);
    }

    private function astronaut(string $code = 'OC-T1'): User
    {
        $mission = Mission::firstOrCreate(['name' => 'Test'], ['starts_on' => today()->subDays(10)]);
        $user = User::factory()->create(['role' => 'astronaut', 'is_active' => true]);
        CrewProfile::create(['user_id' => $user->id, 'mission_id' => $mission->id, 'crew_code' => $code]);

        return $user;
    }

    private function checkinPayload(array $over = []): array
    {
        return array_merge(['sleep_hours' => 7.5, 'sleep_quality' => 4, 'fatigue_rating' => 2, 'exercise_minutes' => 40], $over);
    }

    private function triggeredAlertFor(User $user): Alert
    {
        $crew = $user->crewProfile()->firstOrFail();

        foreach ([2, 1, 0] as $offset) {
            DailyCheckin::create([
                'crew_profile_id' => $crew->id,
                'mission_date' => today()->subDays($offset)->toDateString(),
                'sleep_hours' => 5.5,
                'sleep_quality' => 3,
                'fatigue_rating' => 4,
                'exercise_minutes' => 20,
                'source' => 'manual',
                'submitted_at' => now()->subDays($offset),
            ]);
        }

        app(AlertEvaluationService::class)->evaluateCrew($crew);

        return Alert::where('crew_profile_id', $crew->id)->firstOrFail();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_astronaut_sees_dashboard_with_no_data(): void
    {
        $this->actingAs($this->astronaut())->get('/dashboard')->assertOk()->assertSee('Resting heart rate');
    }

    public function test_staff_cannot_open_astronaut_pages(): void
    {
        $staff = User::factory()->create(['role' => 'medical_officer', 'is_active' => true]);
        $this->actingAs($staff)->get('/dashboard')->assertForbidden();
    }

    public function test_checkin_is_saved_and_resubmission_corrects_instead_of_duplicating(): void
    {
        $user = $this->astronaut();
        $this->actingAs($user)->post('/check-in', $this->checkinPayload())->assertRedirect('/dashboard');
        $this->actingAs($user)->post('/check-in', $this->checkinPayload(['fatigue_rating' => 4]))->assertRedirect('/dashboard');

        $this->assertDatabaseCount('daily_checkins', 1);
        $this->assertDatabaseHas('daily_checkins', ['fatigue_rating' => 4]);
        $this->assertDatabaseHas('audit_events', ['action' => 'checkin.corrected']);
    }

    public function test_invalid_checkin_is_rejected(): void
    {
        $this->actingAs($this->astronaut())
            ->post('/check-in', $this->checkinPayload(['sleep_hours' => 30]))
            ->assertSessionHasErrors('sleep_hours');
        $this->assertDatabaseCount('daily_checkins', 0);
    }

    public function test_implausible_heart_rate_is_rejected(): void
    {
        $this->actingAs($this->astronaut())
            ->post('/readings', ['value' => 400, 'observed_at' => now()->subHour()->format('Y-m-d\TH:i')])
            ->assertSessionHasErrors('value');
    }

    public function test_astronaut_can_record_an_extended_checkin_and_a_supported_vital(): void
    {
        $user = $this->astronaut();

        $this->actingAs($user)->post('/check-in', $this->checkinPayload([
            'mood_rating' => 4, 'stress_rating' => 2, 'social_connectedness_rating' => 4,
            'planned_aerobic_minutes' => 30, 'completed_aerobic_minutes' => 30,
            'planned_resistive_minutes' => 20, 'completed_resistive_minutes' => 20,
            'symptom_tags' => ['headache'],
        ]))->assertRedirect('/dashboard');
        $this->assertDatabaseHas('daily_checkins', ['mood_rating' => 4, 'stress_rating' => 2]);
        $this->assertDatabaseHas('symptom_logs', ['tag' => 'headache']);

        $this->actingAs($user)->post('/readings', [
            'metric_code' => 'body_temperature', 'value' => 36.8,
            'observed_at' => now()->subMinute()->format('Y-m-d\TH:i'),
        ])->assertRedirect(route('readings.index', ['metric' => 'body_temperature']));
        $this->assertDatabaseHas('health_readings', ['value' => 36.8]);
    }

    public function test_astronaut_cannot_see_another_astronauts_readings(): void
    {
        $me = $this->astronaut('OC-T1');
        $other = $this->astronaut('OC-T2');
        HealthReading::create([
            'crew_profile_id' => $other->crewProfile->id,
            'metric_definition_id' => MetricDefinition::where('code', 'resting_heart_rate')->value('id'),
            'value' => 77.7, 'observed_at' => now()->subHour(), 'source' => 'manual',
        ]);

        $this->actingAs($me)->get('/readings')->assertOk()->assertDontSee('77.7');
    }

    public function test_alert_engine_creates_review_alert_for_declining_sleep_and_fatigue(): void
    {
        $user = $this->astronaut();
        $crew = $user->crewProfile()->firstOrFail();

        foreach ([2, 1, 0] as $offset) {
            DailyCheckin::create([
                'crew_profile_id' => $crew->id,
                'mission_date' => today()->subDays($offset)->toDateString(),
                'sleep_hours' => 5.5,
                'sleep_quality' => 3,
                'fatigue_rating' => 4,
                'exercise_minutes' => 20,
                'source' => 'manual',
                'submitted_at' => now()->subDays($offset),
            ]);
        }

        app(AlertEvaluationService::class)->evaluateCrew($crew);

        $this->assertDatabaseHas('alerts', ['crew_profile_id' => $crew->id, 'status' => 'open']);
        $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('Review');
    }

    public function test_alert_workflow_requires_sequential_review_and_closing_note(): void
    {
        $user = $this->astronaut();
        $alert = $this->triggeredAlertFor($user);

        $this->actingAs($user)
            ->post(route('alerts.update', ['alertId' => $alert->id]), ['action' => 'close', 'notes' => 'Too soon'])
            ->assertStatus(422);

        $this->actingAs($user)
            ->post(route('alerts.update', ['alertId' => $alert->id]), ['action' => 'acknowledge'])
            ->assertRedirect(route('alerts.show', ['alertId' => $alert->id]));
        $this->assertDatabaseHas('alerts', ['id' => $alert->id, 'status' => 'acknowledged']);
        app(AlertEvaluationService::class)->evaluateCrew($user->crewProfile()->firstOrFail());
        $this->assertDatabaseHas('alerts', ['id' => $alert->id, 'status' => 'acknowledged']);

        $this->actingAs($user)
            ->post(route('alerts.update', ['alertId' => $alert->id]), ['action' => 'start_review'])
            ->assertRedirect(route('alerts.show', ['alertId' => $alert->id]));
        $this->assertDatabaseHas('alerts', ['id' => $alert->id, 'status' => 'under_review']);
        app(AlertEvaluationService::class)->evaluateCrew($user->crewProfile()->firstOrFail());
        $this->assertDatabaseHas('alerts', ['id' => $alert->id, 'status' => 'under_review']);

        $this->actingAs($user)
            ->post(route('alerts.update', ['alertId' => $alert->id]), ['action' => 'close'])
            ->assertSessionHasErrors('notes');

        $this->actingAs($user)
            ->post(route('alerts.update', ['alertId' => $alert->id]), [
                'action' => 'close',
                'notes' => 'Reviewed the recorded trend and completed a follow-up check-in.',
            ])
            ->assertRedirect(route('alerts.show', ['alertId' => $alert->id]));

        $this->assertDatabaseHas('alerts', ['id' => $alert->id, 'status' => 'closed']);
        $this->assertDatabaseHas('followups', [
            'alert_id' => $alert->id,
            'action' => 'closed',
            'notes' => 'Reviewed the recorded trend and completed a follow-up check-in.',
        ]);
        $this->assertDatabaseCount('followups', 3);
    }

    public function test_astronaut_cannot_view_another_astronauts_alert(): void
    {
        $owner = $this->astronaut('OC-T1');
        $other = $this->astronaut('OC-T2');
        $alert = $this->triggeredAlertFor($other);

        $this->actingAs($owner)
            ->get(route('alerts.show', ['alertId' => $alert->id]))
            ->assertNotFound();
    }

    public function test_medical_officer_sees_only_assigned_crew(): void
    {
        $assignedAstronaut = $this->astronaut('OC-ASSIGNED');
        $unassignedAstronaut = $this->astronaut('OC-UNASSIGNED');
        $medicalOfficer = User::factory()->create(['role' => User::ROLE_MEDICAL_OFFICER, 'is_active' => true]);
        $medicalOfficer->assignedCrewProfiles()->attach($assignedAstronaut->crewProfile->id);

        $this->actingAs($medicalOfficer)
            ->get('/staff')
            ->assertOk()
            ->assertSee('OC-ASSIGNED')
            ->assertDontSee('OC-UNASSIGNED');

        $this->actingAs($medicalOfficer)
            ->get(route('staff.crew.show', ['crewProfileId' => $assignedAstronaut->crewProfile->id]))
            ->assertOk()
            ->assertSee('OC-ASSIGNED');

        $this->actingAs($medicalOfficer)
            ->get(route('staff.crew.show', ['crewProfileId' => $unassignedAstronaut->crewProfile->id]))
            ->assertNotFound();
    }

    public function test_administrator_workspace_does_not_expose_crew_health_records(): void
    {
        $this->astronaut('OC-PRIVATE');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);

        $this->actingAs($admin)
            ->get('/staff')
            ->assertOk()
            ->assertSee('Administrator workspace')
            ->assertDontSee('OC-PRIVATE');

        $this->actingAs($admin)
            ->get(route('staff.crew.show', ['crewProfileId' => CrewProfile::where('crew_code', 'OC-PRIVATE')->value('id')]))
            ->assertForbidden();
    }

    public function test_administrator_can_manage_account_state_but_not_crew_health_records(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);
        $astronaut = $this->astronaut('OC-ADMIN-CHECK');

        $this->actingAs($admin)->get(route('admin.index'))->assertOk()->assertSee('Mission administration')->assertDontSee('OC-ADMIN-CHECK');
        $this->actingAs($admin)->patch(route('admin.users.update', $astronaut), ['role' => 'astronaut', 'is_active' => 0])->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $astronaut->id, 'is_active' => 0]);
    }

    public function test_astronaut_report_and_csv_export_are_scoped_to_own_records(): void
    {
        $me = $this->astronaut('OC-REPORT-ME');
        $other = $this->astronaut('OC-REPORT-OTHER');
        $heartRateId = MetricDefinition::where('code', 'resting_heart_rate')->value('id');

        HealthReading::create([
            'crew_profile_id' => $me->crewProfile->id,
            'metric_definition_id' => $heartRateId,
            'value' => 55.5,
            'observed_at' => now()->subHour(),
            'source' => 'manual',
        ]);
        HealthReading::create([
            'crew_profile_id' => $other->crewProfile->id,
            'metric_definition_id' => $heartRateId,
            'value' => 99.5,
            'observed_at' => now()->subHour(),
            'source' => 'manual',
        ]);

        $this->actingAs($me)
            ->get(route('reports.self'))
            ->assertOk()
            ->assertSee('55.5')
            ->assertDontSee('99.5');

        $csvResponse = $this->actingAs($me)->get(route('exports.self'))->assertOk();
        $csv = $csvResponse->streamedContent();
        $this->assertStringContainsString('resting_heart_rate,55.5,bpm', $csv);
        $this->assertStringNotContainsString('99.5', $csv);
    }

    public function test_medical_officer_report_and_export_require_a_crew_assignment(): void
    {
        $crewUser = $this->astronaut('OC-REPORT-ASSIGN');
        $medicalOfficer = User::factory()->create(['role' => User::ROLE_MEDICAL_OFFICER, 'is_active' => true]);
        $crewId = $crewUser->crewProfile->id;

        $this->actingAs($medicalOfficer)
            ->get(route('reports.assigned', ['crewProfileId' => $crewId]))
            ->assertNotFound();
        $this->actingAs($medicalOfficer)
            ->get(route('exports.assigned', ['crewProfileId' => $crewId]))
            ->assertNotFound();

        $medicalOfficer->assignedCrewProfiles()->attach($crewId);
        $this->actingAs($medicalOfficer)
            ->get(route('reports.assigned', ['crewProfileId' => $crewId]))
            ->assertOk()
            ->assertSee('OC-REPORT-ASSIGN');
        $this->actingAs($medicalOfficer)
            ->get(route('exports.assigned', ['crewProfileId' => $crewId]))
            ->assertOk();
    }
}
