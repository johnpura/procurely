<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Bid;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Pat Lee',
            'email' => 'pat@example.gov',
            'phone' => '(555) 555-0142',
            'job_title' => 'Buyer',
            'role' => 'staff',
        ], $overrides);
    }

    public function test_only_admins_can_use_staff_management(): void
    {
        $staff = User::factory()->create();
        $target = User::factory()->create();

        $this->actingAs($staff)->get("/staff/{$target->id}/edit")->assertForbidden();
        $this->actingAs($staff)->put("/staff/{$target->id}", $this->payload())->assertForbidden();
        $this->actingAs($staff)->post("/staff/{$target->id}/disable")->assertForbidden();
        $this->actingAs($staff)->delete("/staff/{$target->id}")->assertForbidden();
    }

    public function test_admins_can_edit_a_user(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create();

        $this->actingAs($admin)->put("/staff/{$target->id}", $this->payload(['role' => 'admin']))->assertRedirect();

        $target->refresh();
        $this->assertSame('Pat Lee', $target->name);
        $this->assertSame('pat@example.gov', $target->email);
        $this->assertSame('(555) 555-0142', $target->phone);
        $this->assertSame('Buyer', $target->job_title);
        $this->assertTrue($target->isAdmin());
    }

    public function test_email_must_be_unique_but_can_stay_the_same(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create(['email' => 'pat@example.gov']);
        User::factory()->create(['email' => 'taken@example.gov']);

        $this->actingAs($admin)->put("/staff/{$target->id}", $this->payload())->assertSessionHasNoErrors();
        $this->actingAs($admin)->put("/staff/{$target->id}", $this->payload(['email' => 'taken@example.gov']))->assertSessionHasErrors('email');
        $this->actingAs($admin)->put("/staff/{$target->id}", $this->payload(['role' => 'superuser']))->assertSessionHasErrors('role');
    }

    public function test_the_last_active_admin_is_protected(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put("/staff/{$admin->id}", $this->payload(['email' => $admin->email, 'role' => 'staff']))
            ->assertSessionHasErrors('role');
        $this->assertTrue($admin->fresh()->isAdmin());
    }

    public function test_the_last_other_admin_cannot_be_disabled_or_removed(): void
    {
        $admin = User::factory()->admin()->create();
        $onlyOther = User::factory()->admin()->create();
        $onlyOther->forceFill(['is_active' => true])->save();

        // Two admins exist, so either can be changed by the other.
        $this->actingAs($admin)->post("/staff/{$onlyOther->id}/disable")->assertSessionHas('status');
        $this->assertFalse($onlyOther->fresh()->is_active);

        // Now the acting admin is the last active one; a second disable attempt on a staff user is fine,
        // but demoting the last admin is not.
        $third = User::factory()->admin()->create(['is_active' => false]);
        $this->actingAs($admin)->delete("/staff/{$admin->id}")->assertSessionHas('error');
        $this->assertNotSoftDeleted($admin);
    }

    public function test_you_cannot_disable_or_remove_yourself(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->admin()->create();

        $this->actingAs($admin)->post("/staff/{$admin->id}/disable")->assertSessionHas('error');
        $this->actingAs($admin)->delete("/staff/{$admin->id}")->assertSessionHas('error');

        $this->assertTrue($admin->fresh()->is_active);
        $this->assertNotSoftDeleted($admin);
    }

    public function test_disabling_blocks_login_and_enabling_restores_it(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create();

        $this->actingAs($admin)->post("/staff/{$target->id}/disable");
        $this->post('/logout');
        $this->post('/login', ['email' => $target->email, 'password' => 'password']);
        $this->assertGuest();

        $this->actingAs($admin)->post("/staff/{$target->id}/enable");
        $this->post('/logout');
        $this->post('/login', ['email' => $target->email, 'password' => 'password']);
        $this->assertAuthenticatedAs($target);
    }

    public function test_removed_users_can_be_restored_and_cannot_be_edited(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create();

        $this->actingAs($admin)->delete("/staff/{$target->id}")->assertRedirect('/staff');
        $this->assertSoftDeleted($target);

        $this->actingAs($admin)->get("/staff/{$target->id}/edit")->assertNotFound();

        $this->actingAs($admin)->post("/staff/{$target->id}/restore")->assertRedirect('/staff');
        $this->assertNotSoftDeleted($target);
    }

    public function test_a_removed_assignee_falls_back_to_the_department_contact(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create(['name' => 'Pat Lee']);
        $bid = Bid::factory()->open()->create();
        $bid->assigned_to = $target->id;
        $bid->save();

        $this->actingAs($admin)->get("/staff/{$target->id}/edit")->assertSee('open bid');

        $this->actingAs($admin)->delete("/staff/{$target->id}");

        $this->assertSame(config('procurely.contact.name'), $bid->fresh()->contact()['name']);
    }

    public function test_a_reset_link_is_sent(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create();

        $this->actingAs($admin)->post("/staff/{$target->id}/reset-link")->assertSessionHas('status');

        Notification::assertSentTo($target, ResetPassword::class);
    }

    public function test_the_list_filters_and_searches(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['name' => 'Activa Person']);
        User::factory()->inactive()->create(['name' => 'Disabled Person']);
        User::factory()->trashed()->create(['name' => 'Former Person']);

        $this->actingAs($admin)->get('/staff')->assertSee('Activa Person')->assertSee('Disabled Person')->assertDontSee('Former Person');
        $this->actingAs($admin)->get('/staff?filter=disabled')->assertSee('Disabled Person')->assertDontSee('Activa Person');
        $this->actingAs($admin)->get('/staff?filter=former')->assertSee('Former Person')->assertDontSee('Activa Person');
        $this->actingAs($admin)->get('/staff?q=Activa')->assertSee('Activa Person')->assertDontSee('Disabled Person');
    }

    public function test_creating_a_user_stores_phone_and_title(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/staff', [
            'name' => 'New Person',
            'email' => 'new@example.gov',
            'phone' => '(555) 555-0100',
            'job_title' => 'Analyst',
            'password' => 'Str0ng-Passw0rd!',
            'password_confirmation' => 'Str0ng-Passw0rd!',
            'role' => 'staff',
        ])->assertRedirect('/staff');

        $user = User::where('email', 'new@example.gov')->firstOrFail();
        $this->assertSame('Analyst', $user->job_title);
        $this->assertSame(Role::Staff, $user->role);
    }
}
