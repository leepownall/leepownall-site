<?php

use App\Jobs\FetchStepsJob;
use Spatie\WebhookClient\Models\WebhookCall;

Schedule::command('model:prune', [
    '--model' => [WebhookCall::class],
])->daily();

Schedule::command(FetchStepsJob::class)->hourly();
