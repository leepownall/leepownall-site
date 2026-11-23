<?php

namespace App\Training;

enum RunEffort: string
{
    case Easy = 'easy';
    case MarathonPace = 'marathon pace';
    case Tempo = 'tempo';
    case IntervalPace = 'interval pace';
}
