<?php

use Laravel\Sanctum\Sanctum;

return [

    /*
    | Stateful domains — used only for SPA cookie auth. The mobile apps use Bearer
    | tokens (stateless), so this is intentionally limited to first-party web only.
    */
    'stateful' => explode(',', (string) env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s',
        'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
        Sanctum::currentApplicationUrlWithPort(),
    ))),

    // Guards checked before falling back to a Sanctum token.
    'guard' => ['web'],

    /*
    | Default access-token lifetime (minutes). Mobile access tokens are issued with an
    | explicit short expiry per token (see config/mobile.php); this global default is a
    | backstop. null = never expire — we DO NOT rely on that for mobile.
    */
    'expiration' => env('SANCTUM_EXPIRATION'),

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],

];
