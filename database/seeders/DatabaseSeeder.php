<?php

namespace Database\Seeders;

use App\Enums\Role;
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
        $admin->save();
    }
}
