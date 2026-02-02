<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/ai', [HomeController::class, 'aiUsers'])->name('ai.users');
Route::get('/ai/{user}', [HomeController::class, 'aiProfile'])->name('ai.profile');
