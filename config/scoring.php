<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, SparkPost and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'url' => env('SCORING_URL','https://online.impact.ru.com/api/v1/impact/verification'),
    'key' => env('SCORING_KEY','ahouUuOw.NGgcffnveVZPiWFBhRoxHPAUW5Cvkk0t'),
];
