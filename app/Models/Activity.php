<?php

namespace App\Models;

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
        ];
    }
}
