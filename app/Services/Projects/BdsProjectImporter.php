<?php

namespace App\Services\Projects;

use App\Models\BdsImportState;
use App\Models\PublicProject;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

/**
 * Thu thập + chuẩn hoá metadata dự án từ batdongsan.com.vn.
 *
 * - Logic CHUẨN HOÁ (code, configs, developer, province, projectType, status)
 *   dùng chung cho cả PublicProjectBdsSeeder (đọc JSON) lẫn fetchMore() (fetch live).
 * - fetchMore(): với mỗi khu vực, đọc con trỏ trang (BdsImportState) rồi lấy N trang kế
 *   tiếp, parse card, upsert public_projects theo `code`, cập nhật con trỏ.
 *
 * CHỐNG BOT: batdongsan sau Cloudflare managed challenge (chặn theo TLS/JA3).
 *  - Guzzle/ext-curl (OpenSSL) -> 403 challenge.
 *  - curl binary Schannel (Windows/Git) -> 200 OK.
 * fetchHtml() thử Http trước, nếu bị chặn thì fallback shell curl. Bị chặn cả hai =>
 * stoppedReason 'blocked' (không bịa dữ liệu).
 */
class BdsProjectImporter
{
    // ---------------------------------------------------------------------
    // FETCH LIVE
    // ---------------------------------------------------------------------

    /**
     * Lấy tiếp `pages` trang cho mỗi khu vực trong `$cityKeys`.
     *
     * @param  array<int,string>  $cityKeys  key trong config('bds.cities')
     * @return array<string,array{label:string,added:int,updated:int,pagesFetched:int,stoppedReason:?string}>
     */
    public function fetchMore(array $cityKeys, int $pages): array
    {
        $cities  = config('bds.cities', []);
        $delay   = (int) config('bds.delay_ms', 400);
        $results = [];

        foreach ($cityKeys as $key) {
            if (! isset($cities[$key])) {
                continue;
            }
            $city  = $cities[$key];
            $label = $city['label'] ?? $key;

            $state     = BdsImportState::firstOrCreate(['city' => $key], ['last_page' => 0]);
            $startPage = ((int) $state->last_page) + 1;

            $added = 0;
            $updated = 0;
            $pagesFetched = 0;
            $stoppedReason = null;

            for ($p = $startPage; $p < $startPage + $pages; $p++) {
                $url = $this->pageUrl($city['slug'], $p);
                $res = $this->fetchHtml($url);

                // Fallback slug cho Phú Quốc (nếu trang đầu rỗng/không tồn tại).
                if (($res['blocked'] || $res['status'] !== 200 || $this->countCards($res['body']) === 0)
                    && $p === $startPage && ! empty($city['slug_fallback'])) {
                    $alt = $this->fetchHtml($this->pageUrl($city['slug_fallback'], $p));
                    if (! $alt['blocked'] && $alt['status'] === 200 && $this->countCards($alt['body']) > 0) {
                        $res = $alt;
                        $city['slug'] = $city['slug_fallback'];
                    }
                }

                if ($res['blocked']) {
                    $stoppedReason = 'blocked';
                    break;
                }
                if ($res['status'] !== 200) {
                    $stoppedReason = 'http_'.$res['status'];
                    break;
                }

                $cards = $this->parseCards($res['body']);
                if (count($cards) === 0) {
                    $stoppedReason = 'empty';
                    break;
                }

                foreach ($cards as $card) {
                    $card['city'] = $key;
                    [$isNew] = $this->upsertCard($card, $city);
                    $isNew ? $added++ : $updated++;
                }

                $pagesFetched++;
                $state->update(['last_page' => $p, 'last_status' => 'ok', 'last_run_at' => now()]);

                if ($delay > 0) {
                    usleep($delay * 1000);
                }
            }

            if ($stoppedReason && $stoppedReason !== 'empty') {
                $state->update(['last_status' => $stoppedReason, 'last_run_at' => now()]);
            }

            $results[$key] = [
                'label'         => $label,
                'added'         => $added,
                'updated'       => $updated,
                'pagesFetched'  => $pagesFetched,
                'stoppedReason' => $stoppedReason,
            ];
        }

        return $results;
    }

    private function pageUrl(string $slug, int $page): string
    {
        $base = rtrim((string) config('bds.base_url', 'https://batdongsan.com.vn/'), '/');

        return $page <= 1 ? "$base/$slug" : "$base/$slug/p$page";
    }

    /**
     * Trả về ['status'=>int, 'body'=>string, 'blocked'=>bool].
     * Thử Http (Guzzle) trước; nếu bị Cloudflare chặn thì fallback curl binary (Schannel).
     */
    public function fetchHtml(string $url): array
    {
        $transport = config('bds.transport', 'auto');

        if ($transport !== 'curl') {
            $res = $this->fetchViaHttp($url);
            if (! $res['blocked'] || $transport === 'http') {
                return $res;
            }
        }

        // transport = curl, hoặc auto + Http bị chặn.
        return $this->fetchViaCurl($url);
    }

