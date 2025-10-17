<?php

use Illuminate\Support\Facades\Route;

Route::webhooks('webhook', name: 'strava');
Route::webhooks('webhook', name: 'strava-verify', method: 'get');
