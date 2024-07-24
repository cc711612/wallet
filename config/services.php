<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain'   => env('MAILGUN_DOMAIN'),
        'secret'   => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'exchangeRate' => [
        'domain' => env('EXCHANGERATE_URI'),
        'history' => env('EXCHANGERATE_HISTORY_URI'),
        'apiKey' => env('EXCHANGERATE_API_KEY'),
    ],

    'line'     => [
        'client_id'     => env('LINE_KEY'),
        'client_secret' => env('LINE_SECRET'),
        'notify_client_id'     => env('LINE_NOTIFY_CLIENT'),
        'notify_client_secret' => env('LINE_NOTIFY_SECRET'),
        'redirect'      => sprintf("%s%s", config('app.url'), env('LINE_REDIRECT_URI')),
    ],

    'easysplit' => [
        'domain'     => env('EASYSPLIT_DOMAIN', 'https://easysplit.usongrat.tw/'),
    ],
];
