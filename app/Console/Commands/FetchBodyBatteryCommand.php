<?php

namespace App\Console\Commands;

use App\Jobs\FetchBodyBatteryJob;
use Illuminate\Console\Command;

class FetchBodyBatteryCommand extends Command
{
    protected $signature = 'garmin:fetch-body-battery';

    public function handle(): void
    {
        FetchBodyBatteryJob::dispatch();
    }
}
