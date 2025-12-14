<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use App\Models\BodyBattery;
use App\Models\Sleep;
use App\Models\Step;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

use function number_format;

class HomeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $stepsModel = Step::query()->latest()->first();
        $sleepModel = Sleep::query()->latest()->first();
        $bodyBatteryModel = BodyBattery::query()->latest()->first();

        return Inertia::render('Home', [
            'steps' => [
                'value' => number_format($stepsModel?->amount ?? 0),
                'updated_at' => $stepsModel?->updated_at,
            ],
            'sleep' => [
                'value' => number_format($sleepModel?->amount ?? 0),
                'updated_at' => $sleepModel?->updated_at,
            ],
            'bodyBattery' => [
                'value' => number_format($bodyBatteryModel?->amount ?? 0),
                'updated_at' => $bodyBatteryModel?->updated_at,
            ],
            'activity' => ActivityResource::make(
                Activity::query()
                    ->select([
                        'activity_id',
                        'name',
                        'type',
                        'distance',
                        'moving_time',
                        'elapsed_time',
                        'total_elevation_gain',
                        'started_at',
                    ])
                    ->latest('started_at')
                    ->first(),
            ),
        ]);
    }
}
