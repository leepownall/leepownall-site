<?php

namespace App\Console\Commands;

use App\Jobs\FetchSleepJob;
use Illuminate\Console\Command;

class FetchSleepCommand extends Command
{
    protected $signature = 'garmin:fetch-sleep';

    public function handle(): void
    {
        FetchSleepJob::dispatch();
    }
}
