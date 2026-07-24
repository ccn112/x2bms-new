<?php

namespace Database\Seeders;

use App\Models\CommunityGroup;
use App\Models\CommunityGroupMember;
use App\Models\CommunityPost;
use App\Models\Event;
use App\Models\PaymentChannel;
use App\Models\LoyaltyAccount;
use App\Models\LoyaltyTransaction;
use App\Models\Poll;
use App\Models\PollOption;
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

    /** Dự án của cư dân demo (projectIds user #6 = 1,3). */
    private const DEMO_PROJECT_ID = 1;

    /** Căn hộ của cư dân demo #6 (primary). */
    private const DEMO_APARTMENT_ID = 11;

    public function run(): void
    {
        $this->seedVouchers();
        $this->seedLoyalty();
        $this->seedCommunity();
        $this->seedPayments();
        $this->seedPaymentChannels();
    }

    /** Cổng thanh toán demo: bật VietQR cho tenant 1 (toàn bộ dự án). */
    private function seedPaymentChannels(): void
    {
        PaymentChannel::withoutGlobalScopes()->updateOrCreate(
            ['tenant_id' => 1, 'project_id' => null, 'channel' => 'vietqr'],
            [
                'is_enabled' => true,
                'display_name' => 'Chuyển khoản VietQR',
                'sort' => 1,
                'config' => [
                    'bank_bin' => '970436',       // Vietcombank
                    'bank_code' => 'VCB',
                    'account_no' => '1234567890',
                    'account_name' => 'BAN QUAN LY SUNSHINE GARDEN',
                ],
            ]
        );

        // VNPay bật nhưng CHƯA cấu hình credential (ENV) → app hiện not_configured.
        PaymentChannel::withoutGlobalScopes()->updateOrCreate(
            ['tenant_id' => 1, 'project_id' => null, 'channel' => 'vnpay'],
            ['is_enabled' => true, 'display_name' => 'VNPay', 'sort' => 2, 'config' => ['env' => 'sandbox']]
        );

        $this->command?->info('  Payment channels: VietQR (VCB) + VNPay(chờ cấu hình) cho tenant 1.');
    }

    /** Lịch sử thanh toán cho cư dân demo (tab Hoá đơn — CD-PAY-05). */
    private function seedPayments(): void
    {
        $methodId = DB::table('payment_methods')->where('is_active', true)->value('id')
            ?? DB::table('payment_methods')->value('id');

        $apt = DB::table('apartments')->where('id', self::DEMO_APARTMENT_ID)->first();
        if ($apt === null) {
            $this->command?->warn('  Payments: bỏ qua — không thấy apartment #'.self::DEMO_APARTMENT_ID);

            return;
        }

        $statementId = DB::table('statements')
            ->where('apartment_id', self::DEMO_APARTMENT_ID)
            ->where('status', 'paid')
            ->value('id');

        $rows = [
            ['code' => 'PM-2026-06-11', 'amount' => '5000000.00', 'method' => 'Chuyển khoản', 'ref' => 'FT2606110001', 'at' => '2026-06-11'],
            ['code' => 'PM-2026-05-10', 'amount' => '5000000.00', 'method' => 'VietQR', 'ref' => 'FT2605100002', 'at' => '2026-05-10'],
        ];
        foreach ($rows as $r) {
            $paymentId = DB::table('payments')->where('code', $r['code'])->value('id');
            $attrs = [
                'tenant_id' => $apt->tenant_id,
                'building_id' => $apt->building_id,
                'apartment_id' => self::DEMO_APARTMENT_ID,
                'resident_id' => self::DEMO_RESIDENT_ID,
                'payment_method_id' => $methodId,
                'amount' => $r['amount'],
                'paid_at' => Carbon::parse($r['at']),
                'reference_no' => $r['ref'],
                'status' => 'completed',
                'note' => 'Thanh toán phí quản lý',
                'updated_at' => now(),
            ];
            if ($paymentId) {
                DB::table('payments')->where('id', $paymentId)->update($attrs);
            } else {
                $attrs['code'] = $r['code'];
                $attrs['created_at'] = now();
                $paymentId = DB::table('payments')->insertGetId($attrs);
            }

            if ($statementId) {
                DB::table('payment_allocations')->updateOrInsert(
                    ['payment_id' => $paymentId, 'statement_id' => $statementId],
                    ['amount' => $r['amount'], 'updated_at' => now(), 'created_at' => now()]
                );
            }
        }

        $this->command?->info('  Payments: 2 thanh toán (apt '.self::DEMO_APARTMENT_ID.') + allocation vào statement đã trả.');
    }

    /** Tab Cộng đồng: posts + events + polls(+options) + groups (scope project 1). */
    private function seedCommunity(): void
    {
        $p = self::DEMO_PROJECT_ID;

        $posts = [
            ['key' => 'welcome', 'body' => 'Chào mừng cư dân mới của toà nhà! Cùng tham gia nhóm cộng đồng nhé.', 'pinned' => true, 'important' => true, 'likes' => 34, 'comments' => 8],
            ['key' => 'maintenance', 'body' => 'Lịch bảo trì thang máy block A: 8h-11h thứ Bảy tuần này.', 'pinned' => false, 'important' => true, 'likes' => 12, 'comments' => 3],
            ['key' => 'yardsale', 'body' => 'Nhà mình thanh lý bộ sofa còn mới 90%, ai quan tâm inbox nhé!', 'pinned' => false, 'important' => false, 'likes' => 5, 'comments' => 2],
        ];
        foreach ($posts as $i => $po) {
            CommunityPost::withoutGlobalScopes()->updateOrCreate(
                ['project_id' => $p, 'title' => 'SEED-'.$po['key']],
                [
                    'tenant_id' => 1,
                    'author_resident_id' => self::DEMO_RESIDENT_ID,
                    'body' => $po['body'],
                    'like_count' => $po['likes'],
                    'comment_count' => $po['comments'],
                    'is_pinned' => $po['pinned'],
                    'is_important' => $po['important'],
                    'image_paths' => [],
                    'status' => 'published',
                    'created_at' => Carbon::parse('2026-07-'.(10 + $i)),
                ]
            );
        }

        $events = [
            ['title' => 'Đêm nhạc acoustic sân vườn', 'location' => 'Sảnh block B', 'starts' => '2026-08-05 19:00', 'cap' => 120, 'reg' => 45],
            ['title' => 'Lớp yoga buổi sáng', 'location' => 'Khu sinh hoạt tầng 3', 'starts' => '2026-08-10 06:30', 'cap' => 30, 'reg' => 22],
        ];
        foreach ($events as $e) {
            Event::withoutGlobalScopes()->updateOrCreate(
                ['project_id' => $p, 'title' => $e['title']],
                [
                    'tenant_id' => 1,
                    'location' => $e['location'],
                    'description' => 'Sự kiện cộng đồng nội khu.',
                    'starts_at' => Carbon::parse($e['starts']),
                    'ends_at' => Carbon::parse($e['starts'])->addHours(2),
                    'capacity' => $e['cap'],
                    'registered_count' => $e['reg'],
                    'status' => 'published',
                ]
            );
        }

        $poll = Poll::withoutGlobalScopes()->updateOrCreate(
            ['project_id' => $p, 'question' => 'Bạn muốn tiện ích nào được nâng cấp trước?'],
            ['tenant_id' => 1, 'type' => 'single', 'status' => 'open', 'closes_at' => Carbon::parse('2026-08-31'), 'vote_count' => 0]
        );
        $options = ['Hồ bơi', 'Phòng gym', 'Sân chơi trẻ em', 'BBQ sân thượng'];
        $votes = [40, 30, 20, 10];
        $total = 0;
        foreach ($options as $idx => $label) {
            PollOption::updateOrCreate(
                ['poll_id' => $poll->id, 'label' => $label],
                ['vote_count' => $votes[$idx], 'sort' => $idx]
            );
            $total += $votes[$idx];
        }
        $poll->update(['vote_count' => $total]);

        $groups = [
            ['name' => 'Hội cư dân block A', 'desc' => 'Trao đổi thông tin block A', 'members' => 320],
            ['name' => 'Cha mẹ & con nhỏ', 'desc' => 'Kết nối các gia đình có trẻ nhỏ', 'members' => 145],
            ['name' => 'Thể thao & sức khoẻ', 'desc' => 'Chạy bộ, gym, yoga nội khu', 'members' => 210],
        ];
        foreach ($groups as $g) {
            CommunityGroup::withoutGlobalScopes()->updateOrCreate(
                ['project_id' => $p, 'name' => $g['name']],
                ['tenant_id' => 1, 'description' => $g['desc'], 'member_count' => $g['members'], 'status' => 'active']
            );
        }

        // Cư dân demo tham gia sẵn 1 nhóm (để `joined=true` demo).
        $firstGroup = CommunityGroup::withoutGlobalScopes()->where('project_id', $p)->orderBy('id')->first();
        if ($firstGroup) {
            CommunityGroupMember::firstOrCreate(
                ['community_group_id' => $firstGroup->id, 'resident_id' => self::DEMO_RESIDENT_ID],
                ['role' => 'member', 'joined_at' => Carbon::parse('2026-07-01')],
            );
        }

        $this->command?->info('  Community: 3 posts + 2 events + 1 poll(4 options) + 3 groups + 1 membership (project '.$p.').');
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
