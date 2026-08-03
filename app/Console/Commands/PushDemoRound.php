<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Services\Resident\NotificationPushDispatcher;
use Database\Seeders\PushRoundDemoSeeder;
use Illuminate\Console\Command;

/**
 * Bắn "một vòng" push demo — đẩy các thông báo do {@see PushRoundDemoSeeder} dựng
 * (mỗi kênh một cái) qua ĐÚNG đường production {@see NotificationPushDispatcher}:
 * tôn trọng tuỳ chọn KÊNH của cư dân (kênh tắt → bỏ qua, khẩn cấp luôn gửi), gắn
 * ảnh bìa (BigPicture) nếu có, và data deep-link tới màn chi tiết thông báo.
 *
 *   php artisan push:demo-round                 # bắn tất cả các kênh
 *   php artisan push:demo-round --only=community # chỉ một kênh
 *
 * Chỉ chạm thông báo có code `DEMO-PUSH-*` nên không đụng thông báo thật.
 */
class PushDemoRound extends Command
{
    protected $signature = 'push:demo-round
        {--only= : Chỉ bắn một kênh (emergency|billing|maintenance|security|feedback|amenity|community|announcement|system)}';

    protected $description = 'Bắn một vòng push demo qua NotificationPushDispatcher (đúng đường production)';

    public function handle(NotificationPushDispatcher $dispatcher): int
    {
        $query = Notification::query()
            ->where('code', 'like', PushRoundDemoSeeder::CODE_PREFIX.'%')
            ->where('status', 'published')
            ->orderBy('id');

        if ($only = $this->option('only')) {
            $query->where('code', PushRoundDemoSeeder::CODE_PREFIX.$only);
        }

        $notifications = $query->get();
        if ($notifications->isEmpty()) {
            $this->error('Chưa có thông báo demo. Chạy: php artisan db:seed --class=PushRoundDemoSeeder');

            return self::FAILURE;
        }

        $total = 0;
        foreach ($notifications as $n) {
            // resend: true — công cụ demo cần bắn LẠI mỗi lần chạy (production
            // dispatch() mặc định idempotent, không đẩy trùng).
            $sent = $dispatcher->dispatch($n, resend: true);
            $total += $sent;
            $this->line(sprintf('  [%-12s] %-45s → %d thiết bị', $n->type, mb_strimwidth($n->title, 0, 45, '…'), $sent));
        }

        $this->info("Xong. Tổng số lượt đẩy: {$total}.");
        $this->comment('Máy nào tắt kênh tương ứng trong "Cài đặt thông báo" sẽ KHÔNG nhận kênh đó (trừ Khẩn cấp).');

        return self::SUCCESS;
    }
}
