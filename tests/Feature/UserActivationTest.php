<?php

namespace Tests\Feature;

use App\Models\Bid;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_users_cannot_log_in(): void
    {
        $user = User::factory()->inactive()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_active_users_can_log_in(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->assertAuthenticatedAs($user);
    }

    public function test_deactivating_a_signed_in_user_logs_them_out_on_the_next_request(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/dashboard')->assertOk();

        $user->is_active = false;
        $user->save();

        $this->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_created_by_is_recorded_when_an_admin_creates_a_user(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/staff', [
            'name' => 'New Person',
            'email' => 'new@example.com',
            'password' => 'Str0ng-Passw0rd!',
            'password_confirmation' => 'Str0ng-Passw0rd!',
            'role' => 'staff',
        ]);

        $this->assertSame($admin->id, User::where('email', 'new@example.com')->first()->created_by);
    }

    public function test_soft_deleted_users_cannot_log_in(): void
    {
        $user = User::factory()->trashed()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_soft_deleted_users_can_be_restored(): void
    {
        $user = User::factory()->create();
        $user->delete();

        $this->assertSoftDeleted($user);

        $user->restore();

        $this->assertNotSoftDeleted($user);
    }

    public function test_a_departed_assignee_falls_back_to_the_department_contact(): void
    {
        $assignee = User::factory()->create(['name' => 'Pat Lee']);
        $bid = Bid::factory()->open()->create();
        $bid->assigned_to = $assignee->id;
        $bid->save();

        $this->assertSame('Pat Lee', $bid->fresh()->contact()['name']);

        $assignee->delete();

        $this->assertSame(config('procurely.contact.name'), $bid->fresh()->contact()['name']);
    }
}
