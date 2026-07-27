<?php

namespace App\Services\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Sinh bản dẫn xuất cho ảnh cư dân tải lên (đề xuất
 * `x2mobile/docs/IMAGE_PIPELINE_PROPOSAL_20260727.md`, tầng 2).
 *
 * Dùng **GD thuần**, KHÔNG thêm thư viện: GD + exif đã có sẵn trên Herd và trên
 * mọi máy chủ PHP tiêu chuẩn. Thêm `intervention/image` sẽ kéo theo bước
 * `composer install` lúc deploy mà không đổi lại được gì đáng kể ở mức này —
 * và trên Windows còn vướng `ext-pcntl` của Horizon.
 *
 * Ba biến thể:
 *  - `thumb`    320px  → lưới nhiều ảnh, ảnh trong bình luận
 *  - `feed`    1080px  → ảnh đơn trên bảng tin
 *  - `original` 2048px → xem toàn màn hình (vẫn cắp trần, ảnh gốc 12MP là thừa)
 *
 * Xuất WebP (nhỏ hơn JPEG ~25–30%); máy nào không giải được WebP thì app rơi
 * về `url` gốc vẫn còn nguyên trên đĩa.
 */
class ImageVariantService
{
    /** Cạnh dài tối đa mỗi biến thể. */
    public const SIZES = ['thumb' => 320, 'feed' => 1080, 'original' => 2048];

    private const WEBP_QUALITY = 82;

    /** Ảnh lớn hơn mức này thì GD dễ chạm memory_limit → bỏ qua, giữ bản gốc. */
    private const MAX_PIXELS = 50_000_000; // ~50MP

    /**
     * @return array{width:int,height:int,variants:array<string,string>}|null
     *         null khi không phải ảnh raster xử lý được (svg/pdf/gif động…).
     */
    public function generate(UploadedFile $file, string $disk, string $storedPath): ?array
    {
        $absolute = Storage::disk($disk)->path($storedPath);
        if (! is_file($absolute)) {
            return null;
        }

        $info = @getimagesize($absolute);
        if ($info === false) {
            return null;
        }
        [$srcW, $srcH] = $info;
        if ($srcW * $srcH > self::MAX_PIXELS) {
            return ['width' => $srcW, 'height' => $srcH, 'variants' => []];
        }

        $image = $this->read($absolute, $info[2] ?? null);
        if ($image === null) {
            return null;
        }

        // Ảnh chụp dọc bằng iPhone/Android lưu chiều thật + CỜ XOAY trong EXIF;
        // GD không tự áp cờ đó → không xoay thì ảnh nằm ngang khi hiển thị.
        $image = $this->applyExifOrientation($image, $absolute);

        $width = imagesx($image);
        $height = imagesy($image);

        $dir = trim(dirname($storedPath), '.');
        $base = Str::beforeLast(basename($storedPath), '.');
        $variants = [];

        foreach (self::SIZES as $name => $maxEdge) {
            $target = $this->resized($image, $width, $height, $maxEdge);
            $relative = ($dir !== '' ? $dir.'/' : '').$base.'_'.$name.'.webp';
            $out = Storage::disk($disk)->path($relative);
            @mkdir(dirname($out), 0775, true);

            if (@imagewebp($target, $out, self::WEBP_QUALITY)) {
                $variants[$name] = $relative;
            }
            if ($target !== $image) {
                imagedestroy($target);
            }
        }

        imagedestroy($image);

        return ['width' => $width, 'height' => $height, 'variants' => $variants];
    }

    /** @return \GdImage|null */
    private function read(string $path, ?int $type)
    {
        $image = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            IMAGETYPE_GIF => @imagecreatefromgif($path),
            default => false,
        };

        return $image === false ? null : $image;
    }

    /**
     * Xoay/lật theo cờ EXIF Orientation (1–8).
     *
     * @param  \GdImage  $image
     * @return \GdImage
     */
    private function applyExifOrientation($image, string $path)
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }
        $exif = @exif_read_data($path);
        $orientation = (int) ($exif['Orientation'] ?? 1);
        if ($orientation <= 1) {
            return $image;
        }

        $rotated = match ($orientation) {
            3, 4 => imagerotate($image, 180, 0),
            5, 6 => imagerotate($image, -90, 0),
            7, 8 => imagerotate($image, 90, 0),
            default => $image,
        };
        if ($rotated === false) {
            return $image;
        }
        if ($rotated !== $image) {
            imagedestroy($image);
        }
        // 2/4/5/7 còn kèm lật gương.
        if (in_array($orientation, [2, 4, 5, 7], true)) {
            imageflip($rotated, IMG_FLIP_HORIZONTAL);
        }

        return $rotated;
    }

    /**
     * Thu nhỏ theo cạnh dài. Ảnh đã nhỏ hơn đích thì KHÔNG phóng to — phóng chỉ
     * làm file nặng thêm mà không thêm chi tiết.
     *
     * @param  \GdImage  $image
     * @return \GdImage
     */
    private function resized($image, int $width, int $height, int $maxEdge)
    {
        $longest = max($width, $height);
        if ($longest <= $maxEdge) {
            return $image;
        }

        $scale = $maxEdge / $longest;
        $w = max(1, (int) round($width * $scale));
        $h = max(1, (int) round($height * $scale));

        $out = imagecreatetruecolor($w, $h);
        imagealphablending($out, false);
        imagesavealpha($out, true);
        imagecopyresampled($out, $image, 0, 0, 0, 0, $w, $h, $width, $height);

        return $out;
    }
}