    private function fetchViaHttp(string $url): array
    {
        try {
            $resp = Http::withHeaders([
                'User-Agent'      => config('bds.user_agent'),
                'Accept-Language' => config('bds.accept_language', 'vi'),
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ])->timeout((int) config('bds.timeout', 30))->get($url);

            $body = $resp->body();

            return [
                'status'  => $resp->status(),
                'body'    => $body,
                'blocked' => $this->looksBlocked($resp->status(), $body),
            ];
        } catch (\Throwable $e) {
            return ['status' => 0, 'body' => '', 'blocked' => true];
        }
    }

    private function fetchViaCurl(string $url): array
    {
        $bin = (string) config('bds.curl_binary', 'curl');

        try {
            $result = Process::timeout((int) config('bds.timeout', 30) + 10)->run([
                $bin, '-sS', '-L',
                '--compressed',
                '-A', (string) config('bds.user_agent'),
                '-H', 'Accept-Language: '.config('bds.accept_language', 'vi'),
                '-w', "\n__HTTP_STATUS__%{http_code}",
                $url,
            ]);

            $out    = $result->output();
            $status = 0;
            if (preg_match('/__HTTP_STATUS__(\d+)\s*$/', $out, $m)) {
                $status = (int) $m[1];
                $out = preg_replace('/\n?__HTTP_STATUS__\d+\s*$/', '', $out);
            }

            return [
                'status'  => $status,
                'body'    => $out,
                'blocked' => $this->looksBlocked($status, $out),
            ];
        } catch (\Throwable $e) {
            return ['status' => 0, 'body' => '', 'blocked' => true];
        }
    }

    private function looksBlocked(int $status, string $body): bool
    {
        if ($status === 403 || $status === 429 || $status === 503) {
            return true;
        }
        if ($body === '') {
            return true;
        }
        // Trang Cloudflare challenge (không có card thật).
        if ($this->countCards($body) === 0
            && (Str::contains($body, ['challenge-platform', '_cf_chl_opt', 'Just a moment', 'cf-chl'])
                || strlen($body) < 20000)) {
            return true;
        }

        return false;
    }

    private function countCards(string $body): int
    {
        return substr_count($body, 'js__project-card');
    }

    // ---------------------------------------------------------------------
    // PARSE HTML
    // ---------------------------------------------------------------------

