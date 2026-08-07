<?php

namespace App\Services\Notifications;

/**
 * Ước tính chi phí chiến dịch theo giá kênh (config, spec 04 §Cost). VND số nguyên
 * (chốt tiền X2: số nguyên). Chi phí THẬT lưu từ provider response ở delivery log.
 */
class CampaignCostEstimator
{
    /**
     * @param list<string> $channels kênh đã bật
     * @return array{total:int,paid:int,by_channel:array<string,int>}
     */
    public function estimate(array $channels, int $recipientCount): array
    {
        $pricing = (array) config('x2_communication.channel_pricing', []);
        $paidChannels = (array) config('x2_communication.paid_channels', []);

        $byChannel = [];
        $total = 0;
        $paid = 0;
        foreach ($channels as $channel) {
            $unit = (int) ($pricing[$channel] ?? 0);
            $cost = $unit * max(0, $recipientCount);
            $byChannel[$channel] = $cost;
            $total += $cost;
            if (in_array($channel, $paidChannels, true)) {
                $paid += $cost;
            }
        }

        return ['total' => $total, 'paid' => $paid, 'by_channel' => $byChannel];
    }
}
