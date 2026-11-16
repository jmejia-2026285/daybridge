<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'moodle' => [
        'enabled' => (bool) env('MOODLE_ENABLED', false),
        'url' => rtrim((string) env('MOODLE_URL', ''), '/'),
        'token' => env('MOODLE_TOKEN'),
        'timeout' => (int) env('MOODLE_TIMEOUT', 10),
        'cache_minutes' => (int) env('MOODLE_CACHE_MINUTES', 10),
        'user_auth' => env('MOODLE_USER_AUTH', 'manual'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'backup' => [
        'repo' => env('BACKUP_REPO', 'jmejia-2026285/daybridge-respaldo'),
        'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 60),
    ],

];
