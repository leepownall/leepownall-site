<?php

namespace App\Console\Commands;

use App\Http\Integrations\Strava\Requests\CreateSubscription;
use App\Http\Integrations\Strava\Requests\DeleteSubscription;
use App\Http\Integrations\Strava\Requests\ViewSubscription;
use App\Http\Integrations\Strava\StravaConnector;
use Illuminate\Console\Command;

use function json_decode;
use function Laravel\Prompts\error;
use function Laravel\Prompts\select;
use function Laravel\Prompts\info;
use function Laravel\Prompts\table;
use function Laravel\Prompts\text;
use function Pest\Laravel\json;

class ManageStravaCommand extends Command
{
    protected $signature = 'strava:manage';

    public function handle(): void
    {
        $option = select(
            label: 'What do you want to do?',
            options: [
                'create-subscription' => 'Create Subscription',
                'view-subscription' => 'View Subscription',
                'delete-subscription' => 'Delete Subscription',
            ]
        );

        if ($option === 'view-subscription') {
            $connector = new StravaConnector();
            $request = new ViewSubscription();

            $response = $connector->send($request);

            if ($response->failed()) {
                error($response->body());
            }

            if ($response->successful()) {
                table(
                    headers: ['Id', 'Resource State', 'Application ID', 'Callback URL', 'Created At', 'Updated At'],
                    rows: array_map(function ($row) {
                        return (array) $row;
                    }, (array) json_decode($response->body())),
                );
            }
        }

        if ($option === 'delete-subscription') {
            $subscriptionId = text(label: 'What is your subscription id?', required: true);

            $connector = new StravaConnector();
            $request = new DeleteSubscription($subscriptionId);

            $response = $connector->send($request);

            if ($response->failed()) {
                error($response->body());
            }

            if ($response->successful()) {
                info($response->body());
            }
        }

        if ($option === 'create-subscription') {
            $sharingUrl = text(label: 'What is the sharing URL?', required: true);

            $connector = new StravaConnector();
            $request = new CreateSubscription(domain: $sharingUrl);

            $response = $connector->debug()->send($request);

            if ($response->failed()) {
                error($response->body());
            }

            if ($response->successful()) {
                info($response->body());
            }
        }
    }
}
