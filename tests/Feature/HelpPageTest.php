<?php

namespace Tests\Feature;

use App\Helpers\MenuHelper;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/help')->assertRedirect('/login');
    }

    public function test_staff_do_not_see_admin_sections(): void
    {
        $this->actingAs(User::factory()->create())->get('/help')->assertOk()
            ->assertSee('Drafting a bid')
            ->assertDontSee('Assigning the contact and publishing')
            ->assertDontSee('Managing people')
            ->assertDontSee('The audit log');
    }

    public function test_admins_see_everything(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/help')->assertOk()
            ->assertSee('Drafting a bid')
            ->assertSee('Assigning the contact and publishing')
            ->assertSee('Awarding or cancelling a bid')
            ->assertSee('Managing people')
            ->assertSee('The audit log');
    }

    public function test_every_menu_item_a_person_sees_is_explained_on_the_page(): void
    {
        foreach ([User::factory()->create(), User::factory()->admin()->create()] as $user) {
            $this->actingAs($user);
            $page = $this->get('/help');

            foreach (MenuHelper::getAppMenuGroups() as $group) {
                foreach ($group['items'] as $item) {
                    $page->assertSee($item['name']);
                }
            }
        }
    }

    public function test_the_menu_links_to_help(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertSee('href="/help"', false);
    }
}
