<?php

/*
| Mobile / /api/v1 tunables. See docs/ARCHITECTURE_X2_PLATFORM_V1.md §4.2.
| All values overridable via env so nothing sensitive/behavioural is hardcoded.
*/
return [

    'tokens' => [
        // Short-lived access token; refresh mutex on the client handles the churn.
        'access_ttl_minutes' => (int) env('MOBILE_ACCESS_TTL_MINUTES', 30),
        // Long-lived, rotating refresh token, device-bound.
        'refresh_ttl_minutes' => (int) env('MOBILE_REFRESH_TTL_MINUTES', 60 * 24 * 30),
        'access_ability_marker' => 'token:access',
        'refresh_ability' => 'token:refresh',
    ],

    'otp' => [
        'length' => 6,
        'ttl_seconds' => (int) env('MOBILE_OTP_TTL_SECONDS', 300),
        'max_attempts' => (int) env('MOBILE_OTP_MAX_ATTEMPTS', 5),
        // In non-production the code is returned in the response for testing (no SMS gateway wired yet).
        'expose_code_in_dev' => (bool) env('MOBILE_OTP_EXPOSE_DEV', true),
    ],

    'min_app_version' => env('MOBILE_MIN_APP_VERSION', '1.0.0'),

];
