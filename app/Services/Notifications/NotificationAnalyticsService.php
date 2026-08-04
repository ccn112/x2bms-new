<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Models\Notification;
use App\Models\NotificationDeliveryLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * 07-10 — Phân tích hiệu quả thông báo. Tính chỉ số từ dữ liệu THẬT (không hardcode):
 *  - Open-rate = read_count / recipient_count (broadcast đọc theo mốc bell + reads).
 *  - Phễu per-kênh từ `notification_delivery_logs` (gửi → nhận → đọc / thất bại / bỏ).
 *
 * Mọi số PHẢI đi qua service này (rule: dashboard totals từ query class test được độc
 * lập). Scope theo `Notification::scopeVisibleTo` nên không lộ số của tenant/dự án khác.
 *
 * Ghi chú "CTR": chưa track sự kiện CLICK riêng → dùng **tỉ lệ đọc/mở** (read) làm proxy
 * hiệu quả; khi có event click deep-link sẽ bổ sung cột riêng.
 */
class NotificationAnalyticsService
{
    /** Kênh trả phí/ngoài để tách chi phí. */
    private const PAID_CHANNELS = ['sms', 'zalo', 'whatsapp'];

    /** @return Builder<Notification> thông báo ĐÃ PHÁT HÀNH trong phạm vi user. */
    public function base(User $user): Builder
    {
        return Notification::query()->visibleTo($user)->where('status', 'published');
    }

    /** @return array{published:int,recipients:int,reads:int,open_rate:float} */
    public function summary(User $user): array
    {
        $b = $this->base($user);
        $published = (clone $b)->count();
        $recipients = (int) (clone $b)->sum('recipient_count');
        $reads = (int) (clone $b)->sum('read_count');

        return [
            'published' => $published,
            'recipients' => $recipients,
            'reads' => $reads,
            'open_rate' => $recipients > 0 ? round($reads / $recipients * 100, 1) : 0.0,
        ];
    }

    /**
     * Phễu giao nhận theo KÊNH từ sổ gửi.
     *
     * @return array<int, array{channel:string,total:int,delivered:int,read:int,failed:int,suppressed:int,pending:int,cost:float,delivery_rate:float,read_rate:float}>
     */
    public function channelBreakdown(User $user): array
    {
        $visibleIds = $this->base($user)->select('id');

        $rows = NotificationDeliveryLog::query()
            ->whereIn('notification_id', $visibleIds)
            ->select('channel', 'status', DB::raw('count(*) as c'), DB::raw('COALESCE(SUM(cost),0) as cost'))
            ->groupBy('channel', 'status')
            ->get();

        $byChannel = [];
        foreach ($rows as $r) {
            $ch = $r->channel ?? 'app';
            $byChannel[$ch] ??= ['channel' => $ch, 'total' => 0, 'delivered' => 0, 'read' => 0, 'failed' => 0, 'suppressed' => 0, 'pending' => 0, 'cost' => 0.0];
            $c = (int) $r->c;
            $byChannel[$ch]['total'] += $c;
            $byChannel[$ch]['cost'] += (float) $r->cost;
            match ($r->status) {
                'sent', 'delivered', 'read' => $byChannel[$ch]['delivered'] += $c,
                'failed', 'bounced' => $byChannel[$ch]['failed'] += $c,
                'suppressed' => $byChannel[$ch]['suppressed'] += $c,
                default => $byChannel[$ch]['pending'] += $c,   // queued
            };
            if ($r->status === 'read') {
                $byChannel[$ch]['read'] += $c;
            }
        }

        return array_values(array_map(function (array $x) {
            $x['delivery_rate'] = $x['total'] > 0 ? round($x['delivered'] / $x['total'] * 100, 1) : 0.0;
            $x['read_rate'] = $x['delivered'] > 0 ? round($x['read'] / $x['delivered'] * 100, 1) : 0.0;

            return $x;
        }, $byChannel));
    }

    /** Tổng chi phí kênh trả phí (đối soát) trong phạm vi user. */
    public function paidCost(User $user): float
    {
        $visibleIds = $this->base($user)->select('id');

        return (float) NotificationDeliveryLog::query()
            ->whereIn('notification_id', $visibleIds)
            ->whereIn('channel', self::PAID_CHANNELS)
            ->sum('cost');
    }
}
