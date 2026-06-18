<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FrontEnd\HomeController;
Route::get('/', function () {
    return view('welcome');
});

Route::get('/', [HomeController::class, 'index'])->name('home');
