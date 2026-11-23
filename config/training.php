<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Timezone
    |--------------------------------------------------------------------------
    |
    | Runs are matched to the plan by the date they started in this timezone,
    | so a run at 00:30 in summer counts for that day, not the one before.
    |
    */

    'timezone' => env('TRAINING_TIMEZONE', 'Europe/London'),

    /*
    |--------------------------------------------------------------------------
    | Pace window
    |--------------------------------------------------------------------------
    |
    | Scheduled run times are estimated from activities started inside this
    | many months. Older runs stay stored and are left out of the averages.
    |
    */

    'pace_months' => (int) env('TRAINING_PACE_MONTHS', 6),

    /*
    |--------------------------------------------------------------------------
    | Target paces
    |--------------------------------------------------------------------------
    |
    | Seconds per kilometre used when recent activities do not include that
    | effort. A recorded average replaces the target. Easy is left unset so
    | it stays the average of recent runs.
    |
    | Marathon pace is 6:24/km. Tempo is 6:00/km, the middle of 5:50–6:10.
    |
    */

    'paces' => [
        'marathon pace' => (6 * 60) + 24,
        'tempo' => 6 * 60,
    ],

];
