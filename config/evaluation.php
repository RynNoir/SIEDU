<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Anonymity Minimum Respondents
    |--------------------------------------------------------------------------
    |
    | Minimum number of submitted evaluations required for a course class
    | assignment before its "kesan & saran" (impressions/suggestions) are
    | shown to lecturers/kaprodi, to preserve student anonymity.
    |
    */

    'anonymity_min_respondents' => env('EVALUATION_ANONYMITY_MIN_RESPONDENTS', 5),

    /*
    |--------------------------------------------------------------------------
    | Default Password
    |--------------------------------------------------------------------------
    |
    | Default password assigned to accounts created by admin (students and
    | lecturers). Users are forced to change it on first login via the
    | must_change_password flag.
    |
    */

    'default_password' => env('EVALUATION_DEFAULT_PASSWORD', 'password'),

];
