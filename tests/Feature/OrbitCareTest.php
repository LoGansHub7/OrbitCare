<?php

namespace Tests\Feature;

use App\Models\CrewProfile;
use App\Models\HealthReading;
use App\Models\MetricDefinition;
use App\Models\Mission;
use App\Models\User;
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
}
