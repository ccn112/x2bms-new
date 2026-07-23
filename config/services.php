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

    // X2AI Copilot — Anthropic Messages API. Set X2AI_API_KEY (or ANTHROPIC_API_KEY) in .env.
    // Model defaults to a cheaper chat-tier model; override with X2AI_MODEL
    // (e.g. claude-opus-4-8 for the most capable, claude-sonnet-4-6 mid-tier).
    'x2ai' => [
        'key' => env('X2AI_API_KEY', env('ANTHROPIC_API_KEY')),
        'model' => env('X2AI_MODEL', 'claude-haiku-4-5'),
        'base_url' => env('X2AI_BASE_URL', 'https://api.anthropic.com/v1/messages'),
        'version' => '2023-06-01',
        'max_tokens' => (int) env('X2AI_MAX_TOKENS', 1024),

        // Mode 2 — database lookup. Set these when the data API is ready; until
        // then the lookup tool reports "not configured" instead of failing.
        'data_api' => [
            'url' => env('X2AI_DATA_API_URL'),
            'token' => env('X2AI_DATA_API_TOKEN'),
        ],
    ],

    // Air Quality (Home metric AQI). Mặc định Open-Meteo free (không cần key, phi thương mại).
    // Lên prod: đổi ENV sang gói/nguồn có key thương mại (WAQI/IQAir) — không sửa code.
    'aqi' => [
        'provider' => env('AQI_PROVIDER', 'open-meteo'),
        'base_url' => env('AQI_BASE_URL', 'https://air-quality-api.open-meteo.com/v1/air-quality'),
        'api_key' => env('AQI_API_KEY'),
        'cache_ttl' => (int) env('AQI_CACHE_TTL', 3600), // giây
    ],

];
