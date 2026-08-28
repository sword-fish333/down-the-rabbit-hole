<?php

use App\Http\Controllers\FrontEnd\AuthController;
use App\Http\Controllers\FrontEnd\ChatController;
use App\Http\Controllers\FrontEnd\EmailVerificationController;
use App\Http\Controllers\FrontEnd\HomeController;
use App\Http\Controllers\FrontEnd\LearnerController;
use App\Http\Controllers\FrontEnd\MethodController;
use App\Http\Controllers\FrontEnd\ProfileController;
use App\Http\Controllers\FrontEnd\RankingController;
use App\Http\Controllers\FrontEnd\SubjectController;
use App\Http\Controllers\FrontEnd\SubjectFolderController;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

/*
|--------------------------------------------------------------------------
| Language
|--------------------------------------------------------------------------
|
| A GET link, not a form, so the switcher works with JavaScript off and can sit
| anywhere in the chrome. The choice is kept for the visit and for the next one;
| an unknown code 404s rather than silently doing nothing.
|
| Note this changes the language of the *interface*. What language the guide
| teaches in follows the subject (conversations.locale) — see DescentService.
|
*/

Route::get('/language/{locale}', function (string $locale) {
    abort_unless(SetLocale::isSupported($locale), 404);

    session(['locale' => $locale]);
    cookie()->queue(cookie()->forever('locale', $locale));

    // Signed in, the choice belongs to the account, not to this browser — it is
    // waiting for them on the next device they open.
    auth()->user()?->update(['locale' => $locale]);

    return back(fallback: route('home'));
})->name('locale.switch');

/*
|--------------------------------------------------------------------------
| The methods — the reference desk
|--------------------------------------------------------------------------
|
| One entry per learning technique the product is built on. Public and ungated
| on purpose: "why should I trust how this thing teaches" is a question asked
| before signing up, and the honest answer is a page with a reading list on it.
|
| The slug is checked against the registry in config('platform.methods') by the
| controller, so there is nothing to guess and nothing to enumerate.
|
*/

Route::prefix('methods')->as('methods.')->group(function () {
    Route::get('/', [MethodController::class, 'index'])->name('index');
    Route::get('{slug}', [MethodController::class, 'show'])->name('show');
});

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
    Route::post('update-ranking', [ProfileController::class, 'updateRanking'])->name('update-ranking');
});

/*
|--------------------------------------------------------------------------
| The boards, and one learner's record
|--------------------------------------------------------------------------
|
| Signed-in only, both of them. Standing on a board is opt-in, and "visible to
| other learners" is a much easier yes than "visible to the open web" — a
| promise worth keeping narrow, because it is what fills the boards at all.
|
| Board and window travel in the query string so a link lands where it was sent
| from; the learner page is the row you clicked.
|
*/

Route::middleware('auth')->group(function () {
    Route::get('/rankings', [RankingController::class, 'index'])->name('rankings.index');
    Route::get('/learners/{user}', [LearnerController::class, 'show'])->name('learners.show');
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

    // Taught first, or asked first. Ungated like the descent itself, and applied
    // to the next layer rather than the one already open.
    Route::patch('{conversation}/approach', [ChatController::class, 'approach'])->name('approach');

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
