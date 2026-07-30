<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Bóc metadata của một liên kết để app dựng thẻ xem trước khi cư dân dán link
 * vào bài viết.
 *
 * Vì sao phải ở SERVER chứ không fetch từ app: bản web bị CORS chặn đọc HTML của
 * domain khác, và trên mobile thì tải cả trang HTML chỉ để lấy 3 thẻ meta là tốn
 * 4G của cư dân. Server tải một lần rồi cache cho mọi người.
 *
 * ⚠️ Đây là endpoint **server tự đi gọi URL do người dùng nhập** (SSRF). Các
 * chốt bắt buộc, đừng bỏ khi sửa sau này:
 *   - chỉ http/https,
 *   - chặn host nội bộ (localhost, IP private, `.test`, `.local`),
 *   - giới hạn thời gian + kích thước tải,
 *   - KHÔNG theo redirect quá 3 lần (redirect là lối lách whitelist).
 */
class LinkPreviewController extends ApiController
{
    /** Trần dung lượng HTML đọc vào — thẻ meta luôn nằm ở đầu tài liệu. */
    private const MAX_BYTES = 512 * 1024;

    private const TIMEOUT_SECONDS = 6;

    private const CACHE_TTL_MINUTES = 60 * 24;

    /** POST /resident/link-preview  { url } */
    public function show(Request $request): JsonResponse
    {
        $data = $request->validate([
            'url' => ['required', 'string', 'max:2048'],
        ]);

        $url = trim($data['url']);
        $parsed = $this->safeUrl($url);
        if ($parsed === null) {
            return ApiResponse::error('invalid_url',
                'Liên kết không hợp lệ hoặc không được phép.', 422);
        }

        // Cache theo URL đã chuẩn hoá: một link được dán nhiều lần trong toà chỉ
        // tốn một lượt tải.
        $payload = Cache::remember(
            'link_preview:'.sha1($parsed),
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn () => $this->fetch($parsed),
        );

        // Không bóc được gì thì vẫn trả 200 kèm host: app vẽ thẻ tối giản (chỉ
        // domain) thay vì báo lỗi — dán link vào bài vẫn phải đăng được.
        return ApiResponse::success($payload);
    }

    /**
     * Chuẩn hoá + kiểm an toàn. Trả null nếu không được phép.
     */
    private function safeUrl(string $url): ?string
    {
        $u = parse_url($url);
        if ($u === false || empty($u['scheme']) || empty($u['host'])) {
            return null;
        }
        if (! in_array(strtolower($u['scheme']), ['http', 'https'], true)) {
            return null;
        }

        $host = strtolower($u['host']);

        // Chặn hạ tầng nội bộ: server này gọi được cả `x2bms.test` và metadata
        // endpoint của cloud, nên không chặn là mở đường đọc nội bộ.
        if (in_array($host, ['localhost', '127.0.0.1', '::1', '0.0.0.0'], true)) {
            return null;
        }
        if (Str::endsWith($host, ['.test', '.local', '.localhost', '.internal'])) {
            return null;
        }
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            // IP thật thì chỉ cho IP public.
            if (! filter_var($host, FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return null;
            }
        }

        return $url;
    }

    /**
     * @return array<string,mixed>
     */
    private function fetch(string $url): array
    {
        $host = parse_url($url, PHP_URL_HOST) ?: '';
        $fallback = [
            'url' => $url,
            'host' => $host,
            'kind' => $this->kindOf($url, $host),
            'title' => null,
            'description' => null,
            'image_url' => null,
        ];

        try {
            $res = Http::timeout(self::TIMEOUT_SECONDS)
                ->withoutVerifying()
                ->withHeaders([
                    // Nhiều site trả trang rút gọn cho UA lạ; nói thật mình là bot.
                    'User-Agent' => 'X2BMS-LinkPreview/1.0 (+https://x2.fino.vn)',
                    'Accept' => 'text/html,application/xhtml+xml',
                ])
                ->maxRedirects(3)
                ->get($url);
        } catch (\Throwable) {
            return $fallback;
        }

        if (! $res->successful()) {
            return $fallback;
        }

        $html = substr($res->body(), 0, self::MAX_BYTES);

        return [
            'url' => $url,
            'host' => $host,
            'kind' => $fallback['kind'],
            'title' => $this->meta($html, ['og:title', 'twitter:title'])
                ?? $this->titleTag($html),
            'description' => $this->meta($html, ['og:description', 'twitter:description', 'description']),
            'image_url' => $this->absolute(
                $this->meta($html, ['og:image', 'twitter:image', 'twitter:image:src']),
                $url,
            ),
        ];
    }

    /**
     * Nhận diện loại link để app vẽ đúng biểu tượng. `map` tách riêng vì Google
     * Maps thường KHÔNG có `og:image`, nên app phải biết để không hiện thẻ trống.
     */
    private function kindOf(string $url, string $host): string
    {
        $h = strtolower($host);
        if (Str::contains($h, ['google.com', 'goo.gl', 'maps.app.goo.gl']) &&
            (Str::contains(strtolower($url), ['/maps', 'maps.app']) || Str::contains($h, 'maps.'))) {
            return 'map';
        }
        if (Str::contains($h, ['youtube.com', 'youtu.be'])) {
            return 'video';
        }

        return 'link';
    }

    /** @param array<int,string> $names */
    private function meta(string $html, array $names): ?string
    {
        foreach ($names as $name) {
            $q = preg_quote($name, '/');
            // Thuộc tính có thể đứng trước hoặc sau `content` — thử cả hai chiều.
            foreach ([
                '/<meta[^>]+(?:property|name)\s*=\s*["\']'.$q.'["\'][^>]*content\s*=\s*["\']([^"\']+)["\']/i',
                '/<meta[^>]+content\s*=\s*["\']([^"\']+)["\'][^>]*(?:property|name)\s*=\s*["\']'.$q.'["\']/i',
            ] as $pattern) {
                if (preg_match($pattern, $html, $m) === 1) {
                    $v = trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                    if ($v !== '') {
                        return Str::limit($v, 300, '');
                    }
                }
            }
        }

        return null;
    }

    private function titleTag(string $html): ?string
    {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $m) === 1) {
            $v = trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            return $v === '' ? null : Str::limit($v, 300, '');
        }

        return null;
    }

    /** og:image có thể là đường dẫn tương đối — đưa về URL tuyệt đối. */
    private function absolute(?string $candidate, string $base): ?string
    {
        if ($candidate === null || $candidate === '') {
            return null;
        }
        if (Str::startsWith($candidate, ['http://', 'https://'])) {
            return $candidate;
        }
        $b = parse_url($base);
        if (empty($b['scheme']) || empty($b['host'])) {
            return null;
        }
        $origin = $b['scheme'].'://'.$b['host'];

        return Str::startsWith($candidate, '/')
            ? $origin.$candidate
            : $origin.'/'.$candidate;
    }
}
