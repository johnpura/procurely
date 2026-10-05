<?php

namespace Tests\Feature;

use App\Helpers\Audit;
use App\Models\Bid;
use App\Models\BidResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_admin_stats_cover_every_bid(): void
    {
        $admin = User::factory()->admin()->create();
        Bid::factory()->count(2)->create();
        Bid::factory()->open()->create(['closes_at' => now()->addDays(3)]);
        Bid::factory()->count(2)->open()->create(['closes_at' => now()->addDays(20)]);
        Bid::factory()->count(2)->closed()->create();
        Bid::factory()->awarded()->create();

        $this->actingAs($admin)->get('/dashboard')->assertOk()
            ->assertViewHas('stats', fn ($s) => $s['drafts'] === 2
                && $s['open'] === 3
                && $s['closing_soon'] === 1
                && $s['awaiting'] === 2
                && $s['users'] === 1);
    }

    public function test_staff_only_see_their_own_drafts_and_assigned_bids(): void
    {
        $staff = User::factory()->create();
        Bid::factory()->create(['created_by' => $staff->id]);
        Bid::factory()->create();
        Bid::factory()->open()->create(['assigned_to' => $staff->id, 'closes_at' => now()->addDays(2)]);
        Bid::factory()->open()->create();
        Bid::factory()->closed()->create(['assigned_to' => $staff->id]);
        Bid::factory()->closed()->create();

        $this->actingAs($staff)->get('/dashboard')->assertOk()
            ->assertViewHas('stats', fn ($s) => $s['drafts'] === 1
                && $s['open'] === 1
                && $s['closing_soon'] === 1
                && $s['awaiting'] === 1
                && $s['users'] === null);
    }

    public function test_closed_bids_awaiting_an_award_include_response_counts(): void
    {
        $admin = User::factory()->admin()->create();
        $bid = Bid::factory()->closed()->create();
        BidResponse::factory()->count(3)->for($bid)->create();
        Bid::factory()->awarded()->create();

        $this->actingAs($admin)->get('/dashboard')
            ->assertViewHas('awaitingList', fn ($list) => $list->count() === 1 && $list->first()->responses_count === 3);
    }

    public function test_bids_closing_after_a_week_are_not_listed_as_closing_soon(): void
    {
        $admin = User::factory()->admin()->create();
        Bid::factory()->open()->create(['closes_at' => now()->addDays(30)]);

        $this->actingAs($admin)->get('/dashboard')
            ->assertViewHas('closingList', fn ($list) => $list->isEmpty());
    }

    public function test_recent_activity_is_for_admins_only(): void
    {
        Audit::record('test.action', 'Marker entry for the dashboard');

        $this->actingAs(User::factory()->admin()->create())->get('/dashboard')->assertSee('Marker entry for the dashboard');
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertDontSee('Marker entry for the dashboard');
    }

    public function test_admins_are_warned_about_open_bids_without_a_contact(): void
    {
        $admin = User::factory()->admin()->create();
        Bid::factory()->open()->create();

        $this->actingAs($admin)->get('/dashboard')->assertSee('no assigned contact');
    }
}
