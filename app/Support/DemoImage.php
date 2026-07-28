<?php

namespace App\Support;

/**
 * Ảnh demo cho app cư dân — trả URL ảnh THẬT, ổn định theo (keywords, id): mỗi
 * entity luôn ra cùng 1 ảnh. Dùng khi cột ảnh của bản ghi còn trống, để màn hình
 * app "giàu hình ảnh" khi demo.
 *
 * ⚠️ TRƯỚC ĐÂY dùng picsum.photos với `seed = keywords-id`. Vấn đề: **picsum
 * hoàn toàn bỏ qua từ khoá** — seed chỉ chọn một ảnh ngẫu nhiên trong kho. Kết
 * quả là thẻ "dự án bất động sản" hiện ảnh nhà thờ, bãi biển… lệch hẳn thiết kế.
 *
 * NAY dùng **bộ ảnh Unsplash tuyển sẵn theo CHỦ ĐỀ** (URL trực tiếp, không cần
 * API key, đã kiểm HTTP 200). Chọn ảnh theo hash của id nên vẫn ổn định: một
 * entity luôn ra đúng một ảnh, và ảnh luôn đúng chủ đề.
 */
class DemoImage
{
    /**
     * Chủ đề → danh sách photo id Unsplash. Từ khoá truyền vào được dò theo
     * chuỗi con nên `'building,residential,skyline'` khớp chủ đề `building`.
     *
     * @var array<string,array<int,string>>
     */
    private const TOPICS = [
        // Toà nhà / dự án bất động sản — CHỈ ảnh ngoại thất cao tầng (đã soi
        // từng ảnh). Bộ đầu lẫn nội thất, mô hình nhà đồ chơi, nhà vườn thấp
        // tầng nên thẻ "dự án" nhìn sai hẳn so với thiết kế.
        'building' => [
            'photo-1545324418-cc1a3fa10c00', // chung cư nhìn lên
            'photo-1486406146926-c627a92ad1ab', // cao ốc kính nhìn lên
            'photo-1470723710355-95304d8aece4', // skyline đêm
            'photo-1480714378408-67cf0d13bc1b', // skyline hoàng hôn
        ],
        // Tiện ích nội khu: bể bơi, gym, spa
        'amenity' => [
            'photo-1571902943202-507ec2618e8f',
            'photo-1534438327276-14e5300c3a48',
            'photo-1540555700478-4be289fbecef',
            'photo-1512917774080-9991f1c4c750', // nhà có bể bơi
        ],
        // Cộng đồng / hàng xóm / sự kiện cư dân
        'community' => [
            'photo-1517457373958-b7bdd4587205',
            'photo-1523712999610-f77fbcfc3843',
            'photo-1511795409834-ef04bbd61622',
        ],
        // Ưu đãi / ẩm thực / mua sắm
        'offer' => [
            'photo-1555396273-367ea4eb4db5',
            'photo-1600880292203-757bb62b4baf',
        ],
    ];

    /** Chủ đề dùng khi từ khoá không khớp bộ nào. */
    private const FALLBACK = 'building';

    /** URL ảnh thật, đúng chủ đề, ổn định theo (keywords,id). Không cần key. */
    public static function url(string $keywords, int|string $id, int $w = 800, int $h = 600): string
    {
        $photos = self::TOPICS[self::topicFor($keywords)] ?? self::TOPICS[self::FALLBACK];

        // crc32 để phân bố đều; cùng id luôn ra cùng ảnh.
        $photo = $photos[crc32((string) $id) % count($photos)];

        return "https://images.unsplash.com/{$photo}?auto=format&fit=crop&w={$w}&h={$h}&q=70";
    }

    /** Dò chủ đề theo chuỗi con trong danh sách từ khoá. */
    private static function topicFor(string $keywords): string
    {
        $k = strtolower($keywords);
        foreach (array_keys(self::TOPICS) as $topic) {
            if (str_contains($k, $topic)) {
                return $topic;
            }
        }
        // Vài từ khoá cũ đang dùng ở các Resource khác nhau.
        return match (true) {
            str_contains($k, 'apartment'), str_contains($k, 'skyline') => 'building',
            str_contains($k, 'neighbor'), str_contains($k, 'event') => 'community',
            str_contains($k, 'pool'), str_contains($k, 'gym'), str_contains($k, 'spa') => 'amenity',
            str_contains($k, 'voucher'), str_contains($k, 'food') => 'offer',
            default => self::FALLBACK,
        };
    }
}
