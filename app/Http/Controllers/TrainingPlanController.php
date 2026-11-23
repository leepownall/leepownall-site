<?php

namespace App\Http\Controllers;

use App\Training\MarathonPlan;
use App\Training\WeekKind;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrainingPlanController extends Controller
{
    public function __invoke(Request $request, ?int $week = null): Response
    {
        if ($week === null) {
            $page = MarathonPlan::presentFor(now(), $request->user() !== null);

            return $page === null ? Inertia::render('TrainingEmpty') : Inertia::render('Training', $page);
        }

        $page = MarathonPlan::present(WeekKind::Plan, $week, now(), $request->user() !== null);

        abort_if($page === null, 404);

        return Inertia::render('Training', $page);
    }

    public function prep(Request $request, int $prep): Response
    {
        $page = MarathonPlan::present(WeekKind::Prep, $prep, now(), $request->user() !== null);

        abort_if($page === null, 404);

        return Inertia::render('Training', $page);
    }
}
