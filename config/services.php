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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    
    'google' => [
    'client_id' => '1000953428878-tv6flqo1g29bln1713uc5616uv940bur.apps.googleusercontent.com',
    'client_secret' => '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',
    'redirect' =>'http://127.0.0.1:8000/api/auth/google/callback',
     ],

     'stripe' => [
     'secret' => 'sk_test_51QwklEASiga6U8fgPIqEWaBAqWclcPr52gwnvQXisH7qZ3fAAxAyuw41UfRxPvJJh7BBTGcNgnrf47RffNlIgIzc00nq805BE3',
     'public' => 'pk_test_51QwklEASiga6U8fg5MHNBeoNWjD4p3KfCQZRfK19QWovhHQGzeZnWvL7asOEqc7mKNKCOkyG8KaaztmTKN5bMXNL00FbGpBGmh',
     ],
];