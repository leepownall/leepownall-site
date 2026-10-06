<?php

namespace App\Http\Controllers;

use App\Training\MarathonPlan;
use Inertia\Inertia;
use Inertia\Response;

class TrainingPlanController extends Controller
{
    public function __invoke(?int $week = null): Response
    {
        if ($week === null) {
            return Inertia::render('Training', MarathonPlan::presentFor(now()));
        }

        abort_unless($week >= 1 && $week <= MarathonPlan::LENGTH, 404);

        return Inertia::render('Training', MarathonPlan::present($week, now()));
    }

    public function prep(int $prep): Response
    {
        abort_unless($prep >= 1 && $prep <= MarathonPlan::prepLength(), 404);

        return Inertia::render('Training', MarathonPlan::presentPrep($prep, now()));
    }
}
