<?php

namespace App\Training;

/**
 * How a day went against the plan, once it has a run or has passed.
 */
enum DayStatus: string
{
    case Done = 'done';
    case Missed = 'missed';
    case Extra = 'extra';
}
