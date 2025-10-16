<?php

use Spatie\WebhookClient\Models\WebhookCall;

Schedule::command('model:prune', [
    '--model' => [WebhookCall::class],
])->daily();
