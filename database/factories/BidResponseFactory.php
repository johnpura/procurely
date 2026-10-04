<?php

namespace Database\Factories;

use App\Enums\ResponseMethod;
use App\Models\Bid;
use App\Models\BidResponse;
use Illuminate\Database\Eloquent\Factories\Factory;

class BidResponseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'bid_id' => Bid::factory()->open(),
            'method' => ResponseMethod::Online,
            'receipt_code' => fn () => BidResponse::generateReceiptCode(),
            'vendor_name' => fake()->company(),
            'contact_name' => fake()->name(),
            'contact_email' => fake()->safeEmail(),
            'contact_phone' => fake()->numerify('(###) ###-####'),
            'submitted_at' => now()->subHour(),
        ];
    }
}
