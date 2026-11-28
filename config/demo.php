<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Demo Seed Data
    |--------------------------------------------------------------------------
    |
    | The account created by `php artisan db:seed` for local demo data. The
    | DEMO_PASSWORD value should be changed before using these settings
    | anywhere non-local.
    |
    */

    'name' => env('DEMO_NAME', 'Demo User'),

    'email' => env('DEMO_EMAIL', 'demo@example.com'),

    'password' => env('DEMO_PASSWORD', 'password'),

];
