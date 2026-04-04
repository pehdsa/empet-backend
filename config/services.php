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

    'onesignal' => [
        'app_id' => env('ONESIGNAL_APP_ID'),
        'rest_api_key' => env('ONESIGNAL_REST_API_KEY'),
    ],

    'match_ai' => [
        'enabled' => env('MATCH_AI_ENABLED', false),
        'provider' => env('MATCH_AI_PROVIDER', 'null'),
        'timeout' => (int) env('MATCH_AI_TIMEOUT', 15),
        'max_photos' => (int) env('MATCH_AI_MAX_PHOTOS', 3),
        'min_base_score' => (int) env('MATCH_AI_MIN_BASE_SCORE', 25),
        'max_evaluations_per_report' => (int) env('MATCH_AI_MAX_PER_REPORT', 5),
        'openai' => [
            'base_url' => env('MATCH_AI_OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('MATCH_AI_OPENAI_MODEL', 'gpt-4o'),
        ],
        'log' => [
            'fixed_score' => (float) env('MATCH_AI_LOG_FIXED_SCORE', 70.0),
            'fixed_confidence' => (float) env('MATCH_AI_LOG_FIXED_CONFIDENCE', 0.8),
        ],
    ],

];
