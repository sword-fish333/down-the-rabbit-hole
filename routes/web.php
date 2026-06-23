<?php

use App\Http\Controllers\FrontEnd\AuthController;
use App\Http\Controllers\FrontEnd\ChatController;
use App\Http\Controllers\FrontEnd\HomeController;
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
| The descent — rabbit-hole chat
|--------------------------------------------------------------------------
|
| Ungated: a guest can start and explore a hole; the session tracks ownership
| until they sign up. POST advances state, GET streams the guide's turn (SSE).
|
*/

Route::post('/descend', [ChatController::class, 'descend'])->name('descend');
Route::get('/hole/{conversation}', [ChatController::class, 'show'])->name('hole.show');
Route::post('/hole/{conversation}/continue', [ChatController::class, 'continue'])->name('hole.continue');
Route::get('/hole/{conversation}/stream', [ChatController::class, 'stream'])->name('hole.stream');
