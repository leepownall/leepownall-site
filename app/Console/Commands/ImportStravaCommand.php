<?php

namespace App\Console\Commands;

use App\Jobs\ImportStravaActivities;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('training:import-strava {path}')]
#[Description('Import runs from a Strava activities export')]
class ImportStravaCommand extends Command
{
    public function handle(): int
    {
        $path = (string) $this->argument('path');

        if (is_dir($path)) {
            $path = rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'activities.csv';
        }

        if (! is_file($path)) {
            $this->components->error('Activities export not found.');

            return self::FAILURE;
        }

        ImportStravaActivities::dispatch($path);

        $this->components->info('Import queued.');

        return self::SUCCESS;
    }
}
