<?php

namespace Database\Seeders;

use App\Models\Amenity;
use App\Models\CommunityGroup;
use App\Models\CommunityPost;
use App\Models\Event;
use App\Models\Voucher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Dữ liệu demo cho **DỰ ÁN THỨ HAI** của cư dân demo — Đại Phúc Riverside.
 *
 * Vì sao cần: tài khoản demo #6 có hai căn ở hai dự án khác nhau
 * (Sunshine Garden #1 và Đại Phúc Riverside #3), nhưng chỉ dự án 1 có dữ liệu.
 * Đổi căn hộ sang dự án 3 thì mọi tab hiện rỗng — không phân biệt được
 * "scope chạy đúng" với "app hỏng". Seeder này làm đối chứng: nội dung dự án 2
 * **khác hẳn** dự án 1 để nhìn phát biết ngay ngữ cảnh đã đổi.
 *
 * Chạy: `php artisan db:seed --class=SecondProjectDemoSeeder`
 * Idempotent — updateOrCreate theo khoá tự nhiên, chạy lại an toàn.
 *
 * ⚠️ Dự án 3 thuộc **tenant 2** (khác tenant 1 của dự án 1). Voucher scope theo
 * TENANT chứ không theo project, nên ưu đãi ở đây phải tạo dưới tenant 2 —
 * tạo nhầm tenant 1 thì cư dân ở dự án 3 không thấy gì.
 */
class SecondProjectDemoSeeder extends Seeder
{
    private const TENANT_ID = 2;
    private const PROJECT_ID = 3;
    private const BUILDING_ID = 6;

    /** Quan hệ căn hộ #1306 của user #6 → resident #1306. */
    private const RESIDENT_ID = 1306;

    public function run(): void
    {
        $this->seedCommunity();
        $this->seedAmenities();
        $this->seedOffers();
    }

    /** Bảng tin + sự kiện + nhóm — giọng điệu khác hẳn dự án 1 (ven sông). */
    private function seedCommunity(): void
    {
        $posts = [
            ['key' => 'dp-river', 'body' => 'Sáng nay sương trên sông đẹp quá cả nhà ơi, ai dậy sớm ra bờ kè ngắm với mình!', 'pinned' => true, 'important' => false, 'likes' => 74, 'comments' => 18],
            ['key' => 'dp-kayak', 'body' => 'CLB chèo kayak bến số 2 mở lớp cho người mới, sáng CN hàng tuần. Đăng ký ở lễ tân toà A nhé.', 'pinned' => false, 'important' => false, 'likes' => 46, 'comments' => 11],
            ['key' => 'dp-market', 'body' => 'Phiên chợ quê cuối tháng ở quảng trường ven sông — rau nhà trồng, cá sông tươi.', 'pinned' => true, 'important' => false, 'likes' => 91, 'comments' => 24],
            ['key' => 'dp-dredge', 'body' => 'Thông báo: nạo vét kênh nhánh từ 6h-10h thứ Ba, có thể hơi ồn ở toà A. Mong cả nhà thông cảm.', 'pinned' => false, 'important' => true, 'likes' => 8, 'comments' => 5],
            ['key' => 'dp-bbq', 'body' => 'Khu BBQ bờ sông vừa lắp thêm 4 bếp mới, đặt chỗ qua app được rồi nha.', 'pinned' => false, 'important' => false, 'likes' => 57, 'comments' => 13],
            ['key' => 'dp-shuttle', 'body' => 'Xe buýt nội khu đổi giờ chạy từ 1/8: chuyến đầu 5h45, chuyến cuối 22h30.', 'pinned' => false, 'important' => true, 'likes' => 22, 'comments' => 9],
            ['key' => 'dp-fishing', 'body' => 'Nhắc nhẹ: câu cá chỉ ở bến số 3, không câu ở khu bơi để đảm bảo an toàn cho các bé.', 'pinned' => false, 'important' => false, 'likes' => 33, 'comments' => 7],
        ];

        foreach ($posts as $i => $po) {
            CommunityPost::withoutGlobalScopes()->updateOrCreate(
                ['project_id' => self::PROJECT_ID, 'title' => 'SEED-'.$po['key']],
                [
                    'tenant_id' => self::TENANT_ID,
                    'author_resident_id' => self::RESIDENT_ID,
                    'body' => $po['body'],
                    'like_count' => $po['likes'],
                    'comment_count' => $po['comments'],
                    'is_pinned' => $po['pinned'],
                    'is_important' => $po['important'],
                    'image_paths' => [],
                    'status' => 'published',
                    'created_at' => Carbon::parse('2026-07-'.(12 + $i)),
                ]
            );
        }

        $events = [
            ['title' => 'Lễ hội thuyền hoa ven sông', 'location' => 'Bến số 1', 'in' => 4, 'cap' => 300, 'reg' => 186],
            ['title' => 'Giải chạy bình minh 5km', 'location' => 'Đường dạo bờ kè', 'in' => 9, 'cap' => 200, 'reg' => 143],
            ['title' => 'Lớp yoga bên sông cho người lớn tuổi', 'location' => 'Vườn Thiền toà A', 'in' => 14, 'cap' => 40, 'reg' => 31],
        ];
        foreach ($events as $e) {
            Event::withoutGlobalScopes()->updateOrCreate(
                ['project_id' => self::PROJECT_ID, 'title' => $e['title']],
                [
                    'tenant_id' => self::TENANT_ID,
                    'description' => 'Sự kiện cộng đồng Đại Phúc Riverside.',
                    'location' => $e['location'],
                    'starts_at' => now()->addDays($e['in'])->setTime(7, 30),
                    'ends_at' => now()->addDays($e['in'])->setTime(11, 0),
                    'capacity' => $e['cap'],
                    'registered_count' => $e['reg'],
                    'status' => 'published',
                ]
            );
        }

        $groups = [
            ['name' => 'CLB Chèo thuyền Đại Phúc', 'desc' => 'Kayak, SUP và chèo thuyền truyền thống', 'members' => 128],
            ['name' => 'Hội câu cá bến số 3', 'desc' => 'Chia sẻ điểm câu, mồi và chiến lợi phẩm', 'members' => 96],
            ['name' => 'Cha mẹ Đại Phúc', 'desc' => 'Trao đổi về trường lớp, đưa đón, hoạt động cho bé', 'members' => 415],
            ['name' => 'Vườn rau ven sông', 'desc' => 'Nhóm trồng rau sạch tại khu vườn cộng đồng', 'members' => 73],
        ];
        foreach ($groups as $g) {
            CommunityGroup::withoutGlobalScopes()->updateOrCreate(
                ['project_id' => self::PROJECT_ID, 'name' => $g['name']],
                [
                    'tenant_id' => self::TENANT_ID,
                    'description' => $g['desc'],
                    'member_count' => $g['members'],
                    'status' => 'active',
                ]
            );
        }

        $this->command?->info('  [DP] Cộng đồng: 7 bài + 3 sự kiện + 4 nhóm (dự án '.self::PROJECT_ID.').');
    }

    /** Tiện ích nội khu — bộ khác dự án 1 để nhìn phát biết đã đổi ngữ cảnh. */
    private function seedAmenities(): void
    {
        $items = [
            ['code' => 'DP-KAYAK', 'name' => 'Bến kayak', 'type' => 'sport', 'cap' => 12, 'open' => '05:30', 'close' => '18:00', 'price' => 0],
            ['code' => 'DP-BBQ-RIVER', 'name' => 'Khu BBQ bờ sông', 'type' => 'bbq', 'cap' => 40, 'open' => '16:00', 'close' => '22:00', 'price' => 200000],
            ['code' => 'DP-POOL', 'name' => 'Bể bơi vô cực', 'type' => 'pool', 'cap' => 60, 'open' => '06:00', 'close' => '21:00', 'price' => 0],
            ['code' => 'DP-YOGA', 'name' => 'Vườn Thiền & Yoga', 'type' => 'gym', 'cap' => 25, 'open' => '05:00', 'close' => '20:00', 'price' => 0],
            ['code' => 'DP-HALL', 'name' => 'Nhà sinh hoạt cộng đồng', 'type' => 'hall', 'cap' => 120, 'open' => '07:00', 'close' => '22:00', 'price' => 500000],
        ];

        foreach ($items as $a) {
            Amenity::withoutGlobalScopes()->updateOrCreate(
                ['tenant_id' => self::TENANT_ID, 'code' => $a['code']],
                [
                    'project_id' => self::PROJECT_ID,
                    'building_id' => self::BUILDING_ID,
                    'name' => $a['name'],
                    'type' => $a['type'],
                    'description' => 'Tiện ích nội khu Đại Phúc Riverside.',
                    'capacity' => $a['cap'],
                    'open_time' => $a['open'],
                    'close_time' => $a['close'],
                    'booking_unit' => 'slot',
                    'price' => $a['price'],
                    'requires_approval' => $a['price'] > 0,
                    'status' => 'active',
                ]
            );
        }

        $this->command?->info('  [DP] Tiện ích: 5 mục (dự án '.self::PROJECT_ID.').');
    }

    /**
     * Ưu đãi — scope theo **TENANT** (bảng `vouchers` không có project_id), nên
     * đây là ưu đãi của tenant 2. Cư dân dự án 1 (tenant 1) sẽ KHÔNG thấy.
     */
    private function seedOffers(): void
    {
        $offers = [
            ['code' => 'DP-CAFE20', 'name' => 'Giảm 20% cà phê bến sông', 'partner' => 'Bến Sông Coffee', 'cat' => 'food', 'type' => 'percent', 'value' => 20, 'points' => 0],
            ['code' => 'DP-KAYAK1', 'name' => 'Miễn phí 1 giờ thuê kayak', 'partner' => 'CLB Chèo Đại Phúc', 'cat' => 'sport', 'type' => 'gift', 'value' => 0, 'points' => 0],
            ['code' => 'DP-SPA15', 'name' => 'Giảm 15% gói chăm sóc da', 'partner' => 'Riverside Spa', 'cat' => 'beauty', 'type' => 'percent', 'value' => 15, 'points' => 0],
            ['code' => 'DP-FISH50', 'name' => 'Giảm 50k hải sản tươi', 'partner' => 'Chợ quê Đại Phúc', 'cat' => 'food', 'type' => 'amount', 'value' => 50000, 'points' => 0],
            ['code' => 'DP-GIFT-BOAT', 'name' => 'Vé du thuyền ngắm hoàng hôn', 'partner' => 'Đại Phúc Marina', 'cat' => 'travel', 'type' => 'gift', 'value' => 0, 'points' => 800],
            ['code' => 'DP-GIFT-DINNER', 'name' => 'Bữa tối 2 người nhà hàng nổi', 'partner' => 'Nhà hàng Bến Nổi', 'cat' => 'food', 'type' => 'gift', 'value' => 0, 'points' => 1500],
        ];

        foreach ($offers as $o) {
            Voucher::withoutGlobalScopes()->updateOrCreate(
                ['tenant_id' => self::TENANT_ID, 'code' => $o['code']],
                [
                    'owner_level' => 'tenant',
                    'name' => $o['name'],
                    'description' => 'Ưu đãi dành riêng cư dân Đại Phúc Riverside.',
                    'partner_name' => $o['partner'],
                    'category' => $o['cat'],
                    'is_public' => false,
                    'type' => $o['type'],
                    'value' => $o['value'],
                    // points_cost > 0 → hiện ở mục "Quà đổi điểm"; = 0 → mục "Ưu đãi".
                    'points_cost' => $o['points'],
                    'quantity' => 100,
                    'valid_from' => now()->subDays(7),
                    'valid_to' => now()->addMonths(3),
                    'status' => 'active',
                ]
            );
        }

        $this->command?->info('  [DP] Ưu đãi: 6 voucher (tenant '.self::TENANT_ID.') — 4 ưu đãi + 2 quà đổi điểm.');
    }
}
