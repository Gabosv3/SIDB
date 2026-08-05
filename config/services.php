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

    'ycloud' => [
        'api_key' => env('YCLOUD_API_KEY'),
        'webhook_secret' => env('YCLOUD_WEBHOOK_SECRET'),
    ],

    'meta_whatsapp' => [
        'app_id' => env('META_WHATSAPP_APP_ID'),
        'app_secret' => env('META_WHATSAPP_APP_SECRET'),
        'verify_token' => env('META_WHATSAPP_VERIFY_TOKEN'),
        'system_user_token' => env('META_WHATSAPP_SYSTEM_USER_TOKEN'),
        'config_id' => env('META_WHATSAPP_CONFIG_ID'),
    ],

];
