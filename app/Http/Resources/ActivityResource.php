<?php

namespace App\Http\Resources;

use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Activity */
class ActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'activity_id' => $this->activity_id,
            'name' => $this->name,
            'type' => $this->type,
            'distance' => $this->distance,
            'moving_time' => $this->moving_time,
            'elapsed_time' => $this->elapsed_time,
            'total_elevation_gain' => $this->total_elevation_gain,
            'started_at' => $this->started_at,
            'path' => $this->path,
            'max_elevation' => $this->max_elevation,
            'min_elevation' => $this->min_elevation,
            'description' => $this->description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
