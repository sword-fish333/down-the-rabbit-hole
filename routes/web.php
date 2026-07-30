<?php

use App\Http\Controllers\FrontEnd\AuthController;
use App\Http\Controllers\FrontEnd\ChatController;
use App\Http\Controllers\FrontEnd\EmailVerificationController;
use App\Http\Controllers\FrontEnd\HomeController;
use App\Http\Controllers\FrontEnd\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

/*
|--------------------------------------------------------------------------
| Learner auth (web guard)
|--------------------------------------------------------------------------
|
| Registering or logging in claims any rabbit holes started as a guest this
| session, then XP and streaks begin to count.
|
*/

Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register')->name('register.submit');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login.submit');
Route::get('/oauth-login', [AuthController::class, 'oauthRedirect'])->middleware('throttle:oauth')->name('oauth-login');
Route::get('/oauth/google/callback', [AuthController::class, 'googleCallback'])->middleware('throttle:oauth')->name('google.callback');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Email confirmation
|--------------------------------------------------------------------------
|
| A nudge, not a gate: an unverified learner still descends, they just carry
| a reminder. The link is signed and hashes the address it was issued for.
|
*/

Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::post('/email/verify/resend', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')->name('verification.resend');
});
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['auth', 'signed', 'throttle:6,1'])->name('verification.verify');

/*
|--------------------------------------------------------------------------
| Learner profile
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->prefix('profile')->as('profile.')->group(function () {
    Route::get('/', [ProfileController::class, 'index'])->name('index');
    Route::post('update-profile', [ProfileController::class, 'updateProfile'])->name('update-profile');
    Route::post('update-password', [ProfileController::class, 'updatePassword'])->name('update-password');
    Route::post('update-avatar', [ProfileController::class, 'updateAvatar'])->name('update-avatar');
});

/*
|--------------------------------------------------------------------------
| The descent — rabbit-hole chat
|--------------------------------------------------------------------------
|
| Ungated: a guest can start and explore a hole; the session tracks ownership
| until they sign up. GET streams a teaching turn over SSE; POST /checkpoint
| grades their proof and returns the structured verdict as JSON.
|
*/

Route::get('/holes', [ChatController::class, 'index'])->middleware('auth')->name('holes.index');
Route::post('/descend', [ChatController::class, 'descend'])->middleware('throttle:descend')->name('descend');
Route::get('/hole/{conversation}', [ChatController::class, 'show'])->name('hole.show');
Route::get('/hole/{conversation}/stream', [ChatController::class, 'stream'])->name('hole.stream');
Route::post('/hole/{conversation}/checkpoint', [ChatController::class, 'checkpoint'])->name('hole.checkpoint');
