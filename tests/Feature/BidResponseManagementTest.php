<?php

namespace Tests\Feature;

use App\Enums\ResponseMethod;
use App\Models\Bid;
use App\Models\BidResponse;
use App\Models\BidResponseFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BidResponseManagementTest extends TestCase
{
    use RefreshDatabase;

    private function closedBidAssignedTo(User $user): Bid
    {
        $bid = Bid::factory()->closed()->create();
        $bid->assigned_to = $user->id;
        $bid->save();

        return $bid;
    }

    public function test_responses_stay_sealed_until_the_bid_closes(): void
    {
        $admin = User::factory()->admin()->create();
        $open = Bid::factory()->open()->create();
        BidResponse::factory()->for($open)->create();

        $this->actingAs($admin)->get("/manage/bids/{$open->reference_number}/responses")->assertForbidden();
        $this->actingAs($admin)->get("/manage/bids/{$open->reference_number}/edit")
            ->assertOk()->assertSee('1 received so far')->assertSee('sealed');
    }

    public function test_after_closing_only_admins_and_the_assigned_contact_can_open_responses(): void
    {
        $admin = User::factory()->admin()->create();
        $assigned = User::factory()->create();
        $other = User::factory()->create();
        $bid = $this->closedBidAssignedTo($assigned);
        BidResponse::factory()->for($bid)->create(['vendor_name' => 'Acme Paving']);

        $url = "/manage/bids/{$bid->reference_number}/responses";

        $this->actingAs($admin)->get($url)->assertOk()->assertSee('Acme Paving');
        $this->actingAs($assigned)->get($url)->assertOk();
        $this->actingAs($other)->get($url)->assertForbidden();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $bid = Bid::factory()->closed()->create();

        $this->get("/manage/bids/{$bid->reference_number}/responses")->assertRedirect('/login');
    }

    public function test_extending_the_deadline_seals_responses_again(): void
    {
        $admin = User::factory()->admin()->create();
        $bid = Bid::factory()->closed()->create();
        BidResponse::factory()->for($bid)->create();

        $this->actingAs($admin)->get("/manage/bids/{$bid->reference_number}/responses")->assertOk();

        $bid->closes_at = now()->addDays(3);
        $bid->save();

        $this->actingAs($admin)->get("/manage/bids/{$bid->reference_number}/responses")->assertForbidden();
    }

    public function test_response_details_and_file_downloads_respect_ownership(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $bid = Bid::factory()->closed()->create();
        $other = Bid::factory()->closed()->create();

        $response = BidResponse::factory()->for($bid)->create();
        $otherResponse = BidResponse::factory()->for($other)->create();

        Storage::disk('local')->put('responses/a.pdf', 'pdf');
        $file = BidResponseFile::create(['bid_response_id' => $response->id, 'original_name' => 'proposal.pdf', 'path' => 'responses/a.pdf', 'size' => 3]);

        $base = "/manage/bids/{$bid->reference_number}/responses";

        $this->actingAs($admin)->get("{$base}/{$response->id}")->assertOk()->assertSee('proposal.pdf');
        $this->actingAs($admin)->get("{$base}/{$response->id}/files/{$file->id}")->assertOk()->assertDownload('proposal.pdf');
        $this->actingAs($admin)->get("{$base}/{$otherResponse->id}")->assertNotFound();
        $this->actingAs($admin)->get("{$base}/{$otherResponse->id}/files/{$file->id}")->assertNotFound();
    }

    public function test_files_cannot_be_downloaded_before_closing_or_by_unauthorized_staff(): void
    {
        Storage::fake('local');
        $staff = User::factory()->create();
        $open = Bid::factory()->open()->create();
        $closed = Bid::factory()->closed()->create();

        Storage::disk('local')->put('responses/a.pdf', 'pdf');
        $r1 = BidResponse::factory()->for($open)->create();
        $f1 = BidResponseFile::create(['bid_response_id' => $r1->id, 'original_name' => 'a.pdf', 'path' => 'responses/a.pdf', 'size' => 3]);
        $r2 = BidResponse::factory()->for($closed)->create();
        $f2 = BidResponseFile::create(['bid_response_id' => $r2->id, 'original_name' => 'a.pdf', 'path' => 'responses/a.pdf', 'size' => 3]);

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get("/manage/bids/{$open->reference_number}/responses/{$r1->id}/files/{$f1->id}")->assertForbidden();
        $this->actingAs($staff)->get("/manage/bids/{$closed->reference_number}/responses/{$r2->id}/files/{$f2->id}")->assertForbidden();
    }

    public function test_the_assigned_contact_can_log_an_email_response_even_before_closing(): void
    {
        Storage::fake('local');
        $assigned = User::factory()->create();
        $bid = Bid::factory()->open()->create();
        $bid->assigned_to = $assigned->id;
        $bid->save();

        $this->actingAs($assigned)->post("/manage/bids/{$bid->reference_number}/responses", [
            'method' => 'email',
            'vendor_name' => 'Emailed Co',
            'received_at' => now()->subHour()->format('Y-m-d\TH:i'),
            'files' => [UploadedFile::fake()->create('email-bid.pdf', 50, 'application/pdf')],
        ])->assertRedirect("/manage/bids/{$bid->reference_number}/edit")->assertSessionHas('status');

        $response = BidResponse::firstOrFail();
        $this->assertSame(ResponseMethod::Email, $response->method);
        $this->assertSame($assigned->id, $response->logged_by);
        $this->assertFalse($response->isLate());
        $this->assertCount(1, $response->files);
    }

    public function test_responses_logged_after_the_deadline_are_marked_late(): void
    {
        $admin = User::factory()->admin()->create();
        $bid = Bid::factory()->closed()->create();

        $this->actingAs($admin)->post("/manage/bids/{$bid->reference_number}/responses", [
            'method' => 'mail',
            'vendor_name' => 'Slow Mail Co',
            'received_at' => now()->format('Y-m-d\TH:i'),
        ])->assertRedirect();

        $this->assertTrue(BidResponse::firstOrFail()->isLate());
    }

    public function test_logging_is_limited_to_admins_and_the_assigned_contact_on_published_bids(): void
    {
        $other = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $open = Bid::factory()->open()->create();
        $awarded = Bid::factory()->awarded()->create();
        $draft = Bid::factory()->create();
        $payload = ['method' => 'mail', 'vendor_name' => 'X', 'received_at' => now()->subMinute()->format('Y-m-d\TH:i')];

        $this->actingAs($other)->post("/manage/bids/{$open->reference_number}/responses", $payload)->assertForbidden();
        $this->actingAs($admin)->post("/manage/bids/{$awarded->reference_number}/responses", $payload)->assertForbidden();
        $this->actingAs($admin)->post("/manage/bids/{$draft->reference_number}/responses", $payload)->assertForbidden();

        $this->assertDatabaseCount('bid_responses', 0);
    }

    public function test_logging_validation(): void
    {
        $admin = User::factory()->admin()->create();
        $bid = Bid::factory()->open()->create();
        $url = "/manage/bids/{$bid->reference_number}/responses";
        $base = ['method' => 'mail', 'vendor_name' => 'X', 'received_at' => now()->subMinute()->format('Y-m-d\TH:i')];

        $this->actingAs($admin)->post($url, ['method' => 'online'] + $base)->assertSessionHasErrors('method');
        $this->actingAs($admin)->post($url, ['received_at' => now()->addDay()->format('Y-m-d\TH:i')] + $base)->assertSessionHasErrors('received_at');
        $this->actingAs($admin)->post($url, ['vendor_name' => ''] + $base)->assertSessionHasErrors('vendor_name');
        $this->actingAs($admin)->post($url, ['files' => [UploadedFile::fake()->create('x.txt', 5, 'text/plain')]] + $base)->assertSessionHasErrors('files.0');

        $this->assertDatabaseCount('bid_responses', 0);
    }
}
