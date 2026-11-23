<?php

namespace App\Models;

use App\Training\RouteTime;
use Database\Factories\StravaRouteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StravaRoute extends Model
{
    /** @use HasFactory<StravaRouteFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'strava_id',
        'name',
        'description',
        'distance_metres',
        'elevation_gain_metres',
        'time',
    ];

    public function kilometres(): float
    {
        return round($this->distance_metres / 1000, 1);
    }

    public function url(): string
    {
        return "https://www.strava.com/routes/{$this->strava_id}";
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'distance_metres' => 'integer',
            'elevation_gain_metres' => 'integer',
            'time' => RouteTime::class,
        ];
    }
}
