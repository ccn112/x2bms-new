<?php

namespace App\Support;

/**
 * Ảnh demo cho app cư dân — trả URL ảnh THẬT, ổn định theo (keywords, id): mỗi
 * entity luôn ra cùng 1 ảnh. Dùng khi cột ảnh của bản ghi còn trống, để màn hình
 * app "giàu hình ảnh" khi demo.
 *
 * Nguồn: **picsum.photos** (Lorem Picsum) — ổn định, nhanh, không cần API key,
 * KHÔNG chập chờn như loremflickr (đã gặp HTTP 500 xen kẽ). Ảnh chọn theo `seed`
 * (deterministic) nên không "nhảy" mỗi lần tải. `keywords` giữ trong seed để mỗi
 * chủ đề ra một dải ảnh riêng, ổn định.
 *
 * (Nếu muốn ảnh ĐÚNG chủ đề tuyệt đối — hồ bơi/gym/nội thất… — thay bằng bộ ảnh
 * Unsplash curated hoặc ảnh upload thật; chỉ sửa hàm này, mọi Resource đi qua đây.)
 */
class DemoImage
{
    /** URL ảnh thật ổn định theo (keywords,id). Không cần key. */
    public static function url(string $keywords, int|string $id, int $w = 800, int $h = 600): string
    {
        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($keywords)));
        $slug = trim((string) $slug, '-');
        $seed = ($slug !== '' ? $slug : 'x2') . '-' . $id;

        return "https://picsum.photos/seed/{$seed}/{$w}/{$h}";
    }
}
