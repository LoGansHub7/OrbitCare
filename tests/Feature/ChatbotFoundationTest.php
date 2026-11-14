<?php

namespace Tests\Feature;

use App\Models\CrewProfile;
use App\Models\DailyCheckin;
use App\Models\HealthReading;
use App\Models\MetricDefinition;
use App\Models\Mission;
use App\Models\User;
use Database\Seeders\MetricDefinitionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatbotFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MetricDefinitionSeeder::class);
    }

    private function astronaut(string $code): User
    {
        $mission = Mission::firstOrCreate(['name' => 'Test mission'], ['starts_on' => today()->subDays(10)]);
        $user = User::factory()->create(['role' => User::ROLE_ASTRONAUT, 'is_active' => true]);
        CrewProfile::create(['user_id' => $user->id, 'mission_id' => $mission->id, 'crew_code' => $code]);

        return $user;
    }

    private function message(string $id): array
    {
        return [
            'client_id' => $id,
            'role' => 'user',
            'message' => 'Hello ORBIT',
            'intent' => null,
            'created_at' => now()->toIso8601String(),
        ];
    }

    public function test_snapshot_contains_only_the_authenticated_astronauts_latest_data(): void
    {
        $user = $this->astronaut('OC-A');
        $other = $this->astronaut('OC-B');
        $metric = MetricDefinition::where('code', 'resting_heart_rate')->firstOrFail();
        foreach ([[$user, 65], [$other, 95]] as [$owner, $value]) {
            HealthReading::create([
                'crew_profile_id' => $owner->crewProfile->id,
                'metric_definition_id' => $metric->id,
                'value' => $value,
                'observed_at' => now()->subHour(),
                'source' => 'manual',
            ]);
        }
        DailyCheckin::create([
            'crew_profile_id' => $user->crewProfile->id,
            'mission_date' => today(),
            'sleep_hours' => 7.5,
            'sleep_quality' => 4,
            'fatigue_rating' => 2,
            'exercise_minutes' => 30,
            'source' => 'manual',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson(route('chatbot.snapshot'));
        $response->assertOk()->assertJsonPath('astronaut_id', $user->id)
            ->assertJsonPath('readings.0.value', 65)
            ->assertJsonPath('readings.0.freshness_hours', 36)
            ->assertJsonPath('checkin.sleep_hours', 7.5)
            ->assertJsonPath('checkin.freshness_hours.sleep_duration', 36)
            ->assertJsonMissing(['value' => 95]);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_sync_is_idempotent_and_refuses_a_different_owner(): void
    {
        $user = $this->astronaut('OC-A');
        $other = $this->astronaut('OC-B');
        $id = 'a135d701-283e-438a-bf14-267c2449b211';
        $payload = ['astronaut_id' => $user->id, 'messages' => [$this->message($id)]];

        $this->actingAs($user)->postJson(route('chatbot.sync'), $payload)->assertOk()
            ->assertJsonPath('synced.0', $id);
        $this->actingAs($user)->postJson(route('chatbot.sync'), $payload)->assertOk();
        $this->assertDatabaseCount('chatbot_messages', 1);
        $this->actingAs($other)->postJson(route('chatbot.sync'), $payload)->assertUnprocessable();
        $this->assertDatabaseCount('chatbot_messages', 1);
    }

    public function test_invalid_messages_and_unauthorised_accounts_are_rejected(): void
    {
        $user = $this->astronaut('OC-A');
        $staff = User::factory()->create(['role' => User::ROLE_MEDICAL_OFFICER, 'is_active' => true]);

        $this->getJson(route('chatbot.snapshot'))->assertUnauthorized();
        $this->actingAs($staff)->getJson(route('chatbot.snapshot'))->assertForbidden();
        $this->actingAs($staff)->postJson(route('chatbot.sync'), [])->assertForbidden();
        $this->actingAs($user)->postJson(route('chatbot.sync'), [
            'astronaut_id' => $user->id,
            'messages' => [array_merge($this->message('not-a-uuid'), ['role' => 'system'])],
        ])->assertUnprocessable()->assertJsonValidationErrors(['messages.0.client_id', 'messages.0.role']);
        $this->assertDatabaseCount('chatbot_messages', 0);
    }

    public function test_assistant_page_is_available_to_astronauts_only(): void
    {
        $user = $this->astronaut('OC-A');
        $staff = User::factory()->create(['role' => User::ROLE_MEDICAL_OFFICER, 'is_active' => true]);

        $this->actingAs($user)->get(route('assistant.show'))->assertOk()
            ->assertSee('data-orbit-root', false)
            ->assertSee('data-mode="page"', false);
        $this->actingAs($staff)->get(route('assistant.show'))->assertForbidden();
    }
}
