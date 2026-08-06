<?php

declare(strict_types=1);

return [
    'default_locale' => env('APP_LOCALE', 'vi-VN'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'vi-VN'),
    'enabled_locales' => array_values(array_filter(array_map(
        static fn (string $value): string => trim($value),
        explode(',', (string) env('X2_ENABLED_LOCALES', 'vi-VN,en-US'))
    ))),
    'device_header' => 'X-Device-Locale',
    'remote_pack' => [
        'enabled' => (bool) env('X2_REMOTE_TRANSLATION_PACK_ENABLED', true),
        'cache_ttl_seconds' => (int) env('X2_TRANSLATION_PACK_CACHE_TTL', 3600),
        'critical_namespaces' => [
            'x2.api',
            'x2.shared',
        ],
    ],
    'dynamic_translation' => [
        'enabled' => (bool) env('X2_DYNAMIC_TRANSLATION_ENABLED', false),
        'provider' => env('X2_TRANSLATION_PROVIDER', 'null'),
        'max_attempts' => (int) env('X2_TRANSLATION_JOB_MAX_ATTEMPTS', 3),
        'timeout_seconds' => (int) env('X2_TRANSLATION_TIMEOUT', 30),
        'blocked_content_types' => [
            'statement',
            'invoice',
            'receipt',
            'contract',
            'handover_minutes',
            'voting_resolution',
            'emergency_alert',
            'fire_safety_instruction',
        ],
    ],
    'overrides' => [
        'allowed_namespaces' => [
            'x2.resident_app',
            'x2.bql_app',
            'x2.bql_web',
            'x2.notifications',
            'x2.content',
        ],
    ],
];
