<?php
return [

    'STRIPE_SECRET'         => env('STRIPE_SECRET'),
    'STRIPE_WEBHOOK_SECRET' => env('STRIPE_WEBHOOK_SECRET'),
    'APP_URL'               => env('APP_URL'),
    'APP_NAME'              => env('APP_NAME'),
    'X-API-KEY'             => env('X-API-KEY'),
    'name'                  => env('APP_NAME'),
    'mail'                  => env('MAIL_FROM_ADDRESS'),
    'default_lang'          => env('DEFAULT_LANG', 'it'),

    'WA_TO'                 => env('WA_TO'),
    'WA_ID'                 => env('WA_ID'),
    'WA_N'                  => env('WA_N'),

    
];
