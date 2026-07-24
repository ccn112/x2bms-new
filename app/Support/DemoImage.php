<?php

namespace App\Support;

/**
 * Ảnh demo cho app cư dân — trả URL ảnh THẬT theo chủ đề (keyword), ổn định
 * theo id (mỗi entity luôn ra cùng 1 ảnh nhờ `lock`). Dùng khi cột ảnh của
 * bản ghi còn trống, để màn hình app "giàu hình ảnh" khi demo.
 *
 * Nguồn: loremflickr (ảnh Flickr theo keyword, không cần API key).
 */
class DemoImage
{
    /** URL ảnh thật theo chủ đề, ổn định theo id (lock). Không cần key. */
    public static function url(string $keywords, int|string $id, int $w = 800, int $h = 600): string
    {
        $kw = rawurlencode(trim($keywords));

        return "https://loremflickr.com/{$w}/{$h}/{$kw}?lock=".(int) crc32((string) $id);
    }
}
