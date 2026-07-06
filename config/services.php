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

    // ── WhatsApp Cloud API (Meta) ─────────────────────────────────────────────
    'whatsapp' => [
        'token'                => env('WHATSAPP_TOKEN'),
        'phone_number_id'      => env('WHATSAPP_PHONE_NUMBER_ID'),
        'waba_id'              => env('WHATSAPP_WABA_ID'),
        'meta_business_id'     => env('WHATSAPP_META_BUSINESS_ID'),
        'display_phone_number' => env('WHATSAPP_DISPLAY_PHONE_NUMBER'),
        'verified_name'        => env('WHATSAPP_VERIFIED_NAME'),
        'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN', 'mi_token_secreto'),
    ],

    // ── Baileys WhatsApp (Personal - Sin costos Meta) ──────────────────────────
    'baileys' => [
        'url'   => env('BAILEYS_URL', 'http://localhost:3333'),
        'token' => env('BAILEYS_TOKEN'),
    ],

];
