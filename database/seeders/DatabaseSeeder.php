<?php

namespace Database\Seeders;

use App\Models\Admin;
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
        // Content first: a hole started before the modes exist has none to pick.
        $this->call(LearningModeSeeder::class);

        User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
        ]);

        Admin::factory()->create([
            'name' => 'Admin',
            'first_name' => 'Admin',
            'last_name' => null,
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => 'main-admin',
        ]);
    }
}
