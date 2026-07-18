<?php

namespace App\Services\Auth;

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Issues and rotates the mobile access/refresh token pair (Sanctum-backed).
 *
 * Design (docs/ARCHITECTURE_X2_PLATFORM_V1.md §4.2):
 *  - access token : short TTL, carries the person's capability abilities (resident/staff).
 *  - refresh token: long TTL, single ability `token:refresh`, device-bound via token name.
 *  - refresh ROTATES: the old pair for that device is revoked and a fresh pair issued,
 *    so a leaked refresh token is invalidated on next legitimate use.
 */
class TokenService
{
    private const ACCESS_PREFIX = 'mobile-access:';
    private const REFRESH_PREFIX = 'mobile-refresh:';

    /**
     * @return array{access_token:string, refresh_token:string, access_expires_at:string, refresh_expires_at:string, abilities:array<int,string>}
     */
    public function issuePair(User $user, string $deviceId): array
    {
        // Never keep more than one live pair per device.
        $this->revokeDevice($user, $deviceId);

        $cfg = config('mobile.tokens');
        $accessExpires = now()->addMinutes($cfg['access_ttl_minutes']);
        $refreshExpires = now()->addMinutes($cfg['refresh_ttl_minutes']);

        $abilities = $user->tokenAbilities();

        $access = $user->createToken(
            self::ACCESS_PREFIX.$deviceId,
            array_merge($abilities, [$cfg['access_ability_marker']]),
            $accessExpires,
        );

        $refresh = $user->createToken(
            self::REFRESH_PREFIX.$deviceId,
            [$cfg['refresh_ability']],
            $refreshExpires,
        );

        return [
            'access_token' => $access->plainTextToken,
            'refresh_token' => $refresh->plainTextToken,
            'access_expires_at' => $accessExpires->toIso8601String(),
            'refresh_expires_at' => $refreshExpires->toIso8601String(),
            'abilities' => $abilities,
        ];
    }

    /** Rotate: called after a valid refresh token authenticates. Returns a brand-new pair. */
    public function rotate(User $user, string $deviceId): array
    {
        return $this->issuePair($user, $deviceId);
    }

    /** Revoke every mobile token for a device (used on logout and before re-issue). */
    public function revokeDevice(User $user, string $deviceId): void
    {
        $user->tokens()
            ->whereIn('name', [self::ACCESS_PREFIX.$deviceId, self::REFRESH_PREFIX.$deviceId])
            ->delete();
    }

    /** Device id encoded in a token's name, or null if not a mobile token. */
    public function deviceIdFromToken(PersonalAccessToken $token): ?string
    {
        foreach ([self::ACCESS_PREFIX, self::REFRESH_PREFIX] as $prefix) {
            if (str_starts_with($token->name, $prefix)) {
                return substr($token->name, strlen($prefix));
            }
        }

        return null;
    }
}
