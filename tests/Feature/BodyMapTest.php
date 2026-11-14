<?php

namespace Tests\Feature;

use App\Models\CrewProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BodyMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_reach_body_map(): void
    {
        $this->get(route('body-map.index'))->assertRedirect(route('login'));
        $this->getJson(route('body-map.api.all'))->assertUnauthorized();
    }

    public function test_astronaut_sees_body_map_page_with_all_seeded_regions(): void
    {
        $this->seed();
        $crew = CrewProfile::where('crew_code', 'OC-01')->firstOrFail();

        $response = $this->actingAs($crew->user)->get(route('body-map.index'));

        $response->assertOk();
        $response->assertSee('Body Map');
        $response->assertSee('Heart');
    }

    public function test_api_all_returns_regions_with_status_levels(): void
    {
        $this->seed();
        $crew = CrewProfile::where('crew_code', 'OC-01')->firstOrFail();

        $response = $this->actingAs($crew->user)->getJson(route('body-map.api.all'));

        $response->assertOk();
        $response->assertJsonStructure([
            'mission_day',
            'regions' => [['slug', 'name', 'status_level', 'metrics', 'hazards', 'guidance']],
        ]);
        $this->assertCount(11, $response->json('regions'));
    }

    public function test_api_show_returns_single_region_with_metric_history(): void
    {
        $this->seed();
        $crew = CrewProfile::where('crew_code', 'OC-01')->firstOrFail();

        $response = $this->actingAs($crew->user)->getJson(route('body-map.api.show', 'heart'));

        $response->assertOk();
        $response->assertJsonStructure(['slug', 'name', 'metric_history']);
        $response->assertJsonPath('slug', 'heart');
    }

    public function test_api_show_returns_404_for_unknown_slug(): void
    {
        $this->seed();
        $crew = CrewProfile::where('crew_code', 'OC-01')->firstOrFail();

        $this->actingAs($crew->user)->getJson(route('body-map.api.show', 'not-a-region'))->assertNotFound();
    }

    public function test_body_map_status_agrees_with_alert_engine_for_declining_crew(): void
    {
        $this->seed();
        $crew = CrewProfile::where('crew_code', 'OC-02')->firstOrFail();

        $alerts = app(\App\Services\AlertEvaluationService::class);
        $states = $alerts->currentStates($crew, true);

        $response = $this->actingAs($crew->user)->getJson(route('body-map.api.all'));
        $headRegion = collect($response->json('regions'))->firstWhere('slug', 'head-brain');

        // Sleep/fatigue rule triggering must be reflected as non-nominal on the linked region.
        if (($states['sleep_fatigue_review'] ?? null) === 'triggered') {
            $this->assertNotSame('nominal', $headRegion['status_level']);
        }
    }

    public function test_staff_cannot_reach_astronaut_body_map(): void
    {
        $this->seed();
        $officer = \App\Models\User::where('role', 'medical_officer')->firstOrFail();

        $this->actingAs($officer)->get(route('body-map.index'))->assertForbidden();
    }
}
