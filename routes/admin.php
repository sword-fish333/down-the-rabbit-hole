<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\ConversationController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LearningModeController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->as('admin.')->group(function () {
    // Guest (AuthController redirects already-authenticated admins to the dashboard)
    Route::get('login', [AuthController::class, 'login'])->name('login');
    Route::post('login', [AuthController::class, 'submitLogin'])->name('login.submit');
    Route::get('oauth-login', [AuthController::class, 'oauthRedirect'])->name('oauth-login');
    Route::get('google-callback', [AuthController::class, 'googleLogin'])->name('google.callback');

    // Authenticated
    Route::middleware('admin.auth')->group(function () {
        Route::get('/', fn () => redirect()->route('admin.dashboard'));
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        Route::prefix('profile')->as('profile.')->group(function () {
            Route::get('/', [ProfileController::class, 'index'])->name('index');
            Route::post('update-profile', [ProfileController::class, 'updateProfile'])->name('update-profile');
            Route::post('update-password', [ProfileController::class, 'updatePassword'])->name('update-password');
            Route::post('update-profile-img', [ProfileController::class, 'updateProfileImg'])->name('update-profile-img');
            Route::post('request-support', [ProfileController::class, 'requestSupport'])->name('request-support');
        });

        // Content: how the guide teaches. Full CRUD.
        Route::group(
            ['prefix' => 'learning-mode', 'as' => 'learning-mode.'],
            resourceRoutesCallback(LearningModeController::class, 'learning_mode'),
        );
        Route::patch('learning-mode/{learning_mode}/toggle', [LearningModeController::class, 'toggle'])
            ->name('learning-mode.toggle');

        // Learners. No `create`/`store` — accounts come from public sign-up.
        Route::group(
            ['prefix' => 'user', 'as' => 'user.'],
            resourceRoutesCallback(UserController::class, 'user', except: ['create', 'store']),
        );

        // Rabbit holes. Read-only oversight plus deletion.
        Route::group(
            ['prefix' => 'conversation', 'as' => 'conversation.'],
            resourceRoutesCallback(ConversationController::class, 'conversation', except: ['create', 'store', 'update']),
        );
    });
});
