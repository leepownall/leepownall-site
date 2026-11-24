<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\StravaRouteController;
use App\Http\Controllers\TrainingPlanController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/routes', [StravaRouteController::class, 'index'])->name('strava-routes.index');
});

Route::get('/training/prep/{prep}', [TrainingPlanController::class, 'prep'])
    ->whereNumber('prep')
    ->name('training.prep');

Route::get('/training/{week?}', TrainingPlanController::class)
    ->whereNumber('week')
    ->name('training');
