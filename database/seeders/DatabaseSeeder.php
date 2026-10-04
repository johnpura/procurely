<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Bid;
use App\Models\User;
use App\Models\BidResponse;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $password = config('procurely.admin.password');

        if (! $password) {
            if (! app()->environment('local')) {
                $this->command->error('Set ADMIN_PASSWORD before seeding outside the local environment.');

                return;
            }

            $password = 'password';
        }

        $admin = User::firstOrNew(['email' => config('procurely.admin.email')]);
        $admin->name = 'Admin';
        $admin->password = $password;
        $admin->role = Role::Admin;
        $admin->phone = '(555) 555-0101';
        $admin->email_verified_at = now();
        $admin->save();

        if (app()->environment('local')) {
            $make = fn ($factory) => $factory->state(['assigned_to' => $admin->id]);

            $make(Bid::factory()->count(4)->open())->create();
            $make(Bid::factory()->count(3)->closed())->create();
            $make(Bid::factory()->count(2)->awarded())->create();
            $make(Bid::factory()->cancelled())->create();
            Bid::factory()->count(2)->create();

            Bid::closed()->get()->each(fn (Bid $bid) => BidResponse::factory()
                ->count(3)->for($bid)
                ->create(['submitted_at' => $bid->closes_at->copy()->subDays(2)]));

            Bid::open()->get()->each(fn (Bid $bid) => BidResponse::factory()->for($bid)->create());
        }
    }
}
