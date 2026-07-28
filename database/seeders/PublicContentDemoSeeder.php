<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Support\DemoImage;
use Illuminate\Database\Seeder;

/**
 * Nội dung CÔNG KHAI mẫu (tin tức + sự kiện) cho Trang chủ & mục Tin tức/Sự kiện
 * của app cư dân.
 *
 * `public/bootstrap` lấy content từ `notifications` cấp **platform** đã publish.
 * Trước đây chỉ có 5 bản ghi tiêu đề trơ, không ảnh không mô tả nên màn hình
 * nhìn rỗng. Seeder này nạp bộ nội dung có tiêu đề · mô tả ngắn · thân bài
 * HTML nhiều tầng · ảnh bìa và ảnh chèn giữa bài.
 *
 * ## Thân bài là HTML CÓ CẤU TRÚC
 *
 * App dựng thân bài bằng `RichHtmlView` (xem
 * `x2mobile/apps/resident_mobile/lib/core/widgets/rich_html_view.dart`), nên ở
 * đây dùng đúng tập thẻ mà nó hiểu: `h2 h3 · p · ul/ol/li · blockquote ·
 * figure/figcaption/img · table · strong/em/a`.
 *
 * Hai lưu ý về ảnh trong bài:
 *
 * - Ảnh bọc trong `<figure>` + `<figcaption>` để có chú thích dưới ảnh.
 * - Ảnh **DÀI** (dạng infographic tiến độ, sơ đồ tầng) đánh dấu
 *   `data-tall="1"`: app sẽ để ảnh cao tự nhiên (`fitWidth`) thay vì cắt vào
 *   khung 16:10 — cắt ảnh dài là mất phần lớn nội dung của nó.
 *
 * Nội dung là NỘI DUNG MẪU của X2-BMS (thông báo vận hành, sự kiện cư dân) —
 * không mượn tên thương hiệu hay sự kiện thật.
 *
 * Idempotent: dedupe theo `code`.
 */
class PublicContentDemoSeeder extends Seeder
{
    /** Ảnh trong thân bài — cùng cơ chế ảnh thật, ổn định theo (chủ đề, khoá). */
    private static function img(string $topic, string $key, int $w = 900, int $h = 560): string
    {
        return DemoImage::url($topic, $key, $w, $h);
    }

    /** Ảnh DÀI (tỉ lệ dọc) cho infographic/sơ đồ — app hiện nguyên khổ. */
    private static function tallImg(string $topic, string $key): string
    {
        return DemoImage::url($topic, $key, 900, 1800);
    }

