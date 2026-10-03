<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Bid;
use App\Models\User;
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
        $admin->phone = '(555) 555-0101';
        $admin->save();

        if (app()->environment('local')) {
            $make = fn ($factory) => $factory->state(['assigned_to' => $admin->id]);

            $make(Bid::factory()->count(4)->open())->create();
            $make(Bid::factory()->count(3)->closed())->create();
            $make(Bid::factory()->count(2)->awarded())->create();
            $make(Bid::factory()->cancelled())->create();
            Bid::factory()->count(2)->create();
        }
    }
}
