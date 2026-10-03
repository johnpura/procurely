<?php

namespace Tests\Feature;

use App\Models\Bid;
use App\Models\BidDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicBidPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_shows_counts_and_staff_sign_in_to_guests(): void
    {
        Bid::factory()->count(2)->open()->create();

        $this->get('/')->assertOk()->assertSee('Open bids')->assertSee('Staff sign in')->assertDontSee('/register', false);
    }

    public function test_signed_in_staff_see_a_dashboard_link_instead(): void
    {
        $this->actingAs(User::factory()->create())->get('/')->assertOk()->assertSee('Dashboard')->assertDontSee('Staff sign in');
    }

    public function test_open_page_lists_only_open_bids(): void
    {
        $open = Bid::factory()->open()->create(['title' => 'Open thing']);
        Bid::factory()->closed()->create(['title' => 'Closed thing']);
        Bid::factory()->create(['title' => 'Draft thing']);

        $this->get('/bids/open')->assertOk()->assertSee($open->reference_number)->assertDontSee('Closed thing')->assertDontSee('Draft thing');
    }

    public function test_closed_page_lists_closed_awarded_and_cancelled_but_not_open_or_drafts(): void
    {
        Bid::factory()->closed()->create(['title' => 'Closed thing']);
        Bid::factory()->awarded()->create(['title' => 'Awarded thing', 'awarded_to' => 'Acme Paving']);
        Bid::factory()->cancelled()->create(['title' => 'Cancelled thing']);
        Bid::factory()->open()->create(['title' => 'Open thing']);
        Bid::factory()->create(['title' => 'Draft thing']);

        $this->get('/bids/closed')->assertOk()
            ->assertSee('Closed thing')->assertSee('Awarded thing')->assertSee('Acme Paving')->assertSee('Cancelled thing')
            ->assertDontSee('Open thing')->assertDontSee('Draft thing');
    }

    public function test_search_by_keyword_reference_department_and_status(): void
    {
        $roads = Bid::factory()->open()->create(['title' => 'Road resurfacing', 'department' => 'Public Works']);
        Bid::factory()->open()->create(['title' => 'Server hardware', 'department' => 'Information Technology']);
        Bid::factory()->closed()->create(['title' => 'Old road job', 'department' => 'Public Works']);
        Bid::factory()->create(['title' => 'Secret road draft', 'department' => 'Public Works']);

        $this->get('/bids/search?q=road')->assertSee('Road resurfacing')->assertSee('Old road job')->assertDontSee('Server hardware')->assertDontSee('Secret road draft');
        $this->get('/bids/search?q='.$roads->reference_number)->assertSee('Road resurfacing')->assertDontSee('Old road job');
        $this->get('/bids/search?department=Information+Technology')->assertSee('Server hardware')->assertDontSee('Road resurfacing');
        $this->get('/bids/search?status=open')->assertSee('Road resurfacing')->assertDontSee('Old road job');
    }

    public function test_search_treats_percent_signs_literally(): void
    {
        Bid::factory()->open()->create(['title' => 'Plain title']);

        $this->get('/bids/search?q=%25')->assertOk()->assertDontSee('Plain title');
    }

    public function test_search_validates_the_date_range(): void
    {
        $this->get('/bids/search?from=2026-05-02&to=2026-05-01')->assertSessionHasErrors('to');
    }

    public function test_published_bid_detail_shows_facts_and_fallback_contact(): void
    {
        $bid = Bid::factory()->open()->create(['title' => 'Fleet vehicles']);

        $this->get('/bids/'.$bid->reference_number)->assertOk()
            ->assertSee('Fleet vehicles')->assertSee(config('procurely.contact.email'));
    }

    public function test_draft_bids_return_404(): void
    {
        $draft = Bid::factory()->create();

        $this->get('/bids/'.$draft->reference_number)->assertNotFound();
    }

    public function test_documents_download_only_for_visible_bids_and_matching_bid(): void
    {
        Storage::fake('local');
        $open = Bid::factory()->open()->create();
        $draft = Bid::factory()->create();

        Storage::disk('local')->put('bids/a.pdf', 'pdf-a');
        Storage::disk('local')->put('bids/b.pdf', 'pdf-b');

        $docOpen = BidDocument::create(['bid_id' => $open->id, 'name' => 'RFP', 'path' => 'bids/a.pdf', 'original_name' => 'rfp.pdf', 'size' => 5]);
        $docDraft = BidDocument::create(['bid_id' => $draft->id, 'name' => 'Draft RFP', 'path' => 'bids/b.pdf', 'original_name' => 'draft.pdf', 'size' => 5]);

        $this->get("/bids/{$open->reference_number}/documents/{$docOpen->id}")->assertOk()->assertDownload('rfp.pdf');
        $this->get("/bids/{$draft->reference_number}/documents/{$docDraft->id}")->assertNotFound();
        $this->get("/bids/{$open->reference_number}/documents/{$docDraft->id}")->assertNotFound();
    }
}