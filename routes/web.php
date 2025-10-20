<?php

use App\Http\Controllers\Auth\Strava;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('strava/login', Strava\CreateController::class)->name('strava.create')->middleware('auth');
Route::get('strava/callback', Strava\StoreController::class);
