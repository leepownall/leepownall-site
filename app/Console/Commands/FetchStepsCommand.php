<?php

namespace App\Console\Commands;

use App\Jobs\FetchStepsJob;
use Illuminate\Console\Command;

class FetchStepsCommand extends Command
{
    protected $signature = 'garmin:fetch-steps';

    public function handle(): void
    {
        FetchStepsJob::dispatch();
    }
}
