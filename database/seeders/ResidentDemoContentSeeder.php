<?php

namespace Database\Seeders;

use App\Models\AmenityBooking;
use App\Models\CommunityGroup;
use App\Models\CommunityGroupMember;
use App\Models\CommunityPost;
use App\Models\Event;
use App\Models\FeedbackCategory;
use App\Models\FeedbackRequest;
use App\Models\PaymentChannel;
use App\Models\LoyaltyAccount;
use App\Models\LoyaltyTransaction;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\Receipt;
use App\Models\VisitorRegistration;
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
        $this->seedMarketplace();
        $this->seedServices();
        $this->seedRealEstate();
        $this->seedNotifications();
        $this->seedPayments();
        $this->seedPaymentChannels();
        $this->seedVisitors();
        $this->seedAmenityBookings();
        $this->seedFeedback();
        $this->seedReceipts();
    }

    /** Đăng ký khách demo cho căn cư dân (C12 — visitor_registrations). */
    private function seedVisitors(): void
    {
        $apt = DB::table('apartments')->where('id', self::DEMO_APARTMENT_ID)->first();
        if ($apt === null) {
            $this->command?->warn('  Visitors: bỏ qua — không thấy apartment #'.self::DEMO_APARTMENT_ID);

            return;
        }
        $projectId = DB::table('buildings')->where('id', $apt->building_id)->value('project_id');

        $rows = [
            ['code' => 'KH-SEED-001', 'name' => 'Trần Thị Khách', 'phone' => '0912345678', 'purpose' => 'Thăm gia đình', 'plate' => '51H-123.45', 'guests' => 2, 'at' => '2026-07-26 18:00', 'leave' => '2026-07-26 21:00', 'status' => 'pending'],
            ['code' => 'KH-SEED-002', 'name' => 'Giao hàng Shopee', 'phone' => '0987654321', 'purpose' => 'Giao hàng', 'plate' => null, 'guests' => 1, 'at' => '2026-07-25 09:30', 'leave' => null, 'status' => 'approved'],
            ['code' => 'KH-SEED-003', 'name' => 'Nguyễn Văn Bạn', 'phone' => '0905112233', 'purpose' => 'Họp mặt cuối tuần', 'plate' => '30F-678.90', 'guests' => 4, 'at' => '2026-07-24 19:00', 'leave' => '2026-07-24 22:30', 'status' => 'checked_in'],
            ['code' => 'KH-SEED-004', 'name' => 'Kỹ thuật viên Internet', 'phone' => '0933445566', 'purpose' => 'Lắp đặt mạng', 'plate' => null, 'guests' => 1, 'at' => '2026-07-23 14:00', 'leave' => '2026-07-23 15:30', 'status' => 'checked_out'],
            ['code' => 'KH-SEED-005', 'name' => 'Lê Thị Người thân', 'phone' => '0977889900', 'purpose' => 'Chăm em bé', 'plate' => null, 'guests' => 1, 'at' => '2026-07-30 08:00', 'leave' => null, 'status' => 'pending'],
        ];
        foreach ($rows as $r) {
            VisitorRegistration::withoutGlobalScopes()->updateOrCreate(
                ['code' => $r['code']],
                [
                    'tenant_id' => $apt->tenant_id,
                    'project_id' => $projectId,
                    'building_id' => $apt->building_id,
                    'apartment_id' => self::DEMO_APARTMENT_ID,
                    'resident_id' => self::DEMO_RESIDENT_ID,
                    'host_user_id' => 6,
                    'visitor_name' => $r['name'],
                    'visitor_phone' => $r['phone'],
                    'purpose' => $r['purpose'],
                    'vehicle_plate' => $r['plate'],
                    'num_guests' => $r['guests'],
                    'expected_at' => Carbon::parse($r['at']),
                    'expected_leave_at' => $r['leave'] ? Carbon::parse($r['leave']) : null,
                    'status' => $r['status'],
                ]
            );
        }

        $this->command?->info('  Visitors: 5 đăng ký khách (apt '.self::DEMO_APARTMENT_ID.').');
    }

    /** Lượt đặt tiện ích demo cho cư dân (amenity_bookings). */
    private function seedAmenityBookings(): void
    {
        $apt = DB::table('apartments')->where('id', self::DEMO_APARTMENT_ID)->first();
        $amenity = DB::table('amenities')->where('project_id', self::DEMO_PROJECT_ID)->orderBy('id')->first();
        if ($apt === null || $amenity === null) {
            $this->command?->warn('  Amenity bookings: bỏ qua — thiếu apartment/amenity.');

            return;
        }

        $rows = [
            ['code' => 'BK-SEED-001', 'date' => '2026-07-28', 'start' => '06:00', 'end' => '08:00', 'party' => 2, 'status' => 'confirmed', 'note' => 'Tập gym buổi sáng'],
            ['code' => 'BK-SEED-002', 'date' => '2026-08-02', 'start' => '18:00', 'end' => '20:00', 'party' => 4, 'status' => 'pending', 'note' => 'Tiệc BBQ cuối tuần'],
            ['code' => 'BK-SEED-003', 'date' => '2026-07-20', 'start' => '16:00', 'end' => '17:00', 'party' => 2, 'status' => 'completed', 'note' => 'Bơi chiều'],
            ['code' => 'BK-SEED-004', 'date' => '2026-08-05', 'start' => '09:00', 'end' => '10:00', 'party' => 1, 'status' => 'cancelled', 'note' => 'Đặt nhầm giờ, đã huỷ'],
        ];
        foreach ($rows as $r) {
            AmenityBooking::withoutGlobalScopes()->updateOrCreate(
                ['code' => $r['code']],
                [
                    'tenant_id' => $apt->tenant_id,
                    'building_id' => $apt->building_id,
                    'amenity_id' => $amenity->id,
                    'apartment_id' => self::DEMO_APARTMENT_ID,
                    'resident_id' => self::DEMO_RESIDENT_ID,
                    'user_id' => 6,
                    'booking_date' => Carbon::parse($r['date']),
                    'start_time' => $r['start'],
                    'end_time' => $r['end'],
                    'party_size' => $r['party'],
                    'price' => $amenity->price ?? 0,
                    'note' => $r['note'],
                    'status' => $r['status'],
                ]
            );
        }

        $this->command?->info('  Amenity bookings: 4 lượt đặt (resident '.self::DEMO_RESIDENT_ID.').');
    }

    /** Phản ánh demo cho cư dân (feedback_requests). */
    private function seedFeedback(): void
    {
        $apt = DB::table('apartments')->where('id', self::DEMO_APARTMENT_ID)->first();
        if ($apt === null) {
            $this->command?->warn('  Feedback: bỏ qua — không thấy apartment #'.self::DEMO_APARTMENT_ID);

            return;
        }
        $projectId = DB::table('buildings')->where('id', $apt->building_id)->value('project_id');
        $catId = FeedbackCategory::withoutGlobalScopes()->where('tenant_id', $apt->tenant_id)->orderBy('id')->value('id');

        $rows = [
            ['code' => 'PA-SEED-001', 'cat' => $catId, 'title' => 'Đèn hành lang tầng 8 bị hỏng', 'desc' => 'Đèn hành lang trước căn hộ không sáng từ tối qua.', 'priority' => 'normal', 'status' => 'in_progress'],
            ['code' => 'PA-SEED-002', 'cat' => $catId, 'title' => 'Đề nghị tăng tần suất dọn rác', 'desc' => 'Khu vực để rác tầng 8 cần được dọn thường xuyên hơn.', 'priority' => 'low', 'status' => 'new'],
            ['code' => 'PA-SEED-003', 'cat' => $catId, 'title' => 'Rò rỉ nước tầng hầm B1', 'desc' => 'Có vũng nước lớn gần chỗ để xe, trơn trượt nguy hiểm.', 'priority' => 'high', 'status' => 'resolved'],
            ['code' => 'PA-SEED-004', 'cat' => $catId, 'title' => 'Tiếng ồn thi công ngoài giờ', 'desc' => 'Căn hộ tầng trên thi công sau 22h gây ồn.', 'priority' => 'urgent', 'status' => 'in_progress'],
        ];
        foreach ($rows as $r) {
            FeedbackRequest::withoutGlobalScopes()->updateOrCreate(
                ['code' => $r['code']],
                [
                    'tenant_id' => $apt->tenant_id,
                    'building_id' => $apt->building_id,
                    'project_id' => $projectId,
                    'apartment_id' => self::DEMO_APARTMENT_ID,
                    'resident_id' => self::DEMO_RESIDENT_ID,
                    'user_id' => 6,
                    'feedback_category_id' => $r['cat'],
                    'title' => $r['title'],
                    'description' => $r['desc'],
                    'priority' => $r['priority'],
                    'channel' => 'app',
                    'status' => $r['status'],
                ]
            );
        }

        $this->command?->info('  Feedback: 4 phản ánh (apt '.self::DEMO_APARTMENT_ID.').');
    }

    /** Biên lai demo cho 1 thanh toán của cư dân (receipts). */
    private function seedReceipts(): void
    {
        $payment = DB::table('payments')->where('apartment_id', self::DEMO_APARTMENT_ID)->orderByDesc('id')->first();
        if ($payment === null) {
            $this->command?->warn('  Receipts: bỏ qua — không thấy payment cho apt #'.self::DEMO_APARTMENT_ID);

            return;
        }

        Receipt::withoutGlobalScopes()->updateOrCreate(
            ['payment_id' => $payment->id],
            [
                'tenant_id' => $payment->tenant_id,
                'code' => 'BL-'.str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT),
                'amount' => $payment->amount,
                'issued_at' => Carbon::parse($payment->paid_at ?? now()),
                'issued_by_id' => null,
            ]
        );

        $this->command?->info('  Receipts: 1 biên lai cho payment #'.$payment->id.'.');
    }

    /**
     * Cổng thanh toán demo — mô hình MỖI DỰ ÁN 1 TÀI KHOẢN (owner chốt 2026-07-24).
     * VietQR gắn theo `project_id` cụ thể; tài khoản thật do owner nhập qua admin Filament
     * (fila/payment-channels). Số tài khoản dưới đây là DEMO — thay bằng TK thật khi golive.
     */
    private function seedPaymentChannels(): void
    {
        // Dọn bản ghi VietQR toàn-tenant cũ (nếu có) để về đúng mô hình per-project.
        PaymentChannel::withoutGlobalScopes()
            ->where('tenant_id', 1)->whereNull('project_id')->where('channel', 'vietqr')
            ->forceDelete();

        PaymentChannel::withoutGlobalScopes()->updateOrCreate(
            ['tenant_id' => 1, 'project_id' => self::DEMO_PROJECT_ID, 'channel' => 'vietqr'],
            [
                'is_enabled' => true,
                'display_name' => 'Chuyển khoản VietQR',
                'sort' => 1,
                'config' => [
                    'bank_bin' => '970436',       // Vietcombank (DEMO)
                    'bank_code' => 'VCB',
                    'account_no' => '1234567890', // DEMO — thay bằng TK thật của dự án
                    'account_name' => 'BAN QUAN LY SUNSHINE GARDEN',
                ],
            ]
        );

        // VNPay bật nhưng CHƯA cấu hình credential (ENV) → app hiện not_configured.
        PaymentChannel::withoutGlobalScopes()->updateOrCreate(
            ['tenant_id' => 1, 'project_id' => self::DEMO_PROJECT_ID, 'channel' => 'vnpay'],
            ['is_enabled' => true, 'display_name' => 'VNPay', 'sort' => 2, 'config' => ['env' => 'sandbox']]
        );

        $this->command?->info('  Payment channels: VietQR (VCB, DEMO) + VNPay(chờ cấu hình) cho DỰ ÁN '.self::DEMO_PROJECT_ID.'.');
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
            ['key' => 'lostcat', 'body' => 'Nhà em lạc mất bé mèo tam thể ở khu vực block B, ai thấy báo giúp em với ạ!', 'pinned' => false, 'important' => false, 'likes' => 27, 'comments' => 15],
            ['key' => 'parking', 'body' => 'Nhắc nhẹ: bà con đỗ xe đúng vạch tầng hầm để lối đi được thông thoáng nhé.', 'pinned' => true, 'important' => false, 'likes' => 41, 'comments' => 6],
            ['key' => 'foodshare', 'body' => 'Cuối tuần này mình tổ chức góc chia sẻ đồ ăn nhà làm ở sảnh, mời cả nhà tham gia!', 'pinned' => false, 'important' => false, 'likes' => 63, 'comments' => 21],
            ['key' => 'security', 'body' => 'Cảm ơn đội bảo vệ đã hỗ trợ tìm lại ví bị rơi tối qua. Cư dân mình văn minh quá!', 'pinned' => false, 'important' => false, 'likes' => 88, 'comments' => 12],
            ['key' => 'gardening', 'body' => 'Nhóm làm vườn sân thượng đang tuyển thêm thành viên, ai thích trồng cây thì join nha.', 'pinned' => false, 'important' => false, 'likes' => 19, 'comments' => 4],
            ['key' => 'water', 'body' => 'Thông báo: tạm ngưng cấp nước block A từ 13h-15h thứ Năm để súc rửa bể chứa.', 'pinned' => false, 'important' => true, 'likes' => 9, 'comments' => 7],
            ['key' => 'thanks', 'body' => 'Cảm ơn BQL đã trang trí sảnh dịp lễ rất đẹp, các bé nhà mình thích lắm!', 'pinned' => false, 'important' => false, 'likes' => 52, 'comments' => 9],
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
            ['title' => 'Hội chợ cuối tuần cư dân', 'location' => 'Quảng trường trung tâm', 'starts' => '2026-08-16 08:00', 'cap' => 500, 'reg' => 312],
            ['title' => 'Giải chạy bộ nội khu 5K', 'location' => 'Đường nội bộ vòng quanh dự án', 'starts' => '2026-08-24 05:30', 'cap' => 200, 'reg' => 156],
            ['title' => 'Trung thu cho bé', 'location' => 'Sân chơi trẻ em', 'starts' => '2026-09-06 18:00', 'cap' => 300, 'reg' => 210],
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

        $polls = [
            [
                'question' => 'Bạn muốn tiện ích nào được nâng cấp trước?',
                'closes' => '2026-08-31',
                'options' => ['Hồ bơi' => 40, 'Phòng gym' => 30, 'Sân chơi trẻ em' => 20, 'BBQ sân thượng' => 10],
            ],
            [
                'question' => 'Khung giờ nào phù hợp để tổ chức họp cư dân?',
                'closes' => '2026-09-15',
                'options' => ['Sáng thứ Bảy' => 55, 'Chiều Chủ nhật' => 78, 'Tối thứ Sáu' => 33],
            ],
            [
                'question' => 'Bạn ưu tiên loại cây xanh nào cho sân vườn mới?',
                'closes' => '2026-09-30',
                'options' => ['Cây bóng mát' => 62, 'Vườn hoa' => 44, 'Vườn rau cộng đồng' => 51, 'Cây cảnh bonsai' => 18],
            ],
        ];
        foreach ($polls as $pd) {
            $poll = Poll::withoutGlobalScopes()->updateOrCreate(
                ['project_id' => $p, 'question' => $pd['question']],
                ['tenant_id' => 1, 'type' => 'single', 'status' => 'open', 'closes_at' => Carbon::parse($pd['closes']), 'vote_count' => 0]
            );
            $total = 0;
            $idx = 0;
            foreach ($pd['options'] as $label => $count) {
                PollOption::updateOrCreate(
                    ['poll_id' => $poll->id, 'label' => $label],
                    ['vote_count' => $count, 'sort' => $idx]
                );
                $total += $count;
                $idx++;
            }
            $poll->update(['vote_count' => $total]);
        }

        $groups = [
            ['name' => 'Hội cư dân block A', 'desc' => 'Trao đổi thông tin block A', 'members' => 320],
            ['name' => 'Cha mẹ & con nhỏ', 'desc' => 'Kết nối các gia đình có trẻ nhỏ', 'members' => 145],
            ['name' => 'Thể thao & sức khoẻ', 'desc' => 'Chạy bộ, gym, yoga nội khu', 'members' => 210],
            ['name' => 'Chợ nội khu & thanh lý', 'desc' => 'Mua bán, trao đổi đồ dùng giữa cư dân', 'members' => 480],
            ['name' => 'Yêu bếp & ẩm thực', 'desc' => 'Chia sẻ công thức nấu ăn, đặt món nhà làm', 'members' => 265],
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

        $this->command?->info('  Community: 10 posts + 5 events + 3 polls + 5 groups + 1 membership (project '.$p.').');
    }

    /** Tài khoản điểm + hoạt động gần đây cho cư dân demo (tab Ưu đãi — CD-LY-01). */
    private function seedLoyalty(): void
    {
        $account = LoyaltyAccount::withoutGlobalScopes()->updateOrCreate(
            ['resident_id' => self::DEMO_RESIDENT_ID],
            ['tenant_id' => 1, 'points_balance' => 4200, 'tier' => 'gold', 'status' => 'active']
        );

        $activities = [
            ['ref' => 'LY-2026-07-20', 'type' => 'earn', 'points' => 250, 'description' => 'Đánh giá dịch vụ tiện ích', 'at' => '2026-07-20'],
            ['ref' => 'LY-2026-07-12', 'type' => 'redeem', 'points' => -800, 'description' => 'Đổi bình giữ nhiệt cao cấp', 'at' => '2026-07-12'],
            ['ref' => 'LY-2026-07-05', 'type' => 'earn', 'points' => 500, 'description' => 'Thanh toán phí quản lý T7', 'at' => '2026-07-05'],
            ['ref' => 'LY-2026-06-28', 'type' => 'earn', 'points' => 150, 'description' => 'Giới thiệu cư dân mới', 'at' => '2026-06-28'],
            ['ref' => 'LY-2026-06-15', 'type' => 'redeem', 'points' => -200, 'description' => 'Đổi voucher giảm 10% dịch vụ', 'at' => '2026-06-15'],
            ['ref' => 'LY-2026-06-05', 'type' => 'earn', 'points' => 500, 'description' => 'Thanh toán phí quản lý T6', 'at' => '2026-06-05'],
            ['ref' => 'LY-2026-05-25', 'type' => 'redeem', 'points' => -500, 'description' => 'Đổi vé xem phim CGV', 'at' => '2026-05-25'],
            ['ref' => 'LY-2026-05-20', 'type' => 'earn', 'points' => 300, 'description' => 'Tham gia sự kiện cộng đồng', 'at' => '2026-05-20'],
            ['ref' => 'LY-2026-05-10', 'type' => 'earn', 'points' => 500, 'description' => 'Thanh toán phí quản lý T5', 'at' => '2026-05-10'],
            ['ref' => 'LY-2026-05-01', 'type' => 'earn', 'points' => 100, 'description' => 'Hoàn thành khảo sát cộng đồng', 'at' => '2026-05-01'],
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

        $this->command?->info('  Loyalty: account 4200đ (gold) + 10 hoạt động cho resident #'.self::DEMO_RESIDENT_ID.'.');
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
            ['code' => 'OF-SPA20', 'name' => 'Giảm 20% dịch vụ spa & massage', 'type' => 'discount', 'value' => '20.00'],
            ['code' => 'OF-COFFEE1', 'name' => 'Tặng 1 ly cà phê tại café tầng trệt', 'type' => 'gift', 'value' => '0.00'],
            ['code' => 'OF-PARKING2H', 'name' => 'Miễn phí 2 giờ gửi xe cho khách', 'type' => 'gift', 'value' => '0.00'],
            ['code' => 'OF-CLEAN15', 'name' => 'Giảm 15% dịch vụ dọn vệ sinh nhà', 'type' => 'discount', 'value' => '15.00'],
            ['code' => 'OF-MART10', 'name' => 'Giảm 10% siêu thị mini nội khu', 'type' => 'discount', 'value' => '10.00'],
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

        // Quà đổi điểm của tenant 1 (points_cost > 0) — tab "Đổi quà".
        $gifts = [
            ['code' => 'GF-UMBRELLA', 'name' => 'Ô dù cầm tay in logo X2', 'points' => 300, 'value' => '150000.00'],
            ['code' => 'GF-TUMBLER', 'name' => 'Bình giữ nhiệt cao cấp', 'points' => 800, 'value' => '350000.00'],
            ['code' => 'GF-SHOP50', 'name' => 'Phiếu mua hàng siêu thị 50.000đ', 'points' => 500, 'value' => '50000.00'],
            ['code' => 'GF-MOVIE', 'name' => 'Vé xem phim CGV 2D', 'points' => 1000, 'value' => '120000.00'],
            ['code' => 'GF-DINNER2', 'name' => 'Voucher ăn tối cho 2 người', 'points' => 2000, 'value' => '800000.00'],
            ['code' => 'GF-CLEANKIT', 'name' => 'Bộ dụng cụ vệ sinh nhà cửa', 'points' => 600, 'value' => '250000.00'],
        ];
        foreach ($gifts as $g) {
            Voucher::withoutGlobalScopes()->updateOrCreate(
                ['code' => $g['code']],
                [
                    'tenant_id' => 1,
                    'owner_level' => 'tenant',
                    'name' => $g['name'],
                    'type' => 'gift',
                    'value' => $g['value'],
                    'points_cost' => $g['points'],
                    'quantity' => 50,
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

        $this->command?->info('  Vouchers: 8 offers + 6 gifts + 1 platform rollout (tenant 1).');
    }

    /** Chợ nội khu: sản phẩm rao bán (marketplace_products) — đa danh mục cho dự án demo. */
    private function seedMarketplace(): void
    {
        $sellerIds = DB::table('residents')->where('tenant_id', 1)->orderBy('id')->limit(6)->pluck('id')->all();
        if (empty($sellerIds)) {
            $sellerIds = [self::DEMO_RESIDENT_ID];
        }

        $products = [
            ['name' => 'Tủ lạnh Samsung Inverter 360L', 'price' => '6500000.00', 'category' => 'household', 'condition' => 'used', 'desc' => 'Tủ lạnh 2 cửa còn bảo hành 6 tháng, chạy êm.'],
            ['name' => 'Máy giặt LG cửa ngang 9kg', 'price' => '4200000.00', 'category' => 'household', 'condition' => 'used', 'desc' => 'Máy giặt inverter tiết kiệm điện, mới 85%.'],
            ['name' => 'Bộ bàn ăn gỗ sồi 6 ghế', 'price' => '3800000.00', 'category' => 'household', 'condition' => 'used', 'desc' => 'Bàn ăn gỗ tự nhiên, chắc chắn, đẹp.'],
            ['name' => 'iPhone 14 Pro 128GB', 'price' => '15500000.00', 'category' => 'electronics', 'condition' => 'used', 'desc' => 'Máy đẹp 99%, pin 92%, full phụ kiện.'],
            ['name' => 'MacBook Air M2 2023', 'price' => '19800000.00', 'category' => 'electronics', 'condition' => 'used', 'desc' => 'Laptop mỏng nhẹ, ít dùng, còn bảo hành.'],
            ['name' => 'Tai nghe Sony WH-1000XM5', 'price' => '5200000.00', 'category' => 'electronics', 'condition' => 'new', 'desc' => 'Tai nghe chống ồn, nguyên seal chưa khui.'],
            ['name' => 'Áo khoác nữ dạ tweed', 'price' => '450000.00', 'category' => 'fashion', 'condition' => 'new', 'desc' => 'Áo khoác thời trang size M, còn tag.'],
            ['name' => 'Giày sneaker nam size 42', 'price' => '780000.00', 'category' => 'fashion', 'condition' => 'used', 'desc' => 'Giày thể thao mang 2 lần, còn mới.'],
            ['name' => 'Xe đạp trẻ em 4-8 tuổi', 'price' => '900000.00', 'category' => 'kids', 'condition' => 'used', 'desc' => 'Xe đạp có bánh phụ, màu xanh, bé lớn nên bán.'],
            ['name' => 'Bộ tạ tay tập gym tại nhà', 'price' => '650000.00', 'category' => 'sports', 'condition' => 'used', 'desc' => 'Bộ tạ 20kg điều chỉnh được, ít dùng.'],
        ];
        foreach ($products as $i => $pr) {
            DB::table('marketplace_products')->updateOrInsert(
                ['project_id' => self::DEMO_PROJECT_ID, 'name' => $pr['name']],
                [
                    'tenant_id' => 1,
                    'seller_resident_id' => $sellerIds[$i % count($sellerIds)],
                    'description' => $pr['desc'],
                    'price' => $pr['price'],
                    'category' => $pr['category'],
                    'condition' => $pr['condition'],
                    'status' => 'active',
                    'updated_at' => now(),
                    'created_at' => Carbon::parse('2026-07-'.str_pad((string) (5 + $i), 2, '0', STR_PAD_LEFT)),
                ]
            );
        }

        $this->command?->info('  Marketplace: 10 sản phẩm (household/electronics/fashion/kids/sports) — project '.self::DEMO_PROJECT_ID.'.');
    }

    /** Chợ nội khu: nhà cung cấp dịch vụ (service_providers — scope tenant). */
    private function seedServices(): void
    {
        $services = [
            ['name' => 'Giặt là 5 sao', 'category' => 'laundry', 'phone' => '0900111222', 'rating' => '4.7'],
            ['name' => 'Bếp nhà An', 'category' => 'food', 'phone' => '0900222333', 'rating' => '4.5'],
            ['name' => 'Sửa điện nước 24h', 'category' => 'repair', 'phone' => '0900333444', 'rating' => '4.3'],
            ['name' => 'Spa & Nail Home', 'category' => 'beauty', 'phone' => '0900444555', 'rating' => '4.8'],
            ['name' => 'Dọn nhà theo giờ Sạch Xanh', 'category' => 'cleaning', 'phone' => '0900555666', 'rating' => '4.6'],
            ['name' => 'Chăm sóc thú cưng PetCare', 'category' => 'pet', 'phone' => '0900666777', 'rating' => '4.4'],
        ];
        foreach ($services as $s) {
            DB::table('service_providers')->updateOrInsert(
                ['tenant_id' => 1, 'name' => $s['name']],
                [
                    'category' => $s['category'],
                    'phone' => $s['phone'],
                    'rating' => $s['rating'],
                    'status' => 'active',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $this->command?->info('  Services: 6 nhà cung cấp (laundry/food/repair/beauty/cleaning/pet) — tenant 1.');
    }

    /** Tin BĐS nội khu (real_estate_listings) — sale + rent cho dự án demo. */
    private function seedRealEstate(): void
    {
        $ownerId = DB::table('residents')->where('tenant_id', 1)->orderBy('id')->value('id') ?? self::DEMO_RESIDENT_ID;

        $listings = [
            ['code' => 'RE-SEED-001', 'type' => 'sale', 'title' => 'Bán căn 2PN view sông, nội thất cơ bản', 'price' => '3850000000.00', 'area' => '68.00', 'bed' => 2],
            ['code' => 'RE-SEED-002', 'type' => 'rent', 'title' => 'Cho thuê 1PN full nội thất, vào ở ngay', 'price' => '12000000.00', 'area' => '45.00', 'bed' => 1],
            ['code' => 'RE-SEED-003', 'type' => 'sale', 'title' => 'Bán căn 3PN góc, view nội khu thoáng', 'price' => '5200000000.00', 'area' => '92.00', 'bed' => 3],
            ['code' => 'RE-SEED-004', 'type' => 'rent', 'title' => 'Cho thuê studio giá tốt cho người độc thân', 'price' => '8500000.00', 'area' => '32.00', 'bed' => 1],
            ['code' => 'RE-SEED-005', 'type' => 'sale', 'title' => 'Bán duplex 4PN cao cấp, sổ hồng riêng', 'price' => '8900000000.00', 'area' => '145.00', 'bed' => 4],
        ];
        foreach ($listings as $i => $l) {
            DB::table('real_estate_listings')->updateOrInsert(
                ['code' => $l['code']],
                [
                    'tenant_id' => 1,
                    'project_id' => self::DEMO_PROJECT_ID,
                    'apartment_id' => $i === 0 ? self::DEMO_APARTMENT_ID : null,
                    'owner_resident_id' => $ownerId,
                    'type' => $l['type'],
                    'title' => $l['title'],
                    'price' => $l['price'],
                    'area' => $l['area'],
                    'bedrooms' => $l['bed'],
                    'status' => 'active',
                    'published_at' => Carbon::parse('2026-07-'.str_pad((string) (10 + $i), 2, '0', STR_PAD_LEFT)),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $this->command?->info('  Real estate: 5 tin (3 bán + 2 thuê) — project '.self::DEMO_PROJECT_ID.'.');
    }

    /**
     * Thông báo demo hiển thị cho cư dân — published, audience `all` (mọi cư dân thấy),
     * có `body` chi tiết. `cover_path` để trống → Resource trả ảnh demo (DemoImage).
     */
    private function seedNotifications(): void
    {
        $rows = [
            ['code' => 'NTF-SEED-001', 'type' => 'community', 'title' => 'Hội chợ cuối tuần cư dân — 16/08', 'priority' => 'normal', 'pinned' => true,
                'summary' => 'Mời cả nhà tham gia hội chợ cuối tuần tại quảng trường trung tâm.',
                'body' => '<p>Ban Quản lý tổ chức <b>Hội chợ cuối tuần cư dân</b> vào 8h00 ngày 16/08 tại quảng trường trung tâm. Nhiều gian hàng ẩm thực, đồ handmade và khu vui chơi cho các bé. Rất mong cư dân tham gia đông đủ!</p>', 'at' => '2026-07-22 08:00'],
            ['code' => 'NTF-SEED-002', 'type' => 'maintenance', 'title' => 'Bảo trì hệ thống PCCC toàn dự án', 'priority' => 'high', 'pinned' => false,
                'summary' => 'Kiểm tra định kỳ hệ thống báo cháy, có thể có tiếng chuông thử.',
                'body' => '<p>Từ 9h00–11h00 ngày 25/07, kỹ thuật sẽ <b>kiểm tra định kỳ hệ thống PCCC</b>. Trong thời gian này chuông báo cháy có thể kêu thử, cư dân vui lòng không hoảng loạn. Xin cảm ơn sự hợp tác của quý cư dân.</p>', 'at' => '2026-07-20 09:00'],
            ['code' => 'NTF-SEED-003', 'type' => 'billing', 'title' => 'Thông báo kỳ thu phí quản lý tháng 8', 'priority' => 'normal', 'pinned' => false,
                'summary' => 'Hạn thanh toán phí quản lý tháng 8 là ngày 10/08.',
                'body' => '<p>Kỳ thu phí quản lý <b>tháng 8/2026</b> đã phát hành. Quý cư dân vui lòng thanh toán trước ngày 10/08 qua ứng dụng (VietQR) hoặc tại quầy BQL. Chi tiết hoá đơn xem tại mục Hoá đơn của ứng dụng.</p>', 'at' => '2026-07-18 08:00'],
            ['code' => 'NTF-SEED-004', 'type' => 'announcement', 'title' => 'Ra mắt ứng dụng cư dân X2 phiên bản mới', 'priority' => 'low', 'pinned' => false,
                'summary' => 'App cư dân bổ sung nhiều tính năng: cộng đồng, ưu đãi, chợ nội khu.',
                'body' => '<p>Ứng dụng cư dân X2 vừa cập nhật với giao diện mới và nhiều tính năng: <b>bảng tin cộng đồng, ưu đãi & tích điểm, chợ nội khu, đặt tiện ích</b>. Mời quý cư dân trải nghiệm ngay hôm nay!</p>', 'at' => '2026-07-15 10:00'],
        ];

        foreach ($rows as $r) {
            $id = DB::table('notifications')->where('code', $r['code'])->value('id');
            $attrs = [
                'tenant_id' => 1,
                'owner_level' => 'tenant',
                'project_id' => self::DEMO_PROJECT_ID,
                'type' => $r['type'],
                'title' => $r['title'],
                'summary' => $r['summary'],
                'body' => $r['body'],
                'priority' => $r['priority'],
                'status' => 'published',
                'is_pinned' => $r['pinned'],
                'published_at' => Carbon::parse($r['at']),
                'updated_at' => now(),
            ];
            if ($id) {
                DB::table('notifications')->where('id', $id)->update($attrs);
            } else {
                $attrs['code'] = $r['code'];
                $attrs['created_at'] = now();
                $id = DB::table('notifications')->insertGetId($attrs);
            }

            // Audience `all` → mọi cư dân đều thấy (ResidentNotificationService).
            DB::table('notification_audiences')->updateOrInsert(
                ['notification_id' => $id, 'scope_type' => 'all', 'scope_id' => null],
                ['updated_at' => now(), 'created_at' => now()]
            );
        }

        $this->command?->info('  Notifications: 4 thông báo published (audience all, có body) cho cư dân.');
    }
}
