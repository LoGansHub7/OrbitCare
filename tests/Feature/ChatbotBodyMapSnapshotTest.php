<?php

namespace Tests\Feature;

use App\Models\CrewProfile;
use App\Models\Mission;
use App\Models\RadiationReading;
use App\Models\User;
use Database\Seeders\AlertRuleSeeder;
use Database\Seeders\BodyRegionSeeder;
use Database\Seeders\HazardSeeder;
use Database\Seeders\MetricDefinitionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatbotBodyMapSnapshotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([HazardSeeder::class, MetricDefinitionSeeder::class, AlertRuleSeeder::class, BodyRegionSeeder::class]);
    }

    private function astronaut(string $code): User
    {
        $mission = Mission::firstOrCreate(['name' => 'Test mission'], ['starts_on' => today()->subDays(10)]);
        $user = User::factory()->create(['role' => User::ROLE_ASTRONAUT, 'is_active' => true]);
        CrewProfile::create(['user_id' => $user->id, 'mission_id' => $mission->id, 'crew_code' => $code]);

        return $user;
    }

    private function dose(User $user, float $rate): void
    {
        RadiationReading::create([
            'crew_profile_id' => $user->crewProfile->id, 'mission_id' => $user->crewProfile->mission_id,
            'dose_rate' => $rate, 'cumulative_dose' => 10, 'recorded_at' => now()->subHour(), 'is_simulated' => true,
        ]);
    }

    public function test_snapshot_carries_every_body_region_with_guidance_for_all_levels(): void
    {
        $user = $this->astronaut('OC-A');

        $regions = $this->actingAs($user)->getJson(route('chatbot.snapshot'))->assertOk()->json('body_regions');

        $this->assertCount(11, $regions);
        foreach ($regions as $region) {
            $this->assertContains($region['status_level'], ['nominal', 'watch', 'review', 'urgent']);
            $this->assertEqualsCanonicalizing(['nominal', 'watch', 'review', 'urgent'], array_keys($region['guidance_levels']), $region['slug']);
            foreach ($region['guidance_levels'] as $level => $guidance) {
                $this->assertNotEmpty($guidance['self_care_steps'], "{$region['slug']} {$level} steps");
                $this->assertNotEmpty($guidance['red_flags'], "{$region['slug']} {$level} red flags");
                $this->assertNotEmpty($guidance['escalate_to'], "{$region['slug']} {$level} escalation");
            }
            $this->assertSame($region['guidance']['escalate_to'], $region['guidance_levels'][$region['status_level']]['escalate_to']);
        }
        $this->assertContains('whole-body-radiation', array_column($regions, 'slug'));
    }

    public function test_snapshot_body_regions_only_use_the_signed_in_astronauts_readings(): void
    {
        $user = $this->astronaut('OC-A');
        $other = $this->astronaut('OC-B');
        $this->dose($user, 0.5);
        $this->dose($other, 0.9123);

        $response = $this->actingAs($user)->getJson(route('chatbot.snapshot'))->assertOk();

        $whole = collect($response->json('body_regions'))->firstWhere('slug', 'whole-body-radiation');
        $this->assertEquals(0.5, $whole['metrics'][0]['value']);
        $this->assertSame('simulation', $whole['metrics'][0]['source']);
        $this->assertStringNotContainsString('0.9123', $response->getContent());
    }

    public function test_guests_and_staff_cannot_read_the_snapshot_body_regions(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_MEDICAL_OFFICER, 'is_active' => true]);

        $this->getJson(route('chatbot.snapshot'))->assertUnauthorized();
        $this->actingAs($staff)->getJson(route('chatbot.snapshot'))->assertForbidden();
    }
}
