<?php

namespace App\Console\Commands;

use App\Jobs\SendEmailVerificationEmail;
use App\Models\Admin;
use App\Models\User;
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
        $user=User::latest()->first();
        SendEmailVerificationEmail::dispatchSync($user->id, 'en');
dd('1');
        Admin::create([
            'name' => 'Master',
            'role' => 'main-admin',
            'email' => 'ghiurcaalin@gmail.com',
            'password' => bcrypt('dtrh_2026@!'),
            'enabled' => true,
        ]);
    }
}