    public function run(): void
    {
        $now = now();

        $items = [
            [
                'code' => 'PUB-NEWS-LAKE-PREMIUM',
                'type' => 'event',
                'title' => 'Lễ ra mắt phân khu The Lake Premium',
                'summary' => 'Sự kiện ra mắt phân khu cao cấp ven hồ với hơn 300 khách mời, tham quan căn hộ mẫu và công bố chính sách ưu đãi đặt chỗ sớm.',
                'days_ago' => 4,
                'topic' => 'community,event',
                'body' => self::bodyLakePremium(),
            ],
            [
                'code' => 'PUB-NEWS-PROGRESS-07',
                'type' => 'news',
                'title' => 'Tiến độ xây dựng tháng 7/2026',
                'summary' => 'Toà A hoàn thiện mặt ngoài tầng 25, toà B bắt đầu lắp đặt cơ điện. Tiến độ tổng thể đúng kế hoạch bàn giao đã công bố.',
                'days_ago' => 10,
                'topic' => 'building,residential',
                'body' => self::bodyProgress(),
            ],
            [
                'code' => 'PUB-NEWS-POOL-OPENING',
                'type' => 'news',
                'title' => 'Khai trương bể bơi vô cực tầng 30',
                'summary' => 'Bể bơi vô cực, bể trẻ em và khu tắm nắng tầng 30 mở cửa 06:00–21:00 hằng ngày, miễn phí cho cư dân.',
                'days_ago' => 17,
                'topic' => 'amenity,pool',
                'body' => self::bodyPool(),
            ],
            [
                'code' => 'PUB-NEWS-HANDOVER-Q4',
                'type' => 'news',
                'title' => 'Chính sách bàn giao quý IV/2026',
                'summary' => 'Lịch bàn giao theo từng tầng, hồ sơ cần chuẩn bị và quy trình nghiệm thu căn hộ trong bốn bước.',
                'days_ago' => 25,
                'topic' => 'building,residential',
                'body' => self::bodyHandover(),
            ],
            [
                'code' => 'PUB-NEWS-SUMMER-FEST',
                'type' => 'event',
                'title' => 'Ngày hội cư dân mùa hè 2026',
                'summary' => 'Hai ngày cuối tuần với hội chợ gian hàng, sân chơi trẻ em và đêm nhạc ngoài trời tại quảng trường trung tâm.',
                'days_ago' => 32,
                'topic' => 'community,neighbor',
                'body' => self::bodySummerFest(),
            ],
            [
                'code' => 'PUB-NEWS-EV-CHARGER',
                'type' => 'news',
                'title' => 'Bổ sung 12 trụ sạc xe điện tại hầm B1',
                'summary' => 'Hầm B1 có thêm 12 trụ sạc, nâng tổng số toàn dự án lên 28. Đặt chỗ và thanh toán ngay trên ứng dụng.',
                'days_ago' => 40,
                'topic' => 'amenity',
                'body' => self::bodyEvCharger(),
            ],
            [
                'code' => 'PUB-NEWS-FIRE-DRILL',
                'type' => 'event',
                'title' => 'Diễn tập phòng cháy chữa cháy toàn khu',
                'summary' => 'Diễn tập PCCC phối hợp lực lượng địa phương: báo động thử, dùng bình chữa cháy và thoát hiểm theo cầu thang bộ.',
                'days_ago' => 48,
                'topic' => 'community,event',
                'body' => self::bodyFireDrill(),
            ],
            [
                'code' => 'PUB-NEWS-APP-UPDATE',
                'type' => 'news',
                'title' => 'Ứng dụng X2-BMS cập nhật tính năng thanh toán',
                'summary' => 'Thanh toán phí dịch vụ bằng chuyển khoản hoặc ví điện tử, hoá đơn điện tử lưu ngay trong ứng dụng.',
                'days_ago' => 55,
                'topic' => 'interior',
                'body' => self::bodyAppUpdate(),
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
                    'cover_path' => DemoImage::url($item['topic'], crc32($item['code']), 800, 500),
                    'publish_at' => $publishedAt,
                    'published_at' => $publishedAt,
                ]
            );
        }

