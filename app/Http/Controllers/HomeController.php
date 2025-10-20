<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Home', [
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
