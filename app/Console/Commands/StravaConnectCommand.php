<?php

namespace App\Console\Commands;

use App\Models\StravaToken;
use App\Training\StravaClient;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\Client\RequestException;

use function Laravel\Prompts\text;

#[Signature('strava:connect')]
#[Description('Authorise the site to read your Strava activities and routes, once, so webhooks can fetch new runs')]
class StravaConnectCommand extends Command
{
    public function handle(): int
    {
        $this->components->info('Open this URL, approve access, then copy the address Strava sends you to. The page will not load, which is expected.');
        $this->line(StravaClient::authorizeUrl('http://localhost'));

        $answer = text('Paste the address or its code', required: true);
        parse_str((string) parse_url($answer, PHP_URL_QUERY), $query);
        $code = is_string($query['code'] ?? null) ? $query['code'] : trim($answer);

        try {
            $response = StravaClient::exchangeCode($code);
        } catch (RequestException $exception) {
            $this->components->error('Strava rejected the code: '.$exception->response->body());

            return self::FAILURE;
        }

        StravaToken::store($response['athlete']['id'], $response);

        $this->components->info("Connected Strava athlete {$response['athlete']['id']}.");

        return self::SUCCESS;
    }
}
