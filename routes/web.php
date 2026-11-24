<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

Route::get('/', [DashboardController::class, 'homepage'])->name('home');
Route::get('/pilihanarmada', [DashboardController::class, 'pilihanarmada'])->name('armada');

