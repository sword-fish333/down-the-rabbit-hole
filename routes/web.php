<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FrontEnd\HomeController;

Route::get('/', [HomeController::class, 'index'])->name('home');