    /**
     * Bóc các card dự án từ HTML trang danh sách batdongsan.
     * Dùng DOMDocument/DOMXPath (built-in) — không cần thêm package.
     *
     * @return array<int,array{name:string,url:string,img:?string,status:?string,configs:array<int,string>,location:?string,summary:?string}>
     */
    public function parseCards(string $html): array
    {
        if (trim($html) === '') {
            return [];
        }

        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?>'.$html);
        libxml_clear_errors();
        $xp = new DOMXPath($doc);

        $cards = $xp->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' js__project-card ')]");
        $out = [];

        foreach ($cards as $card) {
            if (! $card instanceof DOMElement) {
                continue;
            }

            $name = $this->firstText($xp, $card, 're__prj-card-title');

            $href = null;
            $anchors = $xp->query('.//a[@href]', $card);
            foreach ($anchors as $a) {
                $h = $a->getAttribute('href');
                if ($h !== '' && $h !== '#') {
                    $href = $h;
                    if (preg_match('/pj\d+/i', $h)) {
                        break; // ưu tiên link chứa pj<id>
                    }
                }
            }

            $img = null;
            $imgs = $xp->query('.//img[@data-src]', $card);
            if ($imgs->length > 0 && $imgs->item(0) instanceof DOMElement) {
                $img = $imgs->item(0)->getAttribute('data-src') ?: null;
            }
            if (! $img) {
                $imgs = $xp->query('.//img[@src]', $card);
                if ($imgs->length > 0 && $imgs->item(0) instanceof DOMElement) {
                    $src = $imgs->item(0)->getAttribute('src');
                    $img = ($src && ! Str::startsWith($src, 'data:')) ? $src : null;
                }
            }

            $configs = [];
            foreach ($xp->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' re__prj-card-config-value ')]", $card) as $c) {
                $v = trim(preg_replace('/\s+/u', ' ', $c->textContent));
                if ($v !== '') {
                    $configs[] = $v;
                }
            }

            if ($name === null || $name === '') {
                continue;
            }

            $out[] = [
                'name'     => $name,
                'url'      => $href ?? '',
                'img'      => $img,
                'status'   => $this->firstText($xp, $card, 're__prj-tag-info'),
                'configs'  => $configs,
                'location' => $this->firstText($xp, $card, 're__prj-card-location'),
                'summary'  => $this->firstText($xp, $card, 're__prj-card-summary'),
            ];
        }

        return $out;
    }

    private function firstText(DOMXPath $xp, DOMElement $ctx, string $class): ?string
    {
        $nodes = $xp->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' $class ')]", $ctx);
        if ($nodes->length === 0) {
            return null;
        }
        $t = trim(preg_replace('/\s+/u', ' ', $nodes->item(0)->textContent));

        return $t === '' ? null : $t;
    }

    /**
     * Upsert 1 card vào public_projects theo code. Trả [isNew(bool), PublicProject].
     *
     * @param  array<string,mixed>  $card
     * @param  array<string,mixed>  $cityCfg
     * @return array{0:bool,1:PublicProject}
     */
    public function upsertCard(array $card, array $cityCfg = []): array
    {
        $url  = (string) ($card['url'] ?? '');
        $code = static::codeFrom($url, (string) ($card['name'] ?? ''));
        [$apartments, $blocks, $area] = static::parseConfigs($card['configs'] ?? []);

        $location = $card['location'] ?? null;
        $province = static::province((string) ($location ?? '')) ?? ($cityCfg['province'] ?? null);

        $existing = PublicProject::where('code', $code)->exists();

        $model = PublicProject::updateOrCreate(
            ['code' => $code],
            [
                'name'           => $card['name'],
                'developer_name' => static::developer($card),
                'address'        => $location,
                'province'       => $province,
                'project_type'   => static::projectType($url),
                'status'         => static::status((string) ($card['status'] ?? '')),
                'blocks'         => $blocks,
                'apartments'     => $apartments,
                'description'    => $card['summary'] ?? null,
                'is_public'      => true,
                'metadata_json'  => [
                    'source'      => 'batdongsan.com.vn',
                    'city'        => $card['city'] ?? null,
                    'source_url'  => $url ? 'https://batdongsan.com.vn'.$url : null,
                    'image'       => $card['img'] ?? null,
                    'area'        => $area,
                    'configs_raw' => $card['configs'] ?? [],
                    'status_raw'  => $card['status'] ?? null,
                    'imported_at' => now()->toDateString(),
                ],
            ],
        );

        return [! $existing, $model];
    }

    // ---------------------------------------------------------------------
    // CHUẨN HOÁ (dùng chung với PublicProjectBdsSeeder)
    // ---------------------------------------------------------------------

    public static function codeFrom(string $url, string $name): string
    {
        if (preg_match('/pj(\d+)/i', $url, $m)) {
            return 'BDS-PJ'.$m[1];
        }

        return 'BDS-'.Str::upper(Str::slug($name));
    }

    /**
     * Trả [apartments, blocks, area_string|null].
     *
     * @param  array<int,mixed>  $configs
     * @return array{0:int,1:int,2:?string}
     */
    public static function parseConfigs(array $configs): array
    {
        $area = null;
        $nums = [];
        foreach ($configs as $c) {
            $c = trim((string) $c);
            if ($c === '') {
                continue;
            }
            if (preg_match('/(ha|m²|m2)/iu', $c)) {
                $area = $c;

                continue;
            }
            $int = (int) preg_replace('/\D/', '', $c);
            if ($int > 0) {
                $nums[] = $int;
            }
        }

        return [$nums[0] ?? 0, $nums[1] ?? 0, $area];
    }

    /** @param  array<string,mixed>  $r */
    public static function developer(array $r): ?string
    {
        if (! empty($r['developer'])) {
            return static::tidy((string) $r['developer']);
        }
        $s = (string) ($r['summary'] ?? '');
        foreach ([
            '/do (.+?) làm chủ đầu tư/iu',
            '/do (.+?) (?:phát triển|làm đơn vị phát triển)/iu',
            '/Chủ đầu tư (.+?)(?: tiếp tục| triển khai| tọa lạc|,|\.)/iu',
        ] as $re) {
            if (preg_match($re, $s, $m)) {
                return static::tidy($m[1]);
            }
        }

        return null;
    }

    public static function tidy(string $s): string
    {
        return Str::limit(trim(preg_replace('/\s+/u', ' ', $s)), 250, '');
    }

    public static function province(string $location): ?string
    {
        if ($location === '') {
            return null;
        }
        $parts = array_map('trim', explode(',', $location));

        return end($parts) ?: null;
    }

    public static function projectType(string $url): ?string
    {
        $map = [
            'can-ho-chung-cu' => 'Căn hộ chung cư',
            'khu-do-thi'      => 'Khu đô thị',
            'nha-o-xa-hoi'    => 'Nhà ở xã hội',
            'shophouse'       => 'Shophouse / Nhà phố',
            'khu-phuc-hop'    => 'Khu phức hợp',
            'biet-thu'        => 'Biệt thự / Liền kề',
        ];
        foreach ($map as $key => $label) {
            if (str_contains($url, $key)) {
                return $label;
            }
        }

        return null;
    }

    public static function status(string $raw): string
    {
        $raw = mb_strtolower($raw, 'UTF-8');

        return match (true) {
            str_contains($raw, 'bàn giao')    => 'handover',
            str_contains($raw, 'đang mở bán') => 'selling',
            str_contains($raw, 'sắp mở bán')  => 'planning',
            default                            => 'planning',
        };
    }
}
