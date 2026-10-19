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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'academics' => [
        'path'    => env('ACADEMICS_APP_PATH'),   // absolute path to academics/ folder (unused now - see internal_api)
        'main_db' => env('ACADEMICS_MAIN_DB'),    // unused now - see internal_api
        'url'     => env('ACADEMICS_APP_URL'),
    ],

    'internal_api' => [
        'secret' => env('INTERNAL_API_SECRET'),
    ],

    // SMS_FAKE_SEND=true on test copies: messages are recorded as sent without contacting any gateway.
    'sms' => [
        'fake_send' => (bool) env('SMS_FAKE_SEND', false),
        'onfon_dlr_token' => env('SMS_ONFON_DLR_TOKEN'),
    ],

];
