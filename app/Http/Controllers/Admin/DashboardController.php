<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'users' => User::count(),
            'admins' => Admin::count(),
            'active_admins' => Admin::where('enabled', true)->count(),
        ];

        return view('admin.dashboard', [
            'admin' => auth('admin')->user(),
            'stats' => $stats,
        ]);
    }
}
