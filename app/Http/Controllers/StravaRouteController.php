<?php

namespace App\Http\Controllers;

use App\Models\StravaRoute;
use App\Training\RouteTime;
use Inertia\Inertia;
use Inertia\Response;

class StravaRouteController extends Controller
{
    public function index(): Response
    {
        $routes = StravaRoute::query()->orderBy('distance_metres')->get();

        return Inertia::render('Routes', [
            'nighttime' => $routes->where('time', RouteTime::Nighttime)->map($this->present(...))->values(),
            'daytime' => $routes->where('time', RouteTime::Daytime)->map($this->present(...))->values(),
        ]);
    }

    /**
     * @return array{id: int, name: string, km: float, climb: int, url: string}
     */
    private function present(StravaRoute $route): array
    {
        return [
            'id' => $route->id,
            'name' => $route->name,
            'km' => $route->kilometres(),
            'climb' => $route->elevation_gain_metres,
            'url' => $route->url(),
        ];
    }
}
