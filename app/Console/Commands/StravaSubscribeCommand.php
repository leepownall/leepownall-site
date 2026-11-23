<?php

namespace App\Console\Commands;

use App\Training\StravaClient;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\Client\RequestException;

#[Signature('strava:subscribe')]
#[Description('Ask Strava to send activity webhooks to this site')]
class StravaSubscribeCommand extends Command
{
    public function handle(): int
    {
        try {
            StravaClient::subscribe(route('strava.webhook'));
        } catch (RequestException $exception) {
            $this->components->error('Strava refused the subscription: '.$exception->response->body());

            return self::FAILURE;
        }

        $this->components->info('Subscribed. Strava will send webhooks to '.route('strava.webhook').'.');

        return self::SUCCESS;
    }
}
