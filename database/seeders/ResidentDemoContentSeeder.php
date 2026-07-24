<?php

namespace Database\Seeders;

use App\Models\LoyaltyAccount;
use App\Models\LoyaltyTransaction;
use App\Models\Voucher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Dữ liệu demo cho các tab cư dân (Ưu đãi/Cộng đồng/Chợ…) — để app hiển thị nội
 * dung thật khi build với X2_USE_MOCK=false và để verify HTTP thật.
 *
 * Idempotent: dùng updateOrCreate theo khoá tự nhiên (code…). Chạy an toàn nhiều lần.
 * Scope theo tenant 1 / project 1 (khớp ngữ cảnh user cư dân demo #6).
 *
 * Chạy:  php artisan db:seed --class=ResidentDemoContentSeeder
 */
class ResidentDemoContentSeeder extends Seeder
{
    /** Resident demo #6 → resident_id chính (primary). */
    private const DEMO_RESIDENT_ID = 1305;

    public function run(): void
    {
        $this->seedVouchers();
        $this->seedLoyalty();
    }

    /** Tài khoản điểm + hoạt động gần đây cho cư dân demo (tab Ưu đãi — CD-LY-01). */
    private function seedLoyalty(): void
    {
        $account = LoyaltyAccount::withoutGlobalScopes()->updateOrCreate(
            ['resident_id' => self::DEMO_RESIDENT_ID],
            ['tenant_id' => 1, 'points_balance' => 3200, 'tier' => 'silver', 'status' => 'active']
        );

        $activities = [
            ['ref' => 'LY-2026-07-01', 'type' => 'earn', 'points' => 500, 'description' => 'Thanh toán phí quản lý T7', 'at' => '2026-07-05'],
            ['ref' => 'LY-2026-06-15', 'type' => 'redeem', 'points' => -200, 'description' => 'Đổi voucher giảm 10% dịch vụ', 'at' => '2026-06-15'],
            ['ref' => 'LY-2026-06-01', 'type' => 'earn', 'points' => 500, 'description' => 'Thanh toán phí quản lý T6', 'at' => '2026-06-05'],
            ['ref' => 'LY-2026-05-20', 'type' => 'earn', 'points' => 300, 'description' => 'Tham gia sự kiện cộng đồng', 'at' => '2026-05-20'],
        ];
        foreach ($activities as $a) {
            LoyaltyTransaction::updateOrCreate(
                ['loyalty_account_id' => $account->id, 'reference' => $a['ref']],
                [
                    'type' => $a['type'],
                    'points' => $a['points'],
                    'description' => $a['description'],
                    'transacted_at' => Carbon::parse($a['at']),
                ]
            );
        }

        $this->command?->info('  Loyalty: account 3200đ (silver) + 4 hoạt động cho resident #'.self::DEMO_RESIDENT_ID.'.');
    }

    /** Tab Ưu đãi: offers (points_cost=0) + gifts (points_cost>0) + 1 voucher platform rollout. */
    private function seedVouchers(): void
    {
        $now = Carbon::parse('2026-07-01');
        $validTo = Carbon::parse('2026-12-31');

        // Offers của tenant 1 (không cần đổi điểm).
        $offers = [
            ['code' => 'OF-WELCOME10', 'name' => 'Giảm 10% phí gửi xe tháng đầu', 'type' => 'discount', 'value' => '10.00'],
            ['code' => 'OF-GYM50', 'name' => 'Ưu đãi 50% vé gym nội khu', 'type' => 'discount', 'value' => '50.00'],
            ['code' => 'OF-FREEWASH', 'name' => 'Miễn phí 1 lần giặt ủi', 'type' => 'gift', 'value' => '0.00'],
        ];
        foreach ($offers as $o) {
            Voucher::withoutGlobalScopes()->updateOrCreate(
                ['code' => $o['code']],
                [
                    'tenant_id' => 1,
                    'owner_level' => 'tenant',
                    'name' => $o['name'],
                    'type' => $o['type'],
                    'value' => $o['value'],
                    'points_cost' => 0,
                    'quantity' => 100,
                    'valid_from' => $now,
                    'valid_to' => $validTo,
                    'status' => 'active',
                ]
            );
        }

        // Voucher platform (SA) rollout xuống tenant 1 — cư dân tenant 1 sẽ thấy.
        $platform = Voucher::withoutGlobalScopes()->updateOrCreate(
            ['code' => 'PLT-PARTNER-COFFEE'],
            [
                'tenant_id' => null,
                'owner_level' => 'platform',
                'name' => 'Đối tác: Giảm 15% chuỗi cà phê Highlands',
                'type' => 'discount',
                'value' => '15.00',
                'points_cost' => 0,
                'quantity' => 500,
                'valid_from' => $now,
                'valid_to' => $validTo,
                'status' => 'active',
            ]
        );
        DB::table('voucher_tenant')->updateOrInsert(
            ['voucher_id' => $platform->id, 'tenant_id' => 1],
            [
                'starts_at' => $now,
                'ends_at' => $validTo,
                'status' => 'active',
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        $this->command?->info('  Vouchers: 3 offers + 1 platform rollout (tenant 1). Gifts giữ nguyên seed cũ.');
    }
}
