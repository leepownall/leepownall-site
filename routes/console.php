<?php

use App\Console\Commands\FetchBodyBatteryCommand;
use App\Console\Commands\FetchSleepCommand;
use App\Console\Commands\FetchStepsCommand;
use Spatie\WebhookClient\Models\WebhookCall;

Schedule::command('model:prune', [
    '--model' => [WebhookCall::class],
])->daily();

//Schedule::command(FetchSleepCommand::class)->dailyAt('8:30');
//Schedule::command(FetchStepsCommand::class)->hourly()->between('8:00', '00:00');
//Schedule::command(FetchBodyBatteryCommand::class)->hourlyAt('10')->between('8:00', '00:00');
