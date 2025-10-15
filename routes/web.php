<?php

use App\Http\Controllers\Auth\Strava;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('strava/login', Strava\CreateController::class)->name('strava.create');
Route::get('strava/callback', Strava\StoreController::class);
