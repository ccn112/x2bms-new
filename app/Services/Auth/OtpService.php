<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * OTP issue/verify skeleton. Codes live in the cache with a TTL and an attempt counter.
 *
 * NOTE: no SMS/Zalo gateway is wired yet (Phase 0). In non-production the generated code
 * is surfaced to the caller (config mobile.otp.expose_code_in_dev) so the flow is testable;
 * in production, sending must be delegated to a queued notification channel.
 */
class OtpService
{
    /** @return array{sent:bool, expires_in:int, dev_code:?string} */
    public function request(string $channel, string $destination, string $purpose): array
    {
        $cfg = config('mobile.otp');
        $code = str_pad((string) random_int(0, 10 ** $cfg['length'] - 1), $cfg['length'], '0', STR_PAD_LEFT);

        Cache::put($this->key($channel, $destination, $purpose), [
            'code' => $code,
            'attempts' => 0,
        ], $cfg['ttl_seconds']);

        // TODO(Phase 0.x): dispatch SendOtpNotification($channel, $destination, $code) on queue.

        return [
            'sent' => true,
            'expires_in' => $cfg['ttl_seconds'],
            'dev_code' => (! app()->isProduction() && $cfg['expose_code_in_dev']) ? $code : null,
        ];
    }

    /** @return array{valid:bool, reason:?string} */
    public function verify(string $channel, string $destination, string $purpose, string $code): array
    {
        $cfg = config('mobile.otp');
        $key = $this->key($channel, $destination, $purpose);
        $entry = Cache::get($key);

        if (! $entry) {
            return ['valid' => false, 'reason' => 'expired'];
        }
        if ($entry['attempts'] >= $cfg['max_attempts']) {
            Cache::forget($key);

            return ['valid' => false, 'reason' => 'too_many_attempts'];
        }
        if (! hash_equals($entry['code'], $code)) {
            $entry['attempts']++;
            Cache::put($key, $entry, $cfg['ttl_seconds']);

            return ['valid' => false, 'reason' => 'mismatch'];
        }

        Cache::forget($key);

        return ['valid' => true, 'reason' => null];
    }

    private function key(string $channel, string $destination, string $purpose): string
    {
        return 'otp:'.$purpose.':'.$channel.':'.Str::lower($destination);
    }
}
