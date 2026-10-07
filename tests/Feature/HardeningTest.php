<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_pages_show_the_branded_404(): void
    {
        $this->get('/nothing-here')->assertNotFound()
            ->assertSee('Page not found')->assertSee('Back to the bid board');
    }

    public function test_forbidden_pages_show_the_branded_403(): void
    {
        $this->actingAs(User::factory()->create())->get('/staff')
            ->assertForbidden()->assertSee('not allowed');
    }

    public function test_security_headers_are_sent(): void
    {
        $this->get('/')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Content-Security-Policy');
    }

    public function test_the_csp_can_be_switched_to_report_only_or_off(): void
    {
        config(['procurely.csp' => 'report']);
        $this->get('/')->assertHeader('Content-Security-Policy-Report-Only')->assertHeaderMissing('Content-Security-Policy');

        config(['procurely.csp' => 'off']);
        $this->get('/')->assertHeaderMissing('Content-Security-Policy')->assertHeaderMissing('Content-Security-Policy-Report-Only');
    }

    public function test_the_public_layout_has_a_skip_link_and_main_landmark(): void
    {
        $this->get('/')->assertSee('Skip to main content')->assertSee('id="main"', false);
    }

    public function test_repeated_failed_logins_lock_the_account_out_temporarily(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $i) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_weak_passwords_are_rejected_for_new_users(): void
    {
        $admin = User::factory()->admin()->create();
        $base = ['name' => 'New Person', 'email' => 'new@example.gov', 'role' => 'staff'];

        foreach (['short1A', 'alllowercase1234', 'NoNumbersHereAtAll'] as $weak) {
            $this->actingAs($admin)->post('/staff', $base + ['password' => $weak, 'password_confirmation' => $weak])
                ->assertSessionHasErrors('password');
        }

        $this->actingAs($admin)->post('/staff', $base + ['password' => 'Str0ng-Passw0rd!', 'password_confirmation' => 'Str0ng-Passw0rd!'])
            ->assertSessionHasNoErrors();
    }

    public function test_the_current_nav_link_is_marked_for_assistive_technology(): void
    {
        $this->get('/bids/open')->assertSee('aria-current="page"', false);
        $this->get('/bids/open')->assertSee('Open bids');
        $this->assertSame(1, substr_count($this->get('/bids/open')->getContent(), 'aria-current="page"'));
    }
}
