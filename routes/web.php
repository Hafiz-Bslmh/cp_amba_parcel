<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\potential_simulation;


Route::get('/', [DashboardController::class, 'homepage'])->name('home');
Route::get('/pilihanarmada', [DashboardController::class, 'pilihanarmada'])->name('armada');
Route::get('/simulasi-potensi', [potential_simulation::class, 'simulation'])->name('simulation');
