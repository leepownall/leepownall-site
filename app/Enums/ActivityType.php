<?php

declare(strict_types=1);

namespace App\Enums;

enum ActivityType: string
{
    case Hike = 'Hike';
    case Walk = 'Walk';
    case Bike = 'Bike';
    case Ride = 'Ride';
    case Workout = 'Workout';
    case Yoga = 'Yoga';
    case Run = 'Run';
    case Swim = 'Swim';
    case WeightTraining = 'WeightTraining';
}
