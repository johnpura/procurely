<?php

namespace Tests\Feature;

use App\Enums\ResponseMethod;
use App\Mail\ResponseReceived;
use App\Models\Bid;
use App\Models\BidResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VendorResponseTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'vendor_name' => 'Acme Paving',
            'contact_name' => 'Sam Vendor',
            'contact_email' => 'sam@acme.example',
            'contact_phone' => '(555) 555-0123',
            'cover_note' => 'We would like to bid.',
            'files' => [UploadedFile::fake()->create('proposal.pdf', 200, 'application/pdf')],
            'acknowledge' => '1',
        ], $overrides);
    }

    public function test_the_form_is_available_only_for_open_bids(): void
    {
        $open = Bid::factory()->open()->create();
        $closed = Bid::factory()->closed()->create();
        $draft = Bid::factory()->create();

        $this->get("/bids/{$open->reference_number}/respond")->assertOk()->assertSee('Submit a response');
        $this->get("/bids/{$closed->reference_number}/respond")->assertRedirect("/bids/{$closed->reference_number}");
        $this->get("/bids/{$draft->reference_number}/respond")->assertNotFound();
    }

    public function test_the_bid_page_offers_online_submission_only_while_open(): void
    {
        $open = Bid::factory()->open()->create();
        $closed = Bid::factory()->closed()->create();

        $this->get("/bids/{$open->reference_number}")->assertSee('Submit a response online');
        $this->get("/bids/{$closed->reference_number}")->assertDontSee('Submit a response online');
    }

    public function test_a_valid_submission_is_stored_with_files_a_receipt_and_an_email(): void
    {
        Storage::fake('local');
        Mail::fake();
        $bid = Bid::factory()->open()->create();

        $this->post("/bids/{$bid->reference_number}/respond", $this->payload())
            ->assertRedirect("/bids/{$bid->reference_number}/respond/received")
            ->assertSessionHas('receipt');

        $response = BidResponse::firstOrFail();
        $this->assertSame($bid->id, $response->bid_id);
        $this->assertSame(ResponseMethod::Online, $response->method);
        $this->assertSame('Acme Paving', $response->vendor_name);
        $this->assertMatchesRegularExpression('/^[A-Z2-9]{8}$/', $response->receipt_code);
        $this->assertFalse($response->isLate());
        $this->assertCount(1, $response->files);
        Storage::disk('local')->assertExists($response->files->first()->path);

        Mail::assertSent(ResponseReceived::class, fn ($mail) => $mail->hasTo('sam@acme.example'));
    }

    public function test_the_received_page_needs_a_fresh_receipt(): void
    {
        $bid = Bid::factory()->open()->create();

        $this->get("/bids/{$bid->reference_number}/respond/received")->assertRedirect("/bids/{$bid->reference_number}");
    }

    public function test_late_submissions_are_rejected(): void
    {
        Storage::fake('local');
        $closed = Bid::factory()->closed()->create();

        $this->post("/bids/{$closed->reference_number}/respond", $this->payload())
            ->assertRedirect("/bids/{$closed->reference_number}")
            ->assertSessionHas('error');

        $this->assertDatabaseCount('bid_responses', 0);
    }

    public function test_submissions_to_drafts_return_404(): void
    {
        $draft = Bid::factory()->create();

        $this->post("/bids/{$draft->reference_number}/respond", $this->payload())->assertNotFound();
    }

    public function test_validation_rules(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        
        Storage::fake('local');
        $bid = Bid::factory()->open()->create();
        $url = "/bids/{$bid->reference_number}/respond";

        $this->post($url, $this->payload(['files' => []]))->assertSessionHasErrors('files');
        $this->post($url, $this->payload(['files' => [UploadedFile::fake()->create('notes.txt', 10, 'text/plain')]]))->assertSessionHasErrors('files.0');
        $this->post($url, $this->payload(['files' => array_map(fn () => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'), range(1, 6))]))->assertSessionHasErrors('files');
        $this->post($url, $this->payload(['acknowledge' => null]))->assertSessionHasErrors('acknowledge');
        $this->post($url, $this->payload(['contact_email' => 'not-an-email']))->assertSessionHasErrors('contact_email');
        $this->post($url, $this->payload(['vendor_name' => '']))->assertSessionHasErrors('vendor_name');

        $this->assertDatabaseCount('bid_responses', 0);
    }

    public function test_the_honeypot_blocks_bots(): void
    {
        Storage::fake('local');
        $bid = Bid::factory()->open()->create();

        $this->post("/bids/{$bid->reference_number}/respond", $this->payload(['website' => 'http://spam.example']))
            ->assertSessionHasErrors('website');

        $this->assertDatabaseCount('bid_responses', 0);
    }

    public function test_submissions_are_rate_limited(): void
    {
        $bid = Bid::factory()->open()->create();

        foreach (range(1, 5) as $i) {
            $this->post("/bids/{$bid->reference_number}/respond", []);
        }

        $this->post("/bids/{$bid->reference_number}/respond", [])->assertStatus(429);
    }

    public function test_a_failed_confirmation_email_does_not_lose_the_response(): void
    {
        Storage::fake('local');
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('smtp down'));
        $bid = Bid::factory()->open()->create();

        $this->post("/bids/{$bid->reference_number}/respond", $this->payload())
            ->assertRedirect("/bids/{$bid->reference_number}/respond/received");

        $this->assertDatabaseCount('bid_responses', 1);
    }
}