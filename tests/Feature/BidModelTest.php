<?php

namespace Tests\Feature;

use App\Models\Bid;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BidModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_open_and_closed_scopes_split_published_bids_by_closing_time(): void
    {
        $open = Bid::factory()->open()->create();
        $closed = Bid::factory()->closed()->create();
        $awarded = Bid::factory()->awarded()->create();
        $cancelled = Bid::factory()->cancelled()->create();
        $draft = Bid::factory()->create();

        $this->assertEquals([$open->id], Bid::open()->pluck('id')->all());
        $this->assertEqualsCanonicalizing(
            [$closed->id, $awarded->id, $cancelled->id],
            Bid::closed()->pluck('id')->all()
        );
        $this->assertNotContains($draft->id, Bid::visible()->pluck('id')->all());
    }

    public function test_display_status_labels(): void
    {
        $this->assertSame('Open', Bid::factory()->open()->make()->displayStatus());
        $this->assertSame('Closed', Bid::factory()->closed()->make()->displayStatus());
        $this->assertSame('Awarded', Bid::factory()->awarded()->make()->displayStatus());
        $this->assertSame('Cancelled', Bid::factory()->cancelled()->make()->displayStatus());
        $this->assertSame('Draft', Bid::factory()->make()->displayStatus());
    }

    public function test_bids_are_found_by_reference_number(): void
    {
        $this->assertSame('reference_number', (new Bid)->getRouteKeyName());
    }
}
