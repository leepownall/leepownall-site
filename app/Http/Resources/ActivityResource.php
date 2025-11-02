<?php

namespace App\Http\Resources;

use App\Models\Activity;
use App\ValueObjects\Distance;
use App\ValueObjects\Duration;
use App\ValueObjects\Elevation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/** @mixin Activity */
class ActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->whenHas('id'),
            'activity_id' => $this->whenHas('activity_id'),
            'name' => $this->whenHas('name'),
            'type' => $this->whenHas('type'),
            'distance' => $this->whenHas('distance', fn (Distance $value): float => $value->asKilometers()),
            'moving_time' => $this->whenHas('moving_time', fn (Duration $value): string => $value->asReadable()),
            'elapsed_time' => $this->whenHas('elapsed_time', fn (Duration $value): string => $value->asReadable()),
            'total_elevation_gain' => $this->whenHas('total_elevation_gain', fn (Elevation $value): string => $value->asMeters()),
            'started_at' => $this->whenHas('started_at', fn (Carbon $value): string => $value->diffForHumans()),
            'path' => $this->whenHas('path'),
            'max_elevation' => $this->whenHas('max_elevation', fn (Elevation $value): string => $value->asMeters()),
            'min_elevation' => $this->whenHas('min_elevation', fn (Elevation $value): string => $value->asMeters()),
            'description' => $this->whenHas('description'),
        ];
    }
}
