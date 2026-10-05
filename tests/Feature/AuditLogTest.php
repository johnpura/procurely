<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Bid;
use App\Models\BidResponse;
use App\Models\BidResponseFile;
use App\Models\User;
use App\Helpers\Audit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_entries_cannot_be_changed_or_deleted(): void
    {
        $entry = Audit::record('test.action', 'Something happened');

        $this->expectException(\LogicException::class);
        $entry->update(['summary' => 'Tampered']);
    }

    public function test_entries_cannot_be_deleted(): void
    {
        $entry = Audit::record('test.action', 'Something happened');

        $this->expectException(\LogicException::class);
        $entry->delete();
    }

    public function test_bid_lifecycle_actions_are_recorded_with_deadline_changes(): void
    {
        $admin = User::factory()->admin()->create();
        $payload = [
            'title' => 'Fleet vehicles',
            'department' => 'Public Works',
            'description' => 'Supply six trucks.',
            'closes_at' => now()->addDays(10)->format('Y-m-d\TH:i'),
        ];

        $this->actingAs($admin)->post('/manage/bids', $payload);
        $bid = Bid::firstOrFail();

        $this->actingAs($admin)->post("/manage/bids/{$bid->reference_number}/publish");
        $newClose = now()->addDays(20)->format('Y-m-d\TH:i');
        $this->actingAs($admin)->put("/manage/bids/{$bid->reference_number}", ['closes_at' => $newClose] + $payload);

        $this->assertEqualsCanonicalizing(
            ['bid.created', 'bid.published', 'bid.updated'],
            AuditLog::pluck('action')->all()
        );

        $updated = AuditLog::where('action', 'bid.updated')->firstOrFail();
        $this->assertSame($admin->id, $updated->user_id);
        $this->assertSame($bid->id, $updated->bid_id);
        $this->assertArrayHasKey('closes_at', $updated->properties['changes']);
        $this->assertSame($newClose, \Carbon\Carbon::parse($updated->properties['changes']['closes_at']['to'])->format('Y-m-d\TH:i'));
    }

    public function test_saving_without_changes_records_nothing(): void
    {
        $admin = User::factory()->admin()->create();
        $bid = Bid::factory()->create(['closes_at' => now()->addDays(5)->startOfMinute()]);

        $this->actingAs($admin)->put("/manage/bids/{$bid->reference_number}", [
            'title' => $bid->title,
            'department' => $bid->department,
            'description' => $bid->description,
            'closes_at' => $bid->closes_at->format('Y-m-d\TH:i'),
        ]);

        $this->assertDatabaseMissing('audit_logs', ['action' => 'bid.updated']);
    }

    public function test_every_opening_of_responses_is_recorded(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $bid = Bid::factory()->closed()->create();
        $response = BidResponse::factory()->for($bid)->create(['vendor_name' => 'Acme Paving']);

        Storage::disk('local')->put('responses/a.pdf', 'pdf');
        $file = BidResponseFile::create(['bid_response_id' => $response->id, 'original_name' => 'proposal.pdf', 'path' => 'responses/a.pdf', 'size' => 3]);

        $base = "/manage/bids/{$bid->reference_number}/responses";
        $this->actingAs($admin)->get($base);
        $this->actingAs($admin)->get("{$base}/{$response->id}");
        $this->actingAs($admin)->get("{$base}/{$response->id}/files/{$file->id}");
        $this->actingAs($admin)->get($base);

        $this->assertSame(2, AuditLog::where('action', 'responses.opened')->count());
        $this->assertSame(1, AuditLog::where('action', 'response.viewed')->count());
        $this->assertSame(1, AuditLog::where('action', 'response.file_downloaded')->count());
        $this->assertSame([$admin->id], AuditLog::pluck('user_id')->unique()->values()->all());
    }

    public function test_sealed_attempts_are_not_recorded_as_openings(): void
    {
        $admin = User::factory()->admin()->create();
        $open = Bid::factory()->open()->create();

        $this->actingAs($admin)->get("/manage/bids/{$open->reference_number}/responses")->assertForbidden();

        $this->assertDatabaseMissing('audit_logs', ['action' => 'responses.opened']);
    }

    public function test_online_submissions_are_recorded_with_the_vendor_as_actor(): void
    {
        Storage::fake('local');
        Mail::fake();
        $bid = Bid::factory()->open()->create();

        $this->post("/bids/{$bid->reference_number}/respond", [
            'vendor_name' => 'Acme Paving',
            'contact_name' => 'Sam Vendor',
            'contact_email' => 'sam@acme.example',
            'files' => [UploadedFile::fake()->create('proposal.pdf', 100, 'application/pdf')],
            'acknowledge' => '1',
        ]);

        $entry = AuditLog::where('action', 'response.submitted')->firstOrFail();
        $this->assertNull($entry->user_id);
        $this->assertSame('Vendor: Acme Paving', $entry->actor_name);
        $this->assertSame($bid->id, $entry->bid_id);
    }

    public function test_user_management_actions_are_recorded_without_secrets(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create();

        $this->actingAs($admin)->put("/staff/{$target->id}", [
            'name' => $target->name, 'email' => $target->email, 'role' => 'admin',
        ]);
        $this->actingAs($admin)->post("/staff/{$target->id}/disable");

        $updated = AuditLog::where('action', 'user.updated')->firstOrFail();
        $this->assertEquals(['from' => 'staff', 'to' => 'admin'], $updated->properties['changes']['role']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.disabled', 'subject_id' => $target->id]);
        $this->assertStringNotContainsString('password', json_encode(AuditLog::all()->toArray()));
    }

    public function test_only_admins_can_read_the_log(): void
    {
        $this->get('/audit')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/audit')->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get('/audit')->assertOk();
    }

    public function test_the_log_can_be_filtered(): void
    {
        $admin = User::factory()->admin()->create();
        $bid = Bid::factory()->create();
        Audit::record('bid.published', 'Published thing one', $bid);
        Audit::record('user.disabled', 'Disabled somebody');

        $this->actingAs($admin)->get('/audit?action=bid.published')->assertSee('Published thing one')->assertDontSee('Disabled somebody');
        $this->actingAs($admin)->get('/audit?bid='.$bid->reference_number)->assertSee('Published thing one')->assertDontSee('Disabled somebody');
        $this->actingAs($admin)->get('/audit?q=somebody')->assertSee('Disabled somebody')->assertDontSee('Published thing one');
        $this->actingAs($admin)->get('/audit?from=2999-01-01')->assertSee('No entries match');
    }

    public function test_the_history_link_is_only_shown_to_admins(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->create();
        $bid = Bid::factory()->create(['created_by' => $staff->id]);
        $link = '/audit?bid='.$bid->reference_number;

        $this->actingAs($admin)->get("/manage/bids/{$bid->reference_number}/edit")
            ->assertOk()->assertSee($link, false);

        $this->actingAs($staff)->get("/manage/bids/{$bid->reference_number}/edit")
            ->assertOk()->assertDontSee('/audit?bid=', false);
    }
}