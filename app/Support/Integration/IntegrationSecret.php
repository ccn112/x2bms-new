<?php

namespace App\Support\Integration;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * Batch 08 — secret handling for the Integration Center.
 *
 * Rules: secrets are never persisted or displayed in clear after creation/rotation.
 *  - `encrypt`/`decrypt`  — reversible storage for credentials that must be replayed
 *     to the provider (Crypt = app key, AES-256).
 *  - `hash`               — one-way digest for API-key / webhook secrets we only ever
 *     need to *verify*, never reveal (sha256; high-entropy input so no salt needed).
 *  - `mask`               — the only thing safe to show in a table/detail.
 */
class IntegrationSecret
{
    public function encrypt(string $plain): string
    {
        return Crypt::encryptString($plain);
    }

    public function decrypt(string $payload): string
    {
        return Crypt::decryptString($payload);
    }

    public function hash(string $plain): string
    {
        return hash('sha256', $plain);
    }

    /** Masked summary e.g. "sk-le…7f9c" — first 4 + last 4, middle elided. */
    public function mask(string $plain): string
    {
        $len = strlen($plain);
        if ($len <= 8) {
            return str_repeat('•', max($len, 4));
        }

        return substr($plain, 0, 4).'…'.substr($plain, -4);
    }

    public function generateApiSecret(): string
    {
        return 'sk_'.Str::random(40);
    }

    public function generateClientId(): string
    {
        return 'clt_'.Str::lower(Str::random(8));
    }

    public function generateSigningSecret(): string
    {
        return 'whsec_'.Str::random(32);
    }
}
