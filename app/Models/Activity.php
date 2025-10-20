<?php

namespace App\Models;

use App\Casts\DistanceCast;
use App\Casts\DurationCast;
use App\Casts\ElevationCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'activity_id',
        'name',
        'type',
        'distance',
        'moving_time',
        'elapsed_time',
        'total_elevation_gain',
        'started_at',
        'path',
        'max_elevation',
        'min_elevation',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'distance' => DistanceCast::class,
            'elapsed_time' => DurationCast::class,
            'moving_time' => DurationCast::class,
            'total_elevation_gain' => ElevationCast::class,
            'max_elevation' => ElevationCast::class,
            'min_elevation' => ElevationCast::class,
        ];
    }
}
