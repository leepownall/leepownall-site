<?php

use App\Http\Controllers\StravaWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/strava/webhook', [StravaWebhookController::class, 'verify'])->name('strava.webhook.verify');
Route::post('/strava/webhook', StravaWebhookController::class)->name('strava.webhook');
