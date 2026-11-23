<?php

namespace App\Observers;

use App\Models\Activity;
use App\Training\RunPaces;

class ActivityObserver
{
    public function saved(Activity $activity): void
    {
        RunPaces::recompute();
    }

    public function deleted(Activity $activity): void
    {
        RunPaces::recompute();
    }
}
