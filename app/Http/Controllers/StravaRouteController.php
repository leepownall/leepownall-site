<?php

namespace App\Http\Controllers;

use App\Models\StravaRoute;
use App\Training\RouteTime;
use App\Training\StravaRoutes;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

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

    public function sync(): RedirectResponse
    {
        try {
            $count = StravaRoutes::sync();
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['routes' => $exception->getMessage()]);
        } catch (HttpClientException) {
            throw ValidationException::withMessages(['routes' => 'Strava did not return your routes. Try again in a minute.']);
        }

        return back()->with('routesSynced', $count);
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
