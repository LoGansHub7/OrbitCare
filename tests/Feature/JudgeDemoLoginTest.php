<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JudgeDemoLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_judge_demo_login_is_unavailable_without_the_explicit_flag(): void
    {
        $this->post(route('judge-demo.login', ['account' => 'astronaut']))->assertNotFound();
    }

    public function test_judge_demo_login_authenticates_only_the_configured_active_account(): void
    {
        config()->set('orbitcare.judge_demo', true);
        $user = User::factory()->create([
            'email' => 'astro2@orbitcare.test',
            'role' => User::ROLE_ASTRONAUT,
            'is_active' => true,
        ]);

        $this->post(route('judge-demo.login', ['account' => 'astronaut']))
            ->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('audit_events', ['actor_id' => $user->id, 'action' => 'auth.judge_demo_login']);
    }
}
