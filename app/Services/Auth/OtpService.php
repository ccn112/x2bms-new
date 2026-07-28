<?php

namespace App\Services\Auth;

use App\Mail\OtpCodeMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Phát hành / kiểm tra mã OTP. Mã nằm ở cache kèm TTL và bộ đếm số lần sai.
 *
 * 🚨 GỬI THẬT (bổ sung 2026-07-28): trước đây hàm [request] chỉ cache mã rồi để
 * lại `TODO dispatch SendOtpNotification` — nghĩa là **không gửi đi đâu cả**. Ở
 * dev vẫn test được nhờ `dev_code`, nhưng lên production `dev_code` là null nên
 * đăng ký / đăng nhập OTP / đặt lại mật khẩu sẽ chết hoàn toàn. Nay kênh `email`
 * gửi thật qua [OtpCodeMail]; kênh `phone` vẫn chưa có gateway SMS (ghi log rõ
 * để không tưởng là đã gửi).
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

        $sent = $this->deliver($channel, $destination, $code, $purpose, (int) $cfg['ttl_seconds']);

        return [
            'sent' => $sent,
            'expires_in' => $cfg['ttl_seconds'],
            'dev_code' => (! app()->isProduction() && $cfg['expose_code_in_dev']) ? $code : null,
        ];
    }

    /**
     * Gửi mã. Lỗi SMTP KHÔNG được làm sập request: mã vẫn nằm trong cache, người
     * dùng bấm "Gửi lại mã" là xong; đổi lại ta trả `sent=false` để app nói thật
     * là chưa gửi được thay vì bắt họ ngồi chờ một email không tới.
     */
    private function deliver(string $channel, string $destination, string $code, string $purpose, int $ttl): bool
    {
        if ($channel !== 'email') {
            // Chưa có gateway SMS/Zalo — ghi log để vận hành thấy ngay.
            Log::warning('OTP channel chưa hỗ trợ gửi thật', [
                'channel' => $channel, 'purpose' => $purpose,
            ]);

            return false;
        }

        try {
            Mail::to($destination)->send(new OtpCodeMail($code, $purpose, $ttl));

            return true;
        } catch (\Throwable $e) {
            Log::error('Gửi email OTP thất bại', [
                'purpose' => $purpose,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
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
