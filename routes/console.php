<?php

use App\Console\Commands\FetchBodyBatteryCommand;
use App\Console\Commands\FetchSleepCommand;
use App\Console\Commands\FetchStepsCommand;
use Spatie\WebhookClient\Models\WebhookCall;

Schedule::command('model:prune', [
    '--model' => [WebhookCall::class],
])->daily();

Schedule::command(FetchSleepCommand::class)->dailyAt('9:00');
Schedule::command(FetchStepsCommand::class)->hourly();
Schedule::command(FetchBodyBatteryCommand::class)->hourly();
