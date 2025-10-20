<?php

declare(strict_types=1);

namespace App\Actions;

use GeometryLibrary\PolyUtil;
use MatanYadaev\EloquentSpatial\Objects\LineString;
use MatanYadaev\EloquentSpatial\Objects\Point;

class GeneratePathFromPolyline
{
    public function __invoke(string $polyline): LineString
    {
        $path = PolyUtil::decode($polyline);

        $points = array_map(function ($item): Point {
            return new Point($item['lat'], $item['lng'], srid: 4326);
        }, $path);

        return new LineString($points, srid: 4326);
    }
}
