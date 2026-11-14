<?php

namespace Tests\Feature;

use App\Models\ChatbotMessage;
use App\Models\CrewProfile;
use App\Models\Mission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatbotOversightTest extends TestCase
{
    use RefreshDatabase;

    private function crew(string $code): CrewProfile
    {
        $mission = Mission::firstOrCreate(['name' => 'Oversight mission'], ['starts_on' => today()->subDays(10)]);
        $user = User::factory()->create(['role' => User::ROLE_ASTRONAUT, 'is_active' => true]);

        return CrewProfile::create(['user_id' => $user->id, 'mission_id' => $mission->id, 'crew_code' => $code]);
    }

    private function log(CrewProfile $crew, string $clientId, string $role, string $message, ?string $intent): void
    {
        // astronaut_id is not mass-assignable on ChatbotMessage, so the fixture uses forceCreate.
        ChatbotMessage::forceCreate([
            'astronaut_id' => $crew->user_id,
            'client_id' => $clientId,
            'role' => $role,
            'message' => $message,
            'intent' => $intent,
            'created_at' => now()->subDay(),
        ]);
    }

    public function test_astronauts_cannot_open_the_assistant_activity_page(): void
    {
        $crew = $this->crew('OC-SELF');

        $this->actingAs($crew->user)->get(route('staff.chatbot'))->assertForbidden();
    }

    public function test_medical_officer_sees_topic_counts_for_assigned_crew_only_without_message_text(): void
    {
        $assigned = $this->crew('OC-ASSIGNED');
        $unassigned = $this->crew('OC-HIDDEN');
        $officer = User::factory()->create(['role' => User::ROLE_MEDICAL_OFFICER, 'is_active' => true]);
        $officer->assignedCrewProfiles()->attach($assigned->id);

        $this->log($assigned, 'a-1', 'user', 'PRIVATE-QUESTION-MARKER', null);
        $this->log($assigned, 'a-2', 'assistant', 'Sleep answer', 'cabin_co2');
        $this->log($assigned, 'a-3', 'user', 'Chest pain now', null);
        $this->log($assigned, 'a-4', 'assistant', 'EMERGENCY text', 'emergency');
        $this->log($unassigned, 'u-1', 'user', 'Other crew question', null);
        $this->log($unassigned, 'u-2', 'assistant', 'Other answer', 'emergency');

        $response = $this->actingAs($officer)->get(route('staff.chatbot'));

        $response->assertOk()
            ->assertSee('OC-ASSIGNED')
            ->assertSee('Cabin co2')
            ->assertDontSee('OC-HIDDEN')
            ->assertDontSee('PRIVATE-QUESTION-MARKER')
            ->assertDontSee('Chest pain now')
            ->assertDontSee('EMERGENCY text');
    }

    public function test_admin_sees_aggregate_totals_only_without_crew_identifiers_or_message_text(): void
    {
        $crew = $this->crew('OC-ADMIN-CHECK');
        $this->log($crew, 'x-1', 'user', 'ADMIN-HIDDEN-MARKER', null);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);

        $this->actingAs($admin)->get(route('staff.chatbot'))
            ->assertOk()
            ->assertViewHas('isAdmin', true)
            ->assertViewHas('rows', fn ($rows) => $rows->isEmpty())
            ->assertViewHas('aggregate', fn ($aggregate) => $aggregate['questions'] === 1)
            ->assertDontSee('OC-ADMIN-CHECK')
            ->assertDontSee('ADMIN-HIDDEN-MARKER');
    }
}
