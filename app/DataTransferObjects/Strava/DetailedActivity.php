<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Strava;

use App\Actions\GeneratePathFromPolyline;
use App\Enums\ActivityType;
use App\Enums\IntegrationType;
use Carbon\Carbon;
use MatanYadaev\EloquentSpatial\Objects\LineString;

final class DetailedActivity
{
    public function __construct(
        public int $activityId,
        public ?string $name,
        public ?ActivityType $type,
        public float $distance,
        public int $movingTime,
        public int $elapsedTime,
        public float $totalElevationGain,
        public Carbon $startedAt,
        public LineString $path,
        public ?float $maxElevation = null,
        public ?float $minElevation = null,
        public ?string $description = null,
    ) {}

    public static function fromResponse(array $response): static
    {
        $path = (new GeneratePathFromPolyline)($response['map']['polyline']);

        return new self(
            activityId: $response['id'],
            name: $response['name'],
            type: ActivityType::from($response['type']),
            distance: $response['distance'],
            movingTime: $response['moving_time'],
            elapsedTime: $response['elapsed_time'],
            totalElevationGain: $response['total_elevation_gain'],
            startedAt: Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $response['start_date']),
            path: $path,
            maxElevation: $response['elev_high'],
            minElevation: $response['elev_low'],
            description: $response['description'],
        );
    }

    public function hasNoPath(): bool
    {
        return $this->path === null;
    }
}
