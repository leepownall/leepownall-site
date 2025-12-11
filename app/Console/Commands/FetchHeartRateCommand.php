<?php

namespace App\Console\Commands;

use App\Jobs\FetchHeartRateJob;
use Illuminate\Console\Command;

class FetchHeartRateCommand extends Command
{
    protected $signature = 'garmin:fetch-heart-rate';

    protected $description = 'Command description';

    public function handle(): void
    {
        FetchHeartRateJob::dispatch();
    }
}
