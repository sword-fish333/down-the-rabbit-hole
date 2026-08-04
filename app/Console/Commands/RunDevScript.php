<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('run:dev-script')]
#[Description('Command description')]
class RunDevScript extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {

        Admin::create([
            'name' => 'Master',
            'role' => 'main-admin',
            'email' => 'ghiurcaalin@gmail.com',
            'password' => bcrypt('dtrh_2026@!'),
            'enabled' => true,
        ]);
    }
}
