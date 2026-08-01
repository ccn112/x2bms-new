<?php

namespace App\Console\Commands;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Console\Command;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

/**
 * Gửi 1 push THỬ tới token (hoặc mọi thiết bị của user theo email). Dùng trực
 * tiếp service account — KHÔNG phụ thuộc FCM_ENABLED — để test được kể cả khi
 * chưa bật đại trà.
 *
 *   php artisan push:test --token=xxxxx
 *   php artisan push:test --email=test.cudan1@x2bms.vn
 */
class PushTest extends Command
{
    protected $signature = 'push:test
        {--token= : FCM token đích}
        {--email= : Gửi tới mọi thiết bị của user có email này}
        {--title=X2BMS : Tiêu đề}
        {--body=Thông báo thử từ server ✅ : Nội dung}';

    protected $description = 'Gửi push thử qua FCM (service account, bỏ qua FCM_ENABLED)';

    public function handle(): int
    {
        $creds = config('services.firebase.credentials');
        if (! is_string($creds) || ! file_exists($creds)) {
            $this->error("Không thấy service account: {$creds}");

            return self::FAILURE;
        }

        $tokens = [];
        if ($this->option('token')) {
            $tokens[] = $this->option('token');
        }
        if ($email = $this->option('email')) {
            $user = User::where('email', $email)->first();
            if (! $user) {
                $this->error("Không thấy user {$email}");

                return self::FAILURE;
            }
            $tokens = array_merge($tokens, DeviceToken::where('user_id', $user->id)->pluck('token')->all());
        }
        $tokens = array_values(array_unique(array_filter($tokens)));
        if (empty($tokens)) {
            $this->error('Chưa có token nào (truyền --token hoặc --email có thiết bị đã đăng ký).');

            return self::FAILURE;
        }

        $messaging = (new Factory)->withServiceAccount($creds)->createMessaging();
        $message = CloudMessage::new()
            ->withNotification(Notification::create($this->option('title'), $this->option('body')))
            ->withData(['channel' => 'system', 'sentAt' => (string) now()->timestamp]);

        $this->info('Gửi tới '.count($tokens).' token...');
        $report = $messaging->sendMulticast($message, $tokens);
        $this->info('Thành công: '.$report->successes()->count());
        $this->info('Thất bại : '.$report->failures()->count());
        foreach ($report->failures()->getItems() as $f) {
            $this->warn('  - '.$f->error()?->getMessage());
        }

        return self::SUCCESS;
    }
}
