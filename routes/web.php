<?php

use App\Http\Controllers\FrontEnd\AuthController;
use App\Http\Controllers\FrontEnd\ChatController;
use App\Http\Controllers\FrontEnd\EmailVerificationController;
use App\Http\Controllers\FrontEnd\HomeController;
use App\Http\Controllers\FrontEnd\ProfileController;
use App\Http\Controllers\FrontEnd\SubjectController;
use App\Http\Controllers\FrontEnd\SubjectFolderController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

/*
|--------------------------------------------------------------------------
| Learner auth (web guard)
|--------------------------------------------------------------------------
|
| Registering or logging in claims any subjects started as a guest this
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
| The descent — one subject
|--------------------------------------------------------------------------
|
| Ungated: a guest can start and descend through a subject; the session tracks
| ownership until they sign up. GET streams a teaching turn over SSE; POST
| /checkpoint grades their proof and returns the structured verdict as JSON.
|
*/

Route::post('/descend', [ChatController::class, 'descend'])->middleware('throttle:descend')->name('descend');

Route::prefix('subject')->as('subject.')->group(function () {
    Route::get('{conversation}', [ChatController::class, 'show'])->name('show');
    Route::get('{conversation}/stream', [ChatController::class, 'stream'])->name('stream');
    Route::post('{conversation}/checkpoint', [ChatController::class, 'checkpoint'])->name('checkpoint');

    // Owning a subject is what lets you file or publish it, so these are gated
    // where the descent itself is not.
    Route::middleware('auth')->group(function () {
        Route::post('{conversation}/share', [SubjectController::class, 'share'])->name('share');
        Route::patch('{conversation}/folder', [SubjectController::class, 'file'])->name('file');
    });
});

/*
|--------------------------------------------------------------------------
| The library — every subject, and how the learner files them
|--------------------------------------------------------------------------
|
| `index` doubles as the lazy-load endpoint: asked for a fragment it returns
| just the next page of rows, so the list markup lives in Blade once.
|
*/

Route::middleware('auth')->prefix('subjects')->as('subjects.')->group(function () {
    Route::get('/', [SubjectController::class, 'index'])->name('index');
    Route::delete('/', [SubjectController::class, 'destroy'])->name('destroy');

    Route::get('organization', [SubjectFolderController::class, 'index'])->name('organization');
    Route::post('folders', [SubjectFolderController::class, 'store'])->name('folders.store');
    Route::patch('folders/{folder}', [SubjectFolderController::class, 'update'])->name('folders.update');
    Route::delete('folders/{folder}', [SubjectFolderController::class, 'destroy'])->name('folders.destroy');
});

/*
|--------------------------------------------------------------------------
| A shared subject — public, read-only
|--------------------------------------------------------------------------
|
| The token IS the capability: unshare nulls it and the link dies. Nothing here
| is guessable and nothing here is indexed unless the learner published it.
|
*/

Route::get('/s/{token}', [SubjectController::class, 'shared'])->name('subject.shared');
