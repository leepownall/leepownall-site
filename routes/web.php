<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\TrainingPlanController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/training/prep/{prep}', [TrainingPlanController::class, 'prep'])
    ->whereNumber('prep')
    ->name('training.prep');

Route::get('/training/{week?}', TrainingPlanController::class)
    ->whereNumber('week')
    ->name('training');
