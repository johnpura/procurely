<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_from_staff_pages(): void
    {
        $this->get('/staff')->assertRedirect('/login');
        $this->get('/staff/create')->assertRedirect('/login');
        $this->post('/staff', [])->assertRedirect('/login');
    }

    public function test_staff_users_cannot_access_staff_management(): void
    {
        $staff = User::factory()->create();

        $this->actingAs($staff)->get('/staff')->assertForbidden();
        $this->actingAs($staff)->get('/staff/create')->assertForbidden();
    }

    public function test_staff_users_cannot_create_users(): void
    {
        $staff = User::factory()->create();

        $this->actingAs($staff)->post('/staff', $this->validPayload())->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'new@example.com']);
    }

    public function test_admins_can_view_staff_management(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/staff')->assertOk();
        $this->actingAs($admin)->get('/staff/create')->assertOk();
    }

    public function test_admins_can_create_a_staff_user(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/staff', $this->validPayload())
            ->assertRedirect('/staff');

        $this->assertDatabaseHas('users', [
            'email' => 'new@example.com',
            'role' => 'staff',
        ]);

        $created = User::where('email', 'new@example.com')->first();
        $this->assertTrue(Hash::check('Str0ng-Passw0rd!', $created->password));
        $this->assertNotNull($created->email_verified_at);
    }

    public function test_admins_can_create_another_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/staff', $this->validPayload(['role' => 'admin']))
            ->assertRedirect('/staff');

        $this->assertTrue(User::where('email', 'new@example.com')->first()->isAdmin());
    }

    public function test_creating_a_user_validates_input(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($admin)
            ->post('/staff', $this->validPayload([
                'email' => 'taken@example.com',
                'role' => 'superuser',
                'password' => 'short',
                'password_confirmation' => 'different',
            ]))
            ->assertSessionHasErrors(['email', 'role', 'password']);
    }

    public function test_dashboard_is_available_to_both_roles(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertOk();
        $this->actingAs(User::factory()->admin()->create())->get('/dashboard')->assertOk();
    }

    public function test_staff_menu_link_is_only_shown_to_admins(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/dashboard')
            ->assertSee('href="/staff"', false);

        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertDontSee('href="/staff"', false);
    }

    public function test_public_registration_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', $this->validPayload())->assertNotFound();
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'New Person',
            'email' => 'new@example.com',
            'password' => 'Str0ng-Passw0rd!',
            'password_confirmation' => 'Str0ng-Passw0rd!',
            'role' => 'staff',
        ], $overrides);
    }
}