        $this->command?->info('Đã seed '.count($items).' nội dung công khai (HTML có cấu trúc + ảnh trong bài).');
    }

    private static function bodyLakePremium(): string
    {
        $hero = self::img('community', 'lake-1');
        $model = self::img('amenity', 'lake-2');

        return <<<HTML
<p>Lễ ra mắt phân khu <strong>The Lake Premium</strong> diễn ra tại sảnh sự kiện tầng 1 toà A với sự tham dự của đại diện chủ đầu tư, đơn vị vận hành và hơn 300 khách mời.</p>

<figure>
  <img src="{$hero}" alt="Sảnh sự kiện trong lễ ra mắt phân khu" />
  <figcaption>Sảnh sự kiện tầng 1 toà A được bố trí cho 320 chỗ ngồi.</figcaption>
</figure>

<h2>Chương trình sự kiện</h2>
<ol>
  <li><strong>08:30 – 09:00</strong> — Đón khách, nhận tài liệu phân khu.</li>
  <li><strong>09:00 – 10:00</strong> — Giới thiệu quy hoạch, mật độ xây dựng và hệ tiện ích ven hồ.</li>
  <li><strong>10:00 – 11:00</strong> — Tham quan hai căn hộ mẫu (2PN và 3PN).</li>
  <li><strong>11:00 – 11:30</strong> — Công bố chính sách ưu đãi đặt chỗ sớm.</li>
</ol>

<h3>Điểm nhấn của phân khu</h3>
<ul>
  <li>Toàn bộ 4 toà đều có hướng nhìn ra hồ điều hoà rộng 3,2 ha.</li>
  <li>Khối đế thương mại kết nối trực tiếp với quảng trường trung tâm.</li>
  <li>Bãi đỗ xe hai tầng hầm, tỉ lệ 1,2 chỗ/căn.</li>
</ul>

<figure>
  <img src="{$model}" alt="Căn hộ mẫu 3 phòng ngủ" />
  <figcaption>Căn hộ mẫu 3PN mở cửa tham quan trong suốt sự kiện.</figcaption>
</figure>

<blockquote>Cư dân quan tâm vui lòng đăng ký trước qua ứng dụng để ban tổ chức chuẩn bị chỗ ngồi và tài liệu.</blockquote>

<p>Mọi thắc mắc về sự kiện, liên hệ ban quản lý qua mục <em>Hỗ trợ</em> trong ứng dụng.</p>
HTML;
    }

    private static function bodyProgress(): string
    {
        $site = self::img('building', 'prog-1');
        $chart = self::tallImg('building', 'prog-tall');

        return <<<HTML
<p>Trong tháng 7/2026, công trường đạt các mốc chính dưới đây. Tiến độ tổng thể <strong>đúng kế hoạch</strong> bàn giao đã công bố.</p>

<figure>
  <img src="{$site}" alt="Toàn cảnh công trường tháng 7/2026" />
  <figcaption>Toàn cảnh công trường chụp đầu tháng 7/2026.</figcaption>
</figure>

<h2>Mốc thi công từng toà</h2>
<table>
  <tr><th>Hạng mục</th><th>Kế hoạch</th><th>Thực tế</th></tr>
  <tr><td>Toà A — mặt ngoài</td><td>Tầng 24</td><td>Tầng 25</td></tr>
  <tr><td>Toà A — lắp kính</td><td>Tầng 16–20</td><td>Tầng 18–22</td></tr>
  <tr><td>Toà B — cơ điện</td><td>Bắt đầu</td><td>Bắt đầu</td></tr>
  <tr><td>Cảnh quan</td><td>30%</td><td>34%</td></tr>
</table>

<h3>Chi tiết theo hạng mục</h3>
<ul>
  <li><strong>Toà A:</strong> hoàn thiện mặt ngoài tới tầng 25, lắp kính tầng 18–22, bắt đầu hoàn thiện nội thất tầng 5–8.</li>
  <li><strong>Toà B:</strong> lắp đặt hệ thống cơ điện và thang máy tầng hầm; nghiệm thu hệ thống chữa cháy trục đứng.</li>
  <li><strong>Cảnh quan:</strong> trồng cây khu vực quảng trường trung tâm, thi công đường dạo ven hồ.</li>
</ul>

<h2>Sơ đồ tiến độ luỹ kế</h2>
<figure>
  <img src="{$chart}" alt="Sơ đồ tiến độ luỹ kế từng hạng mục" data-tall="1" />
  <figcaption>Sơ đồ tiến độ luỹ kế — bấm vào ảnh để xem toàn màn hình và phóng to.</figcaption>
</figure>

<p>Ảnh tiến độ được cập nhật vào tuần đầu mỗi tháng. Cư dân đã xác thực có thể xem thêm biên bản nghiệm thu trong mục <em>Tài liệu</em>.</p>
HTML;
    }

    private static function bodyPool(): string
    {
        $pool = self::img('amenity', 'pool-1');

        return <<<HTML
<p>Bể bơi vô cực tầng 30 chính thức mở cửa phục vụ cư dân từ <strong>06:00 đến 21:00</strong> hằng ngày, không thu phí.</p>

<figure>
  <img src="{$pool}" alt="Bể bơi vô cực tầng 30" />
  <figcaption>Bể bơi vô cực tầng 30 nhìn ra hướng đông.</figcaption>
</figure>

<h2>Khu vực gồm</h2>
<ul>
  <li>Bể bơi người lớn dài 25 m, sâu 1,2–1,4 m.</li>
  <li>Bể trẻ em sâu 0,5 m, có mái che.</li>
  <li>Phòng thay đồ, tủ khoá và buồng tắm tráng.</li>
  <li>Khu tắm nắng 24 ghế.</li>
</ul>

<h2>Nội quy cần lưu ý</h2>
<ol>
  <li>Mang thẻ cư dân khi vào; khách mời phải đăng ký trước tại quầy lễ tân.</li>
  <li>Trẻ dưới 12 tuổi phải có người lớn đi kèm.</li>
  <li>Tắm tráng trước khi xuống bể; không mang đồ ăn, thức uống có màu vào khu bể.</li>
</ol>

<blockquote>Bể tạm đóng 30 phút mỗi ngày (13:00–13:30) để vệ sinh và kiểm tra chỉ số nước.</blockquote>
HTML;
    }

    private static function bodyHandover(): string
    {
        $unit = self::img('interior', 'handover-1');
        $floor = self::tallImg('building', 'handover-tall');

        return <<<HTML
<p>Ban quản lý thông báo chính sách bàn giao căn hộ quý IV/2026. Chủ căn hộ vui lòng đọc kỹ phần hồ sơ trước khi đặt lịch.</p>

<h2>Lịch bàn giao</h2>
<p>Lịch chia theo tầng, mỗi ngày tối đa <strong>12 căn</strong> để đảm bảo thời gian nghiệm thu cho từng căn.</p>
<table>
  <tr><th>Tầng</th><th>Thời gian</th></tr>
  <tr><td>5 – 12</td><td>01/10 – 12/10/2026</td></tr>
  <tr><td>13 – 20</td><td>15/10 – 28/10/2026</td></tr>
  <tr><td>21 – 30</td><td>01/11 – 20/11/2026</td></tr>
</table>

<h2>Hồ sơ cần mang</h2>
<ul>
  <li>CCCD của chủ căn hộ (hoặc giấy uỷ quyền có công chứng nếu cử người đại diện).</li>
  <li>Hợp đồng mua bán.</li>
  <li>Biên nhận thanh toán các đợt.</li>
</ul>

<figure>
  <img src="{$unit}" alt="Căn hộ đã hoàn thiện chờ bàn giao" />
  <figcaption>Căn hộ hoàn thiện cơ bản, chờ nghiệm thu bàn giao.</figcaption>
</figure>

<h2>Quy trình nghiệm thu</h2>
<ol>
  <li>Kiểm tra hiện trạng cùng kỹ thuật viên (khoảng 45 phút/căn).</li>
  <li>Ghi nhận hạng mục cần sửa vào biên bản.</li>
  <li>Ký biên bản bàn giao và biên bản tồn đọng (nếu có).</li>
  <li>Nhận chìa khoá và thẻ cư dân.</li>
</ol>

<h2>Sơ đồ mặt bằng tầng điển hình</h2>
<figure>
  <img src="{$floor}" alt="Sơ đồ mặt bằng tầng điển hình" data-tall="1" />
  <figcaption>Mặt bằng tầng điển hình — phóng to để xem số căn và diện tích.</figcaption>
</figure>

<p>Chủ căn hộ có thể đặt lịch nghiệm thu ngay trên ứng dụng sau khi xác thực cư dân.</p>
HTML;
    }

    private static function bodySummerFest(): string
    {
        $fest = self::img('community', 'fest-1');
        $kids = self::img('community', 'fest-2');

        return <<<HTML
<p>Ngày hội cư dân mùa hè diễn ra trong <strong>hai ngày cuối tuần</strong> tại quảng trường trung tâm. Vào cổng miễn phí cho cư dân và người thân.</p>

<figure>
  <img src="{$fest}" alt="Quảng trường trung tâm trong ngày hội" />
  <figcaption>Quảng trường trung tâm được dựng 28 gian hàng.</figcaption>
</figure>

<h2>Hoạt động chính</h2>
<ul>
  <li><strong>Hội chợ gian hàng</strong> của cư dân và đối tác nội khu — đồ ăn, đồ handmade, cây cảnh.</li>
  <li><strong>Sân chơi vận động</strong> cho trẻ em, có nhân viên trông giữ theo giờ.</li>
  <li><strong>Đêm nhạc ngoài trời</strong> từ 19:30, kết thúc trước 22:00 để không ảnh hưởng giờ nghỉ.</li>
</ul>

<figure>
  <img src="{$kids}" alt="Khu vui chơi trẻ em trong ngày hội" />
  <figcaption>Khu vui chơi trẻ em mở từ 09:00 đến 18:00 cả hai ngày.</figcaption>
</figure>

<h3>Đăng ký gian hàng</h3>
<p>Cư dân muốn mở gian hàng gửi đăng ký qua mục <em>Cộng đồng</em> trong ứng dụng trước ngày khai mạc 5 ngày. Ban tổ chức bố trí gian theo thứ tự đăng ký.</p>

<blockquote>Khu vực đỗ xe khách sẽ chuyển tạm sang hầm B2 trong hai ngày diễn ra ngày hội.</blockquote>
HTML;
    }

    private static function bodyEvCharger(): string
    {
        $charger = self::img('interior', 'ev-1');

        return <<<HTML
<p>Nhằm đáp ứng nhu cầu tăng nhanh, ban quản lý đã lắp đặt thêm <strong>12 trụ sạc xe điện</strong> tại hầm B1, nâng tổng số trụ toàn dự án lên <strong>28</strong>.</p>

<figure>
  <img src="{$charger}" alt="Trụ sạc xe điện tại hầm B1" />
  <figcaption>Cụm trụ sạc mới tại hầm B1, khu vực gần thang máy số 3.</figcaption>
</figure>

<h2>Thông số</h2>
<table>
  <tr><th>Loại trụ</th><th>Công suất</th><th>Số lượng</th></tr>
  <tr><td>Sạc thường (AC)</td><td>7 kW</td><td>8</td></tr>
  <tr><td>Sạc nhanh (DC)</td><td>30 kW</td><td>4</td></tr>
</table>

<h2>Cách sử dụng</h2>
<ol>
  <li>Đặt chỗ sạc trên ứng dụng, chọn trụ và khung giờ.</li>
  <li>Quét mã trên trụ để bắt đầu; ứng dụng hiển thị công suất và số điện theo thời gian thực.</li>
  <li>Thanh toán tự động vào hoá đơn dịch vụ hằng tháng.</li>
</ol>

<blockquote>Thời gian sạc tối đa mỗi lượt là 4 giờ trong giờ cao điểm (17:00–21:00) để nhiều cư dân dùng được.</blockquote>
HTML;
    }

    private static function bodyFireDrill(): string
    {
        $drill = self::img('community', 'drill-1');
        $route = self::tallImg('building', 'drill-tall');

        return <<<HTML
<p>Buổi diễn tập phòng cháy chữa cháy được tổ chức phối hợp với lực lượng PCCC địa phương. Cư dân vui lòng dành <strong>45 phút</strong> tham gia — kỹ năng này rất cần trong tình huống thật.</p>

<figure>
  <img src="{$drill}" alt="Cư dân tham gia diễn tập PCCC" />
  <figcaption>Cư dân được hướng dẫn sử dụng bình chữa cháy tại sân trước toà A.</figcaption>
</figure>

<h2>Nội dung diễn tập</h2>
<ol>
  <li>Báo động thử toàn khu (chuông báo sẽ reo 30 giây).</li>
  <li>Hướng dẫn sử dụng bình chữa cháy bột và bình CO₂.</li>
  <li>Thoát hiểm theo cầu thang bộ, không dùng thang máy.</li>
  <li>Tập trung tại điểm an toàn ở sân trước toà A và điểm danh theo tầng.</li>
</ol>

<h2>Sơ đồ đường thoát hiểm</h2>
<figure>
  <img src="{$route}" alt="Sơ đồ đường thoát hiểm theo tầng" data-tall="1" />
  <figcaption>Sơ đồ thoát hiểm — phóng to để xem lối thoát gần căn hộ của bạn.</figcaption>
</figure>

<h3>Ba điều cần nhớ</h3>
<ul>
  <li>Không dùng thang máy khi có báo cháy.</li>
  <li>Đi sát tường, cúi thấp người, che mũi miệng bằng khăn ẩm.</li>
  <li>Nhớ vị trí <strong>hai</strong> cầu thang thoát hiểm gần căn hộ mình.</li>
</ul>
HTML;
    }

    private static function bodyAppUpdate(): string
    {
        $shot = self::img('interior', 'app-1');

        return <<<HTML
<p>Bản cập nhật mới cho phép cư dân thanh toán phí dịch vụ trực tiếp trong ứng dụng qua <strong>chuyển khoản</strong> hoặc <strong>ví điện tử</strong>.</p>

<figure>
  <img src="{$shot}" alt="Màn hình thanh toán trong ứng dụng" />
  <figcaption>Mục "Thanh toán" hiển thị hoá đơn theo kỳ và lịch sử giao dịch.</figcaption>
</figure>

<h2>Có gì mới</h2>
<ul>
  <li>Thanh toán bằng mã VietQR sinh trực tiếp trong ứng dụng.</li>
  <li>Hoá đơn điện tử lưu trong mục <em>Thanh toán</em>, tải lại bất cứ lúc nào.</li>
  <li>Lịch sử giao dịch đối chiếu tự động với sổ thu chi của ban quản lý.</li>
  <li>Nhắc hạn thanh toán trước 3 ngày qua thông báo đẩy.</li>
</ul>

<h2>Cách cập nhật</h2>
<ol>
  <li>Mở App Store hoặc Google Play, tìm <strong>X2-BMS</strong>.</li>
  <li>Bấm <em>Cập nhật</em> và mở lại ứng dụng.</li>
</ol>

<blockquote>Nếu hoá đơn chưa hiện sau khi cập nhật, kéo xuống để tải lại hoặc liên hệ ban quản lý qua mục Hỗ trợ.</blockquote>
HTML;
    }
}
