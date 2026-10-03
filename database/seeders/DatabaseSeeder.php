<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use App\Models\Bid;
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
        $admin = User::firstOrNew(['email' => 'admin@procurely.com']);
        $admin->name = 'Admin';
        $admin->password = 'secret';
        $admin->role = Role::Admin;
        $admin->email_verified_at = now();
        $admin->save();

        if (app()->environment('local')) {
            Bid::factory()->count(4)->open()->create();
            Bid::factory()->count(3)->closed()->create();
            Bid::factory()->count(2)->awarded()->create();
            Bid::factory()->cancelled()->create();
            Bid::factory()->count(2)->create();
        }
    }
}
