<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\BuildingNotificationChannel;
use App\Models\Resident;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * ADR-002 — cấu hình kênh gửi theo TÒA cho các tòa của 2 TK test, để kiểm màn
 * "Cấu hình kênh gửi" + hành vi cổng chờ trong sổ gửi:
 *   - email    : ĐANG HOẠT ĐỘNG (gửi thật qua Elastic Email) + from riêng của tòa.
 *   - zalo/whatsapp/telegram/xspace : CỔNG CHỜ (đã khai tham số mẫu, chưa đấu nối)
 *     → sổ gửi ghi 'provider_pending' thay vì 'provider_not_configured'.
 *
 * Idempotent (updateOrCreate theo building+channel). PHỤ THUỘC: CommunityTestResidentsSeeder.
 *
 *   php artisan db:seed --class=BuildingChannelConfigDemoSeeder --force
 */
class BuildingChannelConfigDemoSeeder extends Seeder
{
    private const ACCOUNTS = ['test.cudan1@x2bms.vn', 'test.cudan2@x2bms.vn'];

    public function run(): void
    {
        foreach ($this->testBuildings() as $building) {
            $this->seedForBuilding($building);
        }
    }

    /** @return \Illuminate\Support\Collection<int, Building> */
    private function testBuildings()
    {
        $userIds = User::whereIn('email', self::ACCOUNTS)->pluck('id');
        $buildingIds = Resident::withoutGlobalScopes()
            ->whereIn('user_id', $userIds)
            ->whereNotNull('building_id')
            ->pluck('building_id')->unique();

        if ($buildingIds->isEmpty()) {
            $this->command?->warn('Chưa có tòa cho TK test. Chạy CommunityTestResidentsSeeder trước.');
        }

        return Building::withoutGlobalScopes()->whereIn('id', $buildingIds)->get();
    }

    private function seedForBuilding(Building $building): void
    {
        $slug = 'toa'.$building->id;

        $configs = [
            // Email — gửi THẬT, từ địa chỉ riêng của tòa.
            'email' => [
                'status' => BuildingNotificationChannel::STATUS_ACTIVE,
                'config' => [
                    'from_name' => 'BQL '.($building->name ?? ('Tòa '.$building->id)),
                    'from_address' => 'noreply@xhub.com.vn',
                    'reply_to' => 'bql.'.$slug.'@xhub.com.vn',
                ],
                'note' => 'Elastic Email — gửi thật.',
            ],
            // Cổng chờ — tham số mẫu để test màn cấu hình + trạng thái provider_pending.
            'zalo' => [
                'status' => BuildingNotificationChannel::STATUS_PENDING,
                'config' => ['oa_id' => 'OA-'.$slug, 'access_token' => 'DEMO_ZALO_TOKEN', 'template_id' => 'tpl_thongbao'],
                'note' => 'Cổng chờ Zalo ZNS — chờ chốt OA + template.',
            ],
            'whatsapp' => [
                'status' => BuildingNotificationChannel::STATUS_PENDING,
                'config' => ['phone_number_id' => 'PNID-'.$slug, 'access_token' => 'DEMO_WA_TOKEN', 'template_namespace' => 'x2bms'],
                'note' => 'Cổng chờ WhatsApp Cloud API.',
            ],
            'telegram' => [
                'status' => BuildingNotificationChannel::STATUS_PENDING,
                'config' => ['bot_token' => 'DEMO_BOT_TOKEN', 'default_chat_id' => '@'.$slug.'_channel'],
                'note' => 'Cổng chờ Telegram Bot.',
            ],
            'xspace' => [
                'status' => BuildingNotificationChannel::STATUS_PENDING,
                'config' => ['workspace_id' => 'ws-'.$slug, 'webhook_url' => 'https://x.space/api/hooks/'.$slug, 'api_key' => 'DEMO_XSPACE_KEY'],
                'note' => 'Cổng chờ X.Space (hệ sinh thái xhub).',
            ],
        ];

        foreach ($configs as $channel => $data) {
            BuildingNotificationChannel::withoutGlobalScopes()->updateOrCreate(
                ['building_id' => $building->id, 'channel' => $channel],
                [
                    'tenant_id' => $building->tenant_id,
                    'enabled' => true,
                    'status' => $data['status'],
                    'config' => $data['config'],
                    'note' => $data['note'],
                    'verified_at' => $data['status'] === BuildingNotificationChannel::STATUS_ACTIVE ? now() : null,
                ],
            );
        }

        $this->command?->info("Cấu hình kênh cho tòa #{$building->id}: email(hoạt động)+zalo/whatsapp/telegram/xspace(cổng chờ).");
    }
}
