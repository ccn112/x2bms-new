<?php

namespace Database\Seeders;

use App\Models\PlatformContent;
use App\Models\PlatformContentCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Bài viết cư dân đọc — PlatformContent (platform-global, publish_scope điều
 * khiển "ai quản lý"): platform = SuperAdmin, tenant = Công ty QLVH, building =
 * BQL toà. content_type phân loại: policy (quy định/nội quy/điều khoản) ·
 * guide (cẩm nang/hướng dẫn) · news (tin tức).
 *
 * Cư dân đọc qua GET /resident/articles (ArticleController) — trả về các bài
 * status=published, is_active. (Bản demo: mọi scope hiển thị cho cư dân.)
 */
class ResidentArticleSeeder extends Seeder
{
    public function run(): void
    {
        $cat = fn (string $code, string $name, string $type, int $sort) =>
            PlatformContentCategory::query()->updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'type' => $type, 'is_active' => true, 'sort_order' => $sort],
            )->id;

        $catQuyDinh = $cat('resident-policy', 'Quy định', 'policy', 1);
        $catCamNang = $cat('resident-guide', 'Cẩm nang', 'guide', 2);
        $catTinTuc = $cat('resident-news', 'Tin tức', 'news', 3);

        $now = Carbon::now();

        // [slug, title, summary, body, content_type, publish_scope, category, daysAgo]
        $articles = [
            // ── SuperAdmin (platform) ──
            ['about-x2-living', 'Về X2 Living',
                'Giới thiệu nền tảng X2 Living — kết nối cư dân, căn hộ và tiện ích.',
                "X2 Living là nền tảng quản lý vận hành chung cư thông minh, kết nối cư dân với ban quản lý và các tiện ích trong khu.\n\nQua ứng dụng, bạn có thể: thanh toán phí dịch vụ, đăng ký khách, đặt tiện ích, gửi phản ánh, nhận thông báo khẩn và tham gia cộng đồng cư dân — tất cả trong một chạm.",
                'guide', 'platform', $catCamNang, 30],
            ['terms-and-policy', 'Điều khoản & Chính sách',
                'Điều khoản sử dụng và chính sách bảo mật dữ liệu cư dân.',
                "1. Chấp thuận điều khoản\nKhi sử dụng ứng dụng, bạn đồng ý với các điều khoản dịch vụ của X2 Living.\n\n2. Bảo mật dữ liệu\nThông tin cá nhân và giao dịch của bạn được mã hoá và chỉ dùng cho mục đích vận hành dịch vụ.\n\n3. Quyền & nghĩa vụ\nCư dân có trách nhiệm cung cấp thông tin chính xác và tuân thủ nội quy khu.",
                'policy', 'platform', $catQuyDinh, 28],
            // ── Công ty quản lý vận hành (tenant) ──
            ['company-service-policy', 'Chính sách dịch vụ của công ty quản lý',
                'Cam kết chất lượng dịch vụ và quy trình hỗ trợ cư dân.',
                "Công ty quản lý vận hành cam kết:\n\n- Phản hồi phản ánh trong vòng 24 giờ.\n- Bảo trì định kỳ hệ thống PCCC, thang máy, cấp nước.\n- Minh bạch phí dịch vụ và công khai thu chi hằng tháng.\n- Đường dây nóng hỗ trợ 24/7.",
                'policy', 'tenant', $catQuyDinh, 20],
            // ── BQL toà (building) ──
            ['building-house-rules', 'Nội quy toà nhà',
                'Quy định chung về sinh hoạt, an ninh và sử dụng tiện ích.',
                "1. Giờ yên tĩnh: 22:00 – 06:00.\n2. Không nuôi vật nuôi gây ảnh hưởng hàng xóm.\n3. Đổ rác đúng nơi, đúng giờ quy định.\n4. Khách ra vào phải đăng ký qua ứng dụng.\n5. Sử dụng tiện ích chung theo lịch đặt và nội quy khu vực.",
                'policy', 'building', $catQuyDinh, 12],
            ['amenity-usage-guide', 'Hướng dẫn sử dụng tiện ích',
                'Cách đặt và sử dụng hồ bơi, gym, BBQ, phòng cộng đồng.',
                "- Đặt lịch tiện ích qua tab Tiện ích → Đặt tiện ích.\n- Nhận mã QR check-in khi đến.\n- Tuân thủ số lượng người và khung giờ đã đặt.\n- Giữ vệ sinh chung sau khi sử dụng.",
                'guide', 'building', $catCamNang, 8],
            ['resident-guide', 'Cẩm nang cư dân mới',
                'Những điều cần biết khi bắt đầu sinh sống tại khu căn hộ.',
                "Chào mừng bạn đến với cộng đồng!\n\n- Kích hoạt tài khoản và xác thực căn hộ trong mục Cá nhân.\n- Thanh toán phí dịch vụ hằng tháng ở tab Tiện ích.\n- Đăng ký khách và nhận mã QR ra vào.\n- Gửi phản ánh khi cần hỗ trợ kỹ thuật.\n- Theo dõi thông báo từ ban quản lý ở màn Trang chủ.",
                'guide', 'platform', $catCamNang, 5],
        ];

        foreach ($articles as $a) {
            PlatformContent::query()->updateOrCreate(
                ['slug' => $a[0]],
                [
                    'category_id' => $a[6],
                    'title' => $a[1],
                    'summary' => $a[2],
                    'body' => $a[3],
                    'content_type' => $a[4],
                    'publish_scope' => $a[5],
                    'status' => 'published',
                    'language' => 'vi',
                    'published_at' => $now->copy()->subDays($a[7]),
                ]
            );
        }

        $this->command?->info('  Articles: '.count($articles).' bài PlatformContent (policy/guide/news · platform/tenant/building) published cho cư dân.');
    }
}
