<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Support\DemoImage;
use Illuminate\Database\Seeder;

/**
 * Cẩm nang cư dân (M01-PUB-06/13) — bài HƯỚNG DẪN dùng lâu dài.
 *
 * Vì sao là `type = 'guide'` chứ không nhét vào `news`: cẩm nang không cũ đi
 * theo ngày. Trộn vào bảng tin thì hôm sau nó trôi xuống dưới một thông báo
 * "diễn tập PCCC" và người mới vào app không còn thấy bài "cách xác thực cư dân"
 * nữa — đúng bài họ cần nhất.
 *
 * Thân bài dùng cùng tập thẻ HTML mà `RichHtmlView` của app hiểu (xem
 * `PublicContentDemoSeeder` để biết chi tiết), có ảnh kèm chú thích.
 *
 * Idempotent: dedupe theo `code`.
 */
class PublicGuideDemoSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $items = [
            [
                'code' => 'PUB-GUIDE-BECOME-RESIDENT',
                'title' => 'Hướng dẫn xác thực cư dân trong 4 bước',
                'summary' => 'Từ đăng ký thành viên tới lúc được ban quản lý duyệt: cần giấy tờ gì, mất bao lâu và xử lý khi bị từ chối.',
                'topic' => 'residential,building',
                'days_ago' => 3,
                'body' => self::bodyBecomeResident(),
            ],
            [
                'code' => 'PUB-GUIDE-PAY-FEES',
                'title' => 'Thanh toán phí dịch vụ: các cách và lưu ý',
                'summary' => 'So sánh chuyển khoản, VietQR và ví điện tử; cách đối chiếu hoá đơn và xử lý khi tiền đã trừ mà app chưa ghi nhận.',
                'topic' => 'interior',
                'days_ago' => 8,
                'body' => self::bodyPayFees(),
            ],
            [
                'code' => 'PUB-GUIDE-BOOK-AMENITY',
                'title' => 'Đặt tiện ích nội khu đúng cách',
                'summary' => 'Quy tắc đặt bể bơi, phòng gym, sân tennis và phòng sinh hoạt cộng đồng; mức phí, thời gian huỷ và các lỗi thường gặp.',
                'topic' => 'amenity,pool',
                'days_ago' => 14,
                'body' => self::bodyBookAmenity(),
            ],
            [
                'code' => 'PUB-GUIDE-VISITOR',
                'title' => 'Đăng ký khách và giao nhận hàng',
                'summary' => 'Cách tạo mã khách, thời hạn hiệu lực của mã, quy định gửi xe cho khách và nhận hàng khi không có nhà.',
                'topic' => 'community',
                'days_ago' => 21,
                'body' => self::bodyVisitor(),
            ],
            [
                'code' => 'PUB-GUIDE-FEEDBACK',
                'title' => 'Gửi phản ánh sao cho được xử lý nhanh',
                'summary' => 'Chọn đúng hạng mục, chụp ảnh gì, mô tả thế nào và cách theo dõi tiến độ xử lý phản ánh.',
                'topic' => 'residential',
                'days_ago' => 28,
                'body' => self::bodyFeedback(),
            ],
            [
                'code' => 'PUB-GUIDE-SAFETY',
                'title' => 'An toàn trong toà nhà: những điều phải biết',
                'summary' => 'Vị trí thang thoát hiểm, cách xử lý khi có báo cháy, kẹt thang máy, rò rỉ nước và số điện thoại khẩn cấp.',
                'topic' => 'building',
                'days_ago' => 35,
                'body' => self::bodySafety(),
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
                    'type' => 'guide',
                    'title' => $item['title'],
                    'summary' => $item['summary'],
                    'body' => $item['body'],
                    'priority' => 'normal',
                    'status' => 'published',
                    'is_pinned' => false,
                    'cover_path' => DemoImage::url($item['topic'], crc32($item['code']), 800, 500),
                    'publish_at' => $publishedAt,
                    'published_at' => $publishedAt,
                ]
            );
        }

        $this->command?->info('Đã seed '.count($items).' bài cẩm nang cư dân (type=guide).');
    }

    private static function bodyBecomeResident(): string
    {
        $img = DemoImage::url('residential', 'guide-res-1');

        return <<<HTML
<p>Xác thực cư dân là bước gắn tài khoản của bạn với một căn hộ cụ thể. Chỉ sau bước này bạn mới thanh toán phí, đặt tiện ích và nhận thông báo của toà mình.</p>

<figure>
  <img src="{$img}" alt="Quầy lễ tân ban quản lý" />
  <figcaption>Bạn có thể xác thực ngay trên app, không cần lên quầy lễ tân.</figcaption>
</figure>

<h2>Bốn bước</h2>
<ol>
  <li><strong>Đăng ký thành viên</strong> bằng email hoặc số điện thoại, kích hoạt bằng mã OTP.</li>
  <li><strong>Chọn dự án và căn hộ</strong> bạn đang ở trong mục "Đăng ký làm cư dân".</li>
  <li><strong>Tải giấy tờ</strong>: hợp đồng mua bán (nếu là chủ hộ) hoặc hợp đồng thuê kèm giấy tờ chủ hộ (nếu đi thuê).</li>
  <li><strong>Chờ ban quản lý duyệt</strong> — thường 1–2 ngày làm việc.</li>
</ol>

<h2>Ảnh giấy tờ thế nào là đạt</h2>
<ul>
  <li>Chụp đủ 4 góc, không cắt mất số hợp đồng và mã căn hộ.</li>
  <li>Đủ sáng, không loá đèn flash lên vùng chữ.</li>
  <li>Chụp bằng camera, không chụp lại ảnh trên màn hình máy khác.</li>
</ul>

<blockquote>Bị từ chối thì trong app có ghi rõ lý do. Sửa đúng phần bị nêu rồi gửi lại — không cần làm lại từ bước một.</blockquote>

<h3>Trường hợp đặc biệt</h3>
<p>Nếu căn hộ đã có người khác xác thực làm chủ hộ, bạn vẫn đăng ký được với vai trò <em>thành viên hộ gia đình</em>. Chủ hộ sẽ nhận thông báo để xác nhận.</p>
HTML;
    }

    private static function bodyPayFees(): string
    {
        $img = DemoImage::url('interior', 'guide-pay-1');

        return <<<HTML
<p>Phí dịch vụ được chốt vào đầu mỗi kỳ và hiện trong mục <em>Thanh toán</em>. Bạn trả trực tiếp trong app, không cần ra quầy.</p>

<h2>Ba cách trả</h2>
<table>
  <tr><th>Cách</th><th>Ghi nhận</th><th>Phí</th></tr>
  <tr><td>VietQR trong app</td><td>Tức thì</td><td>Miễn phí</td></tr>
  <tr><td>Chuyển khoản thủ công</td><td>1–2 giờ làm việc</td><td>Theo bảng phí ngân hàng</td></tr>
  <tr><td>Ví điện tử</td><td>Tức thì</td><td>Miễn phí</td></tr>
</table>

<figure>
  <img src="{$img}" alt="Màn hình thanh toán trong app" />
  <figcaption>Mã VietQR sinh riêng cho từng hoá đơn — quét là đúng số tiền và đúng nội dung.</figcaption>
</figure>

<h2>Lưu ý quan trọng</h2>
<ul>
  <li><strong>Dùng mã QR trong app</strong> thay vì tự nhập nội dung chuyển khoản: nội dung sai là hệ thống không tự đối chiếu được.</li>
  <li>Hoá đơn điện tử lưu trong mục Thanh toán, tải lại được bất cứ lúc nào.</li>
  <li>App nhắc hạn trước 3 ngày qua thông báo đẩy.</li>
</ul>

<h3>Tiền đã trừ mà app chưa ghi nhận?</h3>
<ol>
  <li>Kéo xuống để tải lại mục Thanh toán.</li>
  <li>Quá 2 giờ làm việc vẫn chưa thấy, gửi phản ánh kèm ảnh biên lai chuyển khoản.</li>
  <li>Ban quản lý đối chiếu sổ thu chi và cập nhật trong ngày làm việc.</li>
</ol>
HTML;
    }

    private static function bodyBookAmenity(): string
    {
        $img = DemoImage::url('amenity,pool', 'guide-amen-1');

        return <<<HTML
<p>Tiện ích nội khu đặt trước trong app theo suất giờ. Đặt trước giúp bạn không tới rồi phải chờ, và giúp ban quản lý giới hạn số người cùng lúc.</p>

<figure>
  <img src="{$img}" alt="Bể bơi nội khu" />
  <figcaption>Bể bơi và phòng gym là hai tiện ích được đặt nhiều nhất vào 18:00–20:00.</figcaption>
</figure>

<h2>Quy tắc chung</h2>
<ul>
  <li>Mỗi căn hộ đặt tối đa <strong>2 suất/ngày</strong> cho cùng một tiện ích.</li>
  <li>Đặt trước tối đa 7 ngày.</li>
  <li>Huỷ trước giờ bắt đầu <strong>2 giờ</strong> thì không mất phí; huỷ muộn hơn tính như đã dùng.</li>
  <li>Đến muộn quá 15 phút, suất tự huỷ để nhường người khác.</li>
</ul>

<h2>Phí theo tiện ích</h2>
<table>
  <tr><th>Tiện ích</th><th>Suất</th><th>Phí</th></tr>
  <tr><td>Bể bơi</td><td>90 phút</td><td>Miễn phí</td></tr>
  <tr><td>Phòng gym</td><td>90 phút</td><td>Miễn phí</td></tr>
  <tr><td>Sân tennis</td><td>60 phút</td><td>Có phí</td></tr>
  <tr><td>Phòng sinh hoạt cộng đồng</td><td>Nửa ngày</td><td>Có phí + đặt cọc</td></tr>
</table>

<h3>Ba lỗi thường gặp</h3>
<ol>
  <li>Đặt hộ người ngoài toà — tiện ích chỉ dành cho cư dân và khách đi kèm.</li>
  <li>Quên mang thẻ cư dân, bảo vệ không có căn cứ đối chiếu suất đã đặt.</li>
  <li>Đặt phòng sinh hoạt cộng đồng mà không nêu mục đích, ban quản lý phải hỏi lại nên duyệt chậm.</li>
</ol>
HTML;
    }

    private static function bodyVisitor(): string
    {
        $img = DemoImage::url('community', 'guide-visit-1');

        return <<<HTML
<p>Khách tới thăm được vào bằng <strong>mã khách</strong> bạn tạo trong app. Bảo vệ đối chiếu mã, không cần bạn xuống tận sảnh.</p>

<h2>Tạo mã khách</h2>
<ol>
  <li>Vào mục <em>Khách thăm</em>, bấm "Tạo mã".</li>
  <li>Nhập tên khách, số điện thoại và khung giờ dự kiến.</li>
  <li>Gửi mã cho khách qua tin nhắn — mã có hiệu lực <strong>trong ngày</strong>.</li>
</ol>

<figure>
  <img src="{$img}" alt="Sảnh lễ tân toà nhà" />
  <figcaption>Khách xuất trình mã tại sảnh; bảo vệ ghi nhận vào và ra.</figcaption>
</figure>

<h2>Gửi xe cho khách</h2>
<ul>
  <li>Xe máy: gửi tại tầng hầm B1, tính phí theo lượt.</li>
  <li>Ô tô: cần đăng ký biển số khi tạo mã, chỗ đỗ khách có hạn nên đăng ký sớm.</li>
</ul>

<h2>Nhận hàng khi không có nhà</h2>
<p>Chọn "Gửi tại lễ tân" khi tạo mã cho shipper. Hàng lưu tại tủ giao nhận và app gửi thông báo kèm mã tủ.</p>

<blockquote>Hàng dễ hỏng (thực phẩm tươi, thuốc) lễ tân không nhận giữ — hãy hẹn giờ có người ở nhà.</blockquote>
HTML;
    }

    private static function bodyFeedback(): string
    {
        return <<<HTML
<p>Phản ánh được chuyển thẳng tới bộ phận phụ trách. Mô tả đúng ngay từ đầu là cách nhanh nhất để được xử lý — không phải gửi nhiều lần.</p>

<h2>Chọn đúng hạng mục</h2>
<ul>
  <li><strong>Kỹ thuật</strong>: điện, nước, thang máy, điều hoà.</li>
  <li><strong>Vệ sinh</strong>: rác, mùi, hành lang, khu chung.</li>
  <li><strong>An ninh</strong>: người lạ, mất đồ, tiếng ồn ban đêm.</li>
  <li><strong>Hành chính</strong>: hoá đơn, hợp đồng, thông tin cư dân.</li>
</ul>

<h2>Mô tả thế nào là đủ</h2>
<ol>
  <li>Vị trí chính xác: toà, tầng, số căn hoặc mốc dễ nhận (thang máy số 2, hành lang cạnh phòng rác).</li>
  <li>Thời điểm xảy ra và tần suất (một lần hay lặp lại mỗi tối).</li>
  <li>Ảnh hoặc video ngắn — với sự cố nghe được (tiếng ồn, tiếng rung) thì video hữu ích hơn ảnh.</li>
</ol>

<h2>Theo dõi tiến độ</h2>
<p>Mỗi phản ánh có trạng thái: <em>Đã nhận → Đang xử lý → Hoàn tất</em>. Bạn nhận thông báo mỗi lần đổi trạng thái và có thể bình luận thêm ngay trong phản ánh đó.</p>

<blockquote>Sự cố nguy hiểm ngay lập tức (cháy, rò gas, ngập) hãy GỌI trực tiếp số khẩn cấp, đừng chỉ gửi phản ánh.</blockquote>
HTML;
    }

    private static function bodySafety(): string
    {
        $img = DemoImage::url('building', 'guide-safe-1');

        return <<<HTML
<p>Bài này ngắn nhưng nên đọc một lần và ghi nhớ. Các tình huống dưới đây đều xảy ra ở chung cư và cách xử lý không hiển nhiên.</p>

<h2>Khi có báo cháy</h2>
<ol>
  <li><strong>Không dùng thang máy.</strong> Đi thang bộ thoát hiểm.</li>
  <li>Đi sát tường, cúi thấp người, che mũi miệng bằng khăn ẩm.</li>
  <li>Tập trung tại điểm an toàn ngoài sân, chờ điểm danh theo tầng.</li>
</ol>

<figure>
  <img src="{$img}" alt="Hành lang thoát hiểm toà nhà" />
  <figcaption>Hãy đi thử một lần để biết cầu thang thoát hiểm gần căn hộ mình ở đâu.</figcaption>
</figure>

<h2>Kẹt thang máy</h2>
<ul>
  <li>Bấm nút gọi cứu hộ trong cabin và giữ liên lạc.</li>
  <li><strong>Không</strong> tự cạy cửa hay trèo qua nóc cabin.</li>
  <li>Đội kỹ thuật trực 24/7, thời gian tới thường dưới 10 phút.</li>
</ul>

<h2>Rò rỉ nước</h2>
<ol>
  <li>Đóng van tổng của căn hộ (thường trong hộp kỹ thuật cạnh nhà vệ sinh).</li>
  <li>Gửi phản ánh hạng mục Kỹ thuật kèm ảnh.</li>
  <li>Nước tràn sang căn dưới thì gọi ngay, đừng chờ — thiệt hại tăng theo từng phút.</li>
</ol>

<h2>Ba việc nên làm trước khi cần</h2>
<ul>
  <li>Nhớ vị trí <strong>hai</strong> cầu thang thoát hiểm gần nhất.</li>
  <li>Biết chỗ van nước tổng và tủ điện của căn hộ.</li>
  <li>Lưu số khẩn cấp của ban quản lý vào danh bạ điện thoại.</li>
</ul>
HTML;
    }
}
