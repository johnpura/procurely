<?php

namespace Tests\Feature;

use App\Enums\BidStatus;
use App\Models\Bid;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BidManagementTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Fleet vehicles',
            'department' => 'Public Works',
            'description' => 'Supply six pickup trucks.',
            'closes_at' => now()->addDays(10)->format('Y-m-d\TH:i'),
        ], $overrides);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/manage/bids')->assertRedirect('/login');
    }

    public function test_staff_create_a_draft_with_a_generated_reference_and_cannot_assign_contacts(): void
    {
        $staff = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($staff)->post('/manage/bids', $this->payload(['assigned_to' => $other->id]))->assertRedirect();

        $bid = Bid::firstOrFail();
        $this->assertSame(BidStatus::Draft, $bid->status);
        $this->assertSame($staff->id, $bid->created_by);
        $this->assertNull($bid->assigned_to);
        $this->assertMatchesRegularExpression('/^BID-\d{4}-0001$/', $bid->reference_number);
    }

    public function test_admins_can_assign_a_contact(): void
    {
        $admin = User::factory()->admin()->create();
        $contact = User::factory()->create(['name' => 'Pat Lee']);

        $this->actingAs($admin)->post('/manage/bids', $this->payload(['assigned_to' => $contact->id]));

        $this->assertSame($contact->id, Bid::firstOrFail()->assigned_to);
    }

    public function test_reference_numbers_increment(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/manage/bids', $this->payload());
        $this->actingAs($admin)->post('/manage/bids', $this->payload());

        $year = now()->year;

        $this->assertEquals(
            ["BID-{$year}-0001", "BID-{$year}-0002"],
            Bid::orderBy('reference_number')->pluck('reference_number')->all()
        );
    }

    public function test_staff_edit_only_their_own_drafts(): void
    {
        $staff = User::factory()->create();
        $mine = Bid::factory()->create(['created_by' => $staff->id]);
        $theirs = Bid::factory()->create();
        $published = Bid::factory()->open()->create(['created_by' => $staff->id]);

        $this->actingAs($staff)->get("/manage/bids/{$mine->reference_number}/edit")->assertOk();
        $this->actingAs($staff)->put("/manage/bids/{$mine->reference_number}", $this->payload(['title' => 'New title']))->assertRedirect();
        $this->assertSame('New title', $mine->fresh()->title);

        $this->actingAs($staff)->put("/manage/bids/{$theirs->reference_number}", $this->payload())->assertForbidden();
        $this->actingAs($staff)->put("/manage/bids/{$published->reference_number}", $this->payload())->assertForbidden();
    }

    public function test_only_admins_publish_and_a_future_closing_time_is_required(): void
    {
        $staff = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $draft = Bid::factory()->create(['created_by' => $staff->id, 'closes_at' => now()->addDays(5)]);
        $noDate = Bid::factory()->create(['closes_at' => null]);

        $this->actingAs($staff)->post("/manage/bids/{$draft->reference_number}/publish")->assertForbidden();

        $this->actingAs($admin)->post("/manage/bids/{$noDate->reference_number}/publish")->assertSessionHasErrors('publish');
        $this->assertSame(BidStatus::Draft, $noDate->fresh()->status);

        $this->actingAs($admin)->post("/manage/bids/{$draft->reference_number}/publish")->assertRedirect();
        $this->assertSame(BidStatus::Published, $draft->fresh()->status);
        $this->assertNotNull($draft->fresh()->published_at);

        $this->get('/bids/'.$draft->reference_number)->assertOk();
    }

    public function test_admins_can_cancel_only_published_bids(): void
    {
        $admin = User::factory()->admin()->create();
        $open = Bid::factory()->open()->create();
        $draft = Bid::factory()->create();

        $this->actingAs($admin)->post("/manage/bids/{$draft->reference_number}/cancel")->assertForbidden();
        $this->actingAs($admin)->post("/manage/bids/{$open->reference_number}/cancel")->assertRedirect();

        $this->assertSame(BidStatus::Cancelled, $open->fresh()->status);
    }

    public function test_awarding_requires_a_closed_bid_and_valid_input(): void
    {
        $admin = User::factory()->admin()->create();
        $open = Bid::factory()->open()->create();
        $closed = Bid::factory()->closed()->create();
        $data = ['awarded_to' => 'Acme Paving', 'award_amount' => '12500.50', 'awarded_at' => now()->toDateString()];

        $this->actingAs($admin)->post("/manage/bids/{$open->reference_number}/award", $data)->assertForbidden();
        $this->actingAs($admin)->post("/manage/bids/{$closed->reference_number}/award", ['awarded_to' => ''] + $data)->assertSessionHasErrors('awarded_to');

        $this->actingAs($admin)->post("/manage/bids/{$closed->reference_number}/award", $data)->assertRedirect();

        $closed->refresh();
        $this->assertSame(BidStatus::Awarded, $closed->status);
        $this->assertSame('Acme Paving', $closed->awarded_to);
        $this->assertEquals(12500.50, (float) $closed->award_amount);
    }

    public function test_only_drafts_can_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $draft = Bid::factory()->create();
        $open = Bid::factory()->open()->create();

        $this->actingAs($admin)->delete("/manage/bids/{$open->reference_number}")->assertForbidden();
        $this->assertModelExists($open);

        $this->actingAs($admin)->delete("/manage/bids/{$draft->reference_number}")->assertRedirect();
        $this->assertModelMissing($draft);
    }

    public function test_documents_can_be_uploaded_as_pdf_and_removed(): void
    {
        Storage::fake('local');
        $staff = User::factory()->create();
        $bid = Bid::factory()->create(['created_by' => $staff->id]);

        $this->actingAs($staff)->post("/manage/bids/{$bid->reference_number}/documents", [
            'name' => 'RFP packet',
            'file' => UploadedFile::fake()->create('rfp.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        $doc = $bid->documents()->firstOrFail();
        Storage::disk('local')->assertExists($doc->path);

        $this->actingAs($staff)->delete("/manage/bids/{$bid->reference_number}/documents/{$doc->id}")->assertRedirect();
        Storage::disk('local')->assertMissing($doc->path);
        $this->assertDatabaseCount('bid_documents', 0);
    }

    public function test_non_pdf_uploads_and_other_peoples_bids_are_rejected(): void
    {
        Storage::fake('local');
        $staff = User::factory()->create();
        $mine = Bid::factory()->create(['created_by' => $staff->id]);
        $theirs = Bid::factory()->create();

        $this->actingAs($staff)->post("/manage/bids/{$mine->reference_number}/documents", [
            'file' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
        ])->assertSessionHasErrors('file');

        $this->actingAs($staff)->post("/manage/bids/{$theirs->reference_number}/documents", [
            'file' => UploadedFile::fake()->create('rfp.pdf', 100, 'application/pdf'),
        ])->assertForbidden();
    }

    public function test_published_bids_lock_their_reference_number_but_admins_can_extend_the_deadline(): void
    {
        $admin = User::factory()->admin()->create();
        $bid = Bid::factory()->open()->create(['reference_number' => 'BID-2026-0099']);
        $newClose = now()->addDays(30)->format('Y-m-d\TH:i');

        $this->actingAs($admin)->put('/manage/bids/BID-2026-0099', $this->payload([
            'reference_number' => 'BID-2026-0100',
            'closes_at' => $newClose,
        ]))->assertRedirect();

        $bid->refresh();
        $this->assertSame('BID-2026-0099', $bid->reference_number);
        $this->assertSame($newClose, $bid->closes_at->format('Y-m-d\TH:i'));
    }
}