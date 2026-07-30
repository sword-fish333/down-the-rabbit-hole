<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Admin::create([
            'name' => 'Master',
            'role' => 'main-admin',
            'email' => 'master@admin.com',
            'password' => bcrypt('dtrh_2026@!'),
            'enabled' => true,
        ]);
    }
}
