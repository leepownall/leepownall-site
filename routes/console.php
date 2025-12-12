<?php

use App\Console\Commands\FetchStepsCommand;
use Spatie\WebhookClient\Models\WebhookCall;

Schedule::command('model:prune', [
    '--model' => [WebhookCall::class],
])->daily();

Schedule::command(FetchStepsCommand::class)->hourly();
