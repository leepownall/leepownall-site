<?php

namespace App\Training;

enum RouteTime: string
{
    case Daytime = 'daytime';
    case Nighttime = 'nighttime';

    /**
     * A route is for night running when its Strava name or description
     * mentions night or lit, such as "Canal loop (lit)". Others are daytime.
     */
    public static function fromStrava(string $name, ?string $description): self
    {
        return preg_match('/\b(night|nighttime|night-time|lit)\b/i', $name.' '.$description) === 1
            ? self::Nighttime
            : self::Daytime;
    }
}
