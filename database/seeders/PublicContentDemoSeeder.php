<?php

namespace Database\Seeders;

use App\Models\Notification;
use Illuminate\Database\Seeder;

/**
 * Nội dung CÔNG KHAI mẫu (tin tức + sự kiện) cho Trang chủ & mục Tin tức/Sự kiện
 * của app cư dân.
 *
 * `public/bootstrap` lấy content từ `notifications` cấp **platform** đã publish.
 * Trước đây chỉ có 5 bản ghi tiêu đề trơ, không ảnh không mô tả nên màn hình
 * nhìn rỗng. Seeder này nạp bộ nội dung có tiêu đề · mô tả ngắn · thân bài nhiều
 * đoạn · ảnh bìa theo chủ đề.
 *
 * Nội dung là NỘI DUNG MẪU của X2-BMS (thông báo vận hành, sự kiện cư dân) —
 * không mượn tên thương hiệu hay sự kiện thật.
 *
 * Idempotent: dedupe theo `code`.
 */
class PublicContentDemoSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $items = [
            [
                'code' => 'PUB-NEWS-LAKE-PREMIUM',
                'type' => 'event',
                'title' => 'Lễ ra mắt phân khu The Lake Premium',
                'summary' => 'Sự kiện ra mắt phân khu cao cấp ven hồ với hơn 300 khách mời và chương trình ưu đãi đặt chỗ sớm.',
                'days_ago' => 4,
                'topic' => 'community,event',
                'body' => <<<'HTML'
<p>Lễ ra mắt phân khu <strong>The Lake Premium</strong> sẽ diễn ra tại sảnh sự kiện tầng 1 toà A với sự tham dự của đại diện chủ đầu tư, đơn vị vận hành và hơn 300 khách mời.</p>
<p>Chương trình gồm ba phần: giới thiệu quy hoạch phân khu, tham quan căn hộ mẫu và công bố chính sách ưu đãi đặt chỗ sớm.</p>
<p>Cư dân quan tâm vui lòng đăng ký trước qua ứng dụng để ban tổ chức chuẩn bị chỗ ngồi.</p>
HTML,
            ],
            [
                'code' => 'PUB-NEWS-PROGRESS-07',
                'type' => 'news',
                'title' => 'Tiến độ xây dựng tháng 7/2026',
                'summary' => 'Toà A hoàn thiện mặt ngoài tầng 25, toà B bắt đầu lắp đặt thiết bị cơ điện.',
                'days_ago' => 10,
                'topic' => 'building,residential',
                'body' => <<<'HTML'
<p>Trong tháng 7/2026, công trường đạt các mốc chính sau:</p>
<li>Toà A: hoàn thiện mặt ngoài tới tầng 25, lắp kính tầng 18–22.</li>
<li>Toà B: bắt đầu lắp đặt hệ thống cơ điện, thang máy tầng hầm.</li>
<li>Cảnh quan: trồng cây khu vực quảng trường trung tâm.</li>
<p>Tiến độ tổng thể đang theo đúng kế hoạch bàn giao đã công bố.</p>
HTML,
            ],
            [
                'code' => 'PUB-NEWS-POOL-OPENING',
                'type' => 'news',
                'title' => 'Khai trương bể bơi vô cực tầng 30',
                'summary' => 'Bể bơi vô cực và khu tắm nắng tầng 30 mở cửa từ 06:00 đến 21:00 hằng ngày.',
                'days_ago' => 17,
                'topic' => 'amenity,pool',
                'body' => <<<'HTML'
<p>Bể bơi vô cực tầng 30 chính thức mở cửa phục vụ cư dân từ <strong>06:00 đến 21:00</strong> hằng ngày.</p>
<p>Khu vực gồm bể bơi người lớn, bể trẻ em, phòng thay đồ và khu tắm nắng. Cư dân vui lòng mang thẻ cư dân khi sử dụng và tuân thủ nội quy an toàn được niêm yết tại lối vào.</p>
HTML,
            ],
            [
                'code' => 'PUB-NEWS-HANDOVER-Q4',
                'type' => 'news',
                'title' => 'Chính sách bàn giao quý IV/2026',
                'summary' => 'Lịch bàn giao theo từng tầng, hồ sơ cần chuẩn bị và quy trình nghiệm thu căn hộ.',
                'days_ago' => 25,
                'topic' => 'building,residential',
                'body' => <<<'HTML'
<p>Ban quản lý thông báo chính sách bàn giao căn hộ quý IV/2026:</p>
<li>Lịch bàn giao chia theo tầng, mỗi ngày tối đa 12 căn để đảm bảo thời gian nghiệm thu.</li>
<li>Hồ sơ cần mang: CCCD, hợp đồng mua bán, biên nhận thanh toán.</li>
<li>Quy trình: kiểm tra hiện trạng → ghi nhận hạng mục cần sửa → ký biên bản → nhận chìa khoá.</li>
<p>Chủ căn hộ có thể đặt lịch nghiệm thu ngay trên ứng dụng sau khi xác thực cư dân.</p>
HTML,
            ],
            [
                'code' => 'PUB-NEWS-SUMMER-FEST',
                'type' => 'event',
                'title' => 'Ngày hội cư dân mùa hè 2026',
                'summary' => 'Hội chợ, sân chơi trẻ em và đêm nhạc ngoài trời tại quảng trường trung tâm.',
                'days_ago' => 32,
                'topic' => 'community,neighbor',
                'body' => <<<'HTML'
<p>Ngày hội cư dân mùa hè diễn ra trong hai ngày cuối tuần tại quảng trường trung tâm, gồm:</p>
<li>Hội chợ gian hàng của cư dân và đối tác nội khu.</li>
<li>Sân chơi vận động cho trẻ em, có nhân viên trông giữ.</li>
<li>Đêm nhạc ngoài trời từ 19:30.</li>
<p>Vào cổng miễn phí cho cư dân và người thân.</p>
HTML,
            ],
            [
                'code' => 'PUB-NEWS-EV-CHARGER',
                'type' => 'news',
                'title' => 'Bổ sung 12 trụ sạc xe điện tại hầm B1',
                'summary' => 'Hầm B1 có thêm 12 trụ sạc, đặt chỗ và thanh toán ngay trên ứng dụng.',
                'days_ago' => 40,
                'topic' => 'amenity',
                'body' => <<<'HTML'
<p>Nhằm đáp ứng nhu cầu tăng nhanh, ban quản lý đã lắp đặt thêm <strong>12 trụ sạc xe điện</strong> tại hầm B1, nâng tổng số trụ toàn dự án lên 28.</p>
<p>Cư dân đặt chỗ sạc và thanh toán trực tiếp trên ứng dụng. Thời gian sạc tối đa mỗi lượt là 4 giờ trong giờ cao điểm.</p>
HTML,
            ],
            [
                'code' => 'PUB-NEWS-FIRE-DRILL',
                'type' => 'event',
                'title' => 'Diễn tập phòng cháy chữa cháy toàn khu',
                'summary' => 'Diễn tập PCCC có sự tham gia của lực lượng địa phương, cư dân được hướng dẫn thoát hiểm.',
                'days_ago' => 48,
                'topic' => 'community,event',
                'body' => <<<'HTML'
<p>Buổi diễn tập phòng cháy chữa cháy được tổ chức phối hợp với lực lượng PCCC địa phương.</p>
<p>Nội dung: báo động thử, hướng dẫn sử dụng bình chữa cháy, thoát hiểm theo cầu thang bộ và tập trung tại điểm an toàn.</p>
<p>Cư dân vui lòng dành 45 phút tham gia — kỹ năng này rất cần trong tình huống thật.</p>
HTML,
            ],
            [
                'code' => 'PUB-NEWS-APP-UPDATE',
                'type' => 'news',
                'title' => 'Ứng dụng X2-BMS cập nhật tính năng thanh toán',
                'summary' => 'Thanh toán phí dịch vụ bằng chuyển khoản, ví điện tử và lưu hoá đơn điện tử trong ứng dụng.',
                'days_ago' => 55,
                'topic' => 'interior',
                'body' => <<<'HTML'
<p>Bản cập nhật mới cho phép cư dân thanh toán phí dịch vụ trực tiếp trong ứng dụng qua chuyển khoản hoặc ví điện tử.</p>
<p>Hoá đơn điện tử được lưu trong mục "Thanh toán", có thể tải lại bất cứ lúc nào. Lịch sử giao dịch đối chiếu tự động với sổ thu chi của ban quản lý.</p>
HTML,
            ],
        ];

        foreach ($items as $item) {
            $publishedAt = $now->copy()->subDays($item['days_ago']);

            Notification::withoutGlobalScopes()->updateOrCreate(
                ['code' => $item['code']],
                [
                    'tenant_id' => null,
                    'owner_level' => 'platform',
                    'project_id' => null,
                    'building_id' => null,
                    'type' => $item['type'],
                    'title' => $item['title'],
                    'summary' => $item['summary'],
                    'body' => $item['body'],
                    'priority' => 'normal',
                    'status' => 'published',
                    'is_pinned' => false,
                    // Ảnh bìa: URL đầy đủ nên resource/bootstrap dùng trực tiếp,
                    // không cần storage:link.
                    'cover_path' => \App\Support\DemoImage::url($item['topic'], crc32($item['code']), 800, 500),
                    'publish_at' => $publishedAt,
                    'published_at' => $publishedAt,
                ]
            );
        }

        $this->command?->info('Đã seed '.count($items).' nội dung công khai (tin tức + sự kiện, có ảnh + mô tả).');
    }
}
