<?php

namespace Database\Factories;

use App\Enums\BidStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class BidFactory extends Factory
{
    public function definition(): array
    {
        return [
            'reference_number' => 'RFP-'.now()->year.'-'.fake()->unique()->numerify('####'),
            'title' => fake()->sentence(5),
            'department' => fake()->randomElement(['Public Works', 'Information Technology', 'Parks', 'Finance']),
            'description' => fake()->paragraphs(2, true),
            'status' => BidStatus::Draft,
            'contact_name' => fake()->name(),
            'contact_email' => fake()->safeEmail(),
            'contact_phone' => fake()->numerify('(###) ###-####'),
        ];
    }

    public function open(): static
    {
        return $this->state(fn () => [
            'status' => BidStatus::Published,
            'published_at' => now()->subDays(3),
            'closes_at' => now()->addDays(14),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'status' => BidStatus::Published,
            'published_at' => now()->subDays(40),
            'closes_at' => now()->subDays(10),
        ]);
    }

    public function awarded(): static
    {
        return $this->state(fn () => [
            'status' => BidStatus::Awarded,
            'published_at' => now()->subDays(60),
            'closes_at' => now()->subDays(30),
            'awarded_to' => fake()->company(),
            'award_amount' => fake()->randomFloat(2, 5000, 250000),
            'awarded_at' => now()->subDays(20),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => BidStatus::Cancelled,
            'published_at' => now()->subDays(20),
            'closes_at' => now()->addDays(5),
        ]);
    }
}
