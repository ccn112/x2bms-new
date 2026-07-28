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
     * @param  ?bool  $enrich  làm giàu từ trang chi tiết (null = theo config bds.enrich_detail)
     * @return array<string,array{label:string,added:int,updated:int,pagesFetched:int,stoppedReason:?string}>
     */
    public function fetchMore(array $cityKeys, int $pages, ?bool $enrich = null): array
    {
        $cities  = config('bds.cities', []);
        $delay   = (int) config('bds.delay_ms', 400);
        $enrich  = $enrich ?? (bool) config('bds.enrich_detail', true);
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
                    [$isNew, $model] = $this->upsertCard($card, $city);
                    $isNew ? $added++ : $updated++;

                    // Làm giàu từ trang chi tiết cho dự án MỚI hoặc CHƯA có detail.
                    if ($enrich && ($isNew || empty($model->metadata_json['detail']))) {
                        $this->enrichDetail($model);
                        if ($delay > 0) {
                            usleep($delay * 1000);
                        }
                    }
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
        // Trang Cloudflare challenge THẬT: ngắn + chứa token challenge.
        // (Lưu ý: chuỗi 'challenge-platform' xuất hiện cả trên trang HỢP LỆ — KHÔNG dùng làm dấu hiệu.
        //  Trang chi tiết hợp lệ không có card nên KHÔNG dựa vào số card để phán bị chặn.)
        if (strlen($body) < 20000
            && Str::contains($body, ['_cf_chl_opt', 'challenge-error-text', 'cf-chl-', 'Just a moment'])) {
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
        $addr = static::parseAddress((string) ($location ?? ''));
        $province = $addr['province'] ?? ($cityCfg['province'] ?? null);

        $developerName = static::developer($card);
        $developerId = $developerName
            ? optional(\App\Models\Developer::upsertByName($developerName, ['source' => 'batdongsan.com.vn']))->id
            : null;

        $prev = PublicProject::where('code', $code)->first();
        $existing = (bool) $prev;

        // Giữ lại các khoá làm giàu từ trang chi tiết (đừng ghi đè mất khi upsert lại card).
        $meta = [
            'source'      => 'batdongsan.com.vn',
            'city'        => $card['city'] ?? null,
            'source_url'  => $url ? 'https://batdongsan.com.vn'.$url : null,
            'image'       => $card['img'] ?? null,
            'cover_image' => $card['img'] ?? null, // baseline; enrichDetail sẽ nâng lên ảnh gallery
            'area'        => $area,
            'configs_raw' => $card['configs'] ?? [],
            'status_raw'  => $card['status'] ?? null,
            'imported_at' => now()->toDateString(),
        ];
        foreach (['detail', 'detail_fetched_at', 'detail_error', 'price', 'legal', 'developer_unit',
            'images', 'cover_image', 'images_watermarked'] as $k) {
            if ($prev && array_key_exists($k, (array) $prev->metadata_json)) {
                $meta[$k] = $prev->metadata_json[$k];
            }
        }

        $model = PublicProject::updateOrCreate(
            ['code' => $code],
            [
                'name'           => $card['name'],
                'developer_name' => $developerName,
                'developer_id'   => $developerId,
                'address'        => $location,
                'ward'           => $addr['ward'],
                'district'       => $addr['district'],
                'province'       => $province,
                'project_type'   => static::projectType($url),
                'status'         => static::status((string) ($card['status'] ?? '')),
                'blocks'         => $blocks,
                'apartments'     => $apartments,
                'description'    => $card['summary'] ?? null,
                'is_public'      => true,
                'metadata_json'  => $meta,
            ],
        );

        return [! $existing, $model];
    }

    // ---------------------------------------------------------------------
    // LÀM GIÀU TỪ TRANG CHI TIẾT
    // ---------------------------------------------------------------------

    /**
     * Fetch trang chi tiết dự án (theo metadata_json.source_url), parse bảng thông tin
     * (re__project-attr) + CĐT/giá/pháp lý từ mô tả & FAQ; lưu vào metadata_json['detail'].
     * Map lên cột khi có: project_type / blocks / apartments / developer_name.
     * Bỏ qua êm nếu bị chặn/empty (ghi metadata_json['detail_error']).
     */
    public function enrichDetail(PublicProject $p): bool
    {
        $meta = (array) $p->metadata_json;
        $url = $meta['source_url'] ?? null;
        if (! $url) {
            return false;
        }

        $res = $this->fetchHtml($url);
        if ($res['blocked'] || $res['status'] !== 200 || strlen($res['body']) < 20000) {
            $meta['detail_error'] = $res['blocked'] ? 'blocked' : ('http_'.$res['status']);
            $p->update(['metadata_json' => $meta]);

            return false;
        }

        $parsed = $this->parseDetail($res['body']);

        $meta['detail'] = $parsed['attrs'];
        $meta['detail_fetched_at'] = now()->toIso8601String();
        unset($meta['detail_error']);
        if (! empty($parsed['faq'])) {
            $meta['detail_faq'] = $parsed['faq'];
        }
        if ($parsed['price'] !== null) {
            $meta['price'] = $parsed['price'];
        }
        if ($parsed['legal'] !== null) {
            $meta['legal'] = $parsed['legal'];
        }
        if ($parsed['developer_unit'] !== null) {
            $meta['developer_unit'] = $parsed['developer_unit'];
        }
        // Ảnh dự án (URL, chưa tải file). Ưu tiên gallery chi tiết; giữ ảnh card làm dự phòng cover.
        if (! empty($parsed['images'])) {
            $meta['images'] = $parsed['images'];
            $meta['cover_image'] = $parsed['cover_image'] ?? ($meta['image'] ?? null);
            $meta['images_watermarked'] = $parsed['images_watermarked'];
        } elseif (empty($meta['cover_image']) && ! empty($meta['image'])) {
            $meta['cover_image'] = $meta['image'];
        }

        $update = ['metadata_json' => $meta];

        // Map bảng thông tin lên cột (chỉ khi có giá trị rõ hơn).
        $attrs = $parsed['attrs'];
        if (($aps = $this->intFromLabel($attrs, ['Số căn hộ', 'Số căn', 'Số lượng căn hộ'])) > 0) {
            $update['apartments'] = $aps;
        }
        if (($blk = $this->intFromLabel($attrs, ['Số tòa', 'Số block', 'Số tháp', 'Số khối'])) > 0) {
            $update['blocks'] = $blk;
        }
        if ($parsed['project_type'] !== null) {
            $update['project_type'] = $parsed['project_type'];
        }
        if (($p->developer_name === null || $p->developer_name === '') && $parsed['developer'] !== null) {
            $update['developer_name'] = $parsed['developer'];
            if ($dev = \App\Models\Developer::upsertByName($parsed['developer'], ['source' => 'batdongsan.com.vn'])) {
                $update['developer_id'] = $dev->id;
            }
        }

        // Địa chỉ chi tiết hơn từ trang chi tiết → cập nhật address + re-parse ward/district/province.
        $full = $parsed['address_full'];
        if ($full && $this->addressIsBetter($full, (string) $p->address)) {
            $a = static::parseAddress($full);
            $update['address'] = $full;
            $update['ward'] = $a['ward'];
            $update['district'] = $a['district'];
            if ($a['province']) {
                $update['province'] = $a['province'];
            }
        }

        // Toạ độ bản đồ.
        if ($parsed['latitude'] !== null && $parsed['longitude'] !== null) {
            $update['latitude'] = $parsed['latitude'];
            $update['longitude'] = $parsed['longitude'];
        }

        $p->update($update);

        return true;
    }

    /**
     * Backfill CHỦ ĐẦU TƯ từ metadata SẴN CÓ (KHÔNG fetch lại) — nhanh, không đụng Cloudflare.
     * Nguồn: detail['Chủ đầu tư'] → detail_faq ("Chủ đầu tư ...: X") → regex description.
     * Trả true nếu gán được developer.
     */
    public function backfillDeveloperFromMeta(PublicProject $p): bool
    {
        if ($p->developer_id) {
            return false;
        }
        $meta = (array) $p->metadata_json;
        $name = null;

        $detail = (array) ($meta['detail'] ?? []);
        if (! empty($detail['Chủ đầu tư'])) {
            $name = $detail['Chủ đầu tư'];
        }
        if (! $name) {
            foreach ((array) ($meta['detail_faq'] ?? []) as $a) {
                if (preg_match('/Chủ đầu tư[^:]*:\s*(.+)$/iu', (string) $a, $m)) {
                    $cand = static::tidy(trim($m[1], " .\u{00A0}"));
                    if (mb_strlen($cand) > 2) {
                        $name = $cand;
                        break;
                    }
                }
            }
        }
        if (! $name && ! empty($p->description)) {
            $name = static::developer(['summary' => $p->description]);
        }
        if (! $name) {
            return false;
        }

        $name = static::tidy($name);
        $dev = \App\Models\Developer::upsertByName($name, ['source' => 'batdongsan.com.vn']);
        if (! $dev) {
            return false;
        }
        $p->update(['developer_name' => $name, 'developer_id' => $dev->id]);

        return true;
    }

    /** Địa chỉ mới "tốt hơn" nếu có nhiều đoạn (dấu phẩy) hơn hoặc dài hơn rõ rệt. */
    private function addressIsBetter(string $candidate, string $current): bool
    {
        if (trim($current) === '') {
            return true;
        }
        $cn = substr_count($candidate, ',');
        $cur = substr_count($current, ',');
        if ($cn > $cur) {
            return true;
        }

        return $cn === $cur && mb_strlen($candidate) > mb_strlen($current) + 3;
    }

    /** Lấy số nguyên đầu tiên từ giá trị nhãn khớp (vd "280 căn" -> 280). */
    private function intFromLabel(array $attrs, array $labels): int
    {
        foreach ($labels as $l) {
            if (isset($attrs[$l]) && preg_match('/([\d.]+)/', str_replace('.', '', (string) $attrs[$l]), $m)) {
                return (int) $m[1];
            }
        }

        return 0;
    }

    /**
     * Parse trang chi tiết → ['attrs'=>{nhãn:giá trị}, 'faq'=>{hỏi:đáp}, 'price','legal',
     * 'developer','developer_unit','project_type'].
     *
     * @return array<string,mixed>
     */
    public function parseDetail(string $html): array
    {
        $out = ['attrs' => [], 'faq' => [], 'price' => null, 'legal' => null,
            'developer' => null, 'developer_unit' => null, 'project_type' => null,
            'address_full' => null, 'latitude' => null, 'longitude' => null,
            'images' => [], 'cover_image' => null, 'images_watermarked' => false];

        if (trim($html) === '') {
            return $out;
        }

        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?>'.$html);
        libxml_clear_errors();
        $xp = new DOMXPath($doc);

        // Bảng thông tin: tbody.re__project-attr > tr > td.re__attr-item-label + td.re__attr-item-value
        foreach ($xp->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' re__project-attr ')]") as $row) {
            $lbl = $xp->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' re__attr-item-label ')]", $row);
            $val = $xp->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' re__attr-item-value ')]", $row);
            if ($lbl->length === 0 || $val->length === 0) {
                continue;
            }
            $l = trim(preg_replace('/\s+/u', ' ', $lbl->item(0)->textContent));
            $v = trim(preg_replace('/\s+/u', ' ', $val->item(0)->textContent));
            if ($l !== '' && $v !== '') {
                $out['attrs'][$l] = $v;
            }
        }

        // Map một số nhãn trực tiếp.
        foreach (['Loại hình', 'Loại dự án'] as $k) {
            if (! empty($out['attrs'][$k])) {
                $out['project_type'] = $out['attrs'][$k];
                break;
            }
        }
        foreach (['Chủ đầu tư'] as $k) {
            if (! empty($out['attrs'][$k])) {
                $out['developer'] = static::tidy($out['attrs'][$k]);
                break;
            }
        }
        foreach (['Đơn vị phát triển', 'Nhà phát triển'] as $k) {
            if (! empty($out['attrs'][$k])) {
                $out['developer_unit'] = static::tidy($out['attrs'][$k]);
                break;
            }
        }
        foreach (['Mức giá', 'Giá bán', 'Giá'] as $k) {
            if (! empty($out['attrs'][$k])) {
                $out['price'] = $out['attrs'][$k];
                break;
            }
        }
        foreach (['Pháp lý', 'Tình trạng pháp lý'] as $k) {
            if (! empty($out['attrs'][$k])) {
                $out['legal'] = $out['attrs'][$k];
                break;
            }
        }

        // FAQ: re__collapse-box (label = hỏi, content = đáp).
        foreach ($xp->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' re__collapse-box ')]") as $box) {
            $qN = $xp->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' re__collapse-label ')]", $box);
            $aN = $xp->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' re__collapse-content ')]", $box);
            if ($qN->length === 0 || $aN->length === 0) {
                continue;
            }
            $q = trim(preg_replace('/\s+/u', ' ', $qN->item(0)->textContent));
            $a = trim(preg_replace('/\s+/u', ' ', $aN->item(0)->textContent));
            if ($q === '' || $a === '') {
                continue;
            }
            $out['faq'][Str::limit($q, 200, '')] = Str::limit($a, 500, '');

            // Suy ra CĐT / giá từ FAQ nếu bảng thông tin thiếu.
            if ($out['developer'] === null && preg_match('/Chủ đầu tư[^:]*:\s*(.+)$/iu', $a, $m)) {
                $d = static::tidy(trim($m[1], " .\u{00A0}"));
                if ($d !== '' && mb_strlen($d) > 2) {
                    $out['developer'] = $d;
                }
            }
            if ($out['price'] === null && Str::contains(mb_strtolower($q, 'UTF-8'), 'giá')
                && preg_match('/([\d.,]+\s*(?:triệu|tỷ)[^.\n]*)/u', $a, $m)) {
                $out['price'] = static::tidy($m[1]);
            }
        }

        // CĐT từ mô tả tổng quan nếu vẫn thiếu.
        if ($out['developer'] === null) {
            $ov = $xp->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' re__detail-content ')]");
            if ($ov->length > 0) {
                $out['developer'] = static::developer(['summary' => $ov->item(0)->textContent]);
            }
        }

        // Địa chỉ đầy đủ hơn: div.re__project-address (bỏ text link "Xem bản đồ").
        $addrN = $xp->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' re__project-address ')]");
        if ($addrN->length > 0) {
            $node = $addrN->item(0)->cloneNode(true);
            // gỡ các <a> (link "Xem bản đồ") khỏi bản sao trước khi lấy text
            foreach (iterator_to_array($node->getElementsByTagName('a')) as $a) {
                $a->parentNode?->removeChild($a);
            }
            $full = trim(preg_replace('/\s+/u', ' ', $node->textContent));
            $full = rtrim($full, " .\u{00A0}");
            if ($full !== '') {
                $out['address_full'] = $full;
            }
        }

        // Toạ độ: URL Google Maps ...q=<lat>,<lng> hoặc LatLng(lat,lng).
        if (preg_match('#[?&]q=(-?\d{1,2}\.\d+),(-?\d{2,3}\.\d+)#', $html, $m)
            || preg_match('#LatLng\(\s*(-?\d{1,2}\.\d+)\s*,\s*(-?\d{2,3}\.\d+)#i', $html, $m)) {
            $lat = (float) $m[1];
            $lng = (float) $m[2];
            // Sơ bộ trong khung Việt Nam.
            if ($lat >= 7 && $lat <= 24 && $lng >= 100 && $lng <= 115) {
                $out['latitude'] = round($lat, 7);
                $out['longitude'] = round($lng, 7);
            }
        }

        // Ảnh dự án: lấy từ album (re__project-album), quy về bản full-size (bỏ /crop/NxN/),
        // rồi gom thêm các ảnh full-size cùng "lô upload" (cùng path YYYY/MM/DD) trong trang
        // → tránh vơ nhầm ảnh của dự án liên quan. Ảnh batdongsan có hậu tố _wm = watermark.
        $stripCrop = fn (string $u): string => preg_replace('#/crop/\d+x\d+/#', '/', $u);
        $albumImgs = [];
        foreach ($xp->query("//*[contains(@class,'re__project-album__media')]//img") as $img) {
            $u = $img->getAttribute('data-src') ?: $img->getAttribute('src');
            if ($u && ! Str::startsWith($u, 'data:')) {
                $albumImgs[] = $stripCrop($u);
            }
        }
        $prefixes = [];
        foreach ($albumImgs as $u) {
            if (preg_match('#(https://file\d+\.batdongsan\.com\.vn/\d{4}/\d{2}/\d{2}/)#', $u, $m)) {
                $prefixes[$m[1]] = true;
            }
        }
        $images = $albumImgs;
        if ($prefixes !== [] && preg_match_all('#https://file\d+\.batdongsan\.com\.vn/(?!crop/)\d{4}/\d{2}/\d{2}/[^"\'\s\\\\]+?\.(?:jpg|jpeg|png|webp)#i', $html, $mm)) {
            foreach ($mm[0] as $u) {
                foreach (array_keys($prefixes) as $pre) {
                    if (Str::startsWith($u, $pre)) {
                        $images[] = $u;
                        break;
                    }
                }
            }
        }
        // Chuẩn hoá: unique, giữ thứ tự, tối đa 40 (lấy nhiều ảnh hơn/dự án).
        $images = array_values(array_unique($images));
        $images = array_slice($images, 0, 40);
        if ($images !== []) {
            $out['images'] = $images;
            $out['cover_image'] = $images[0];
            $out['images_watermarked'] = (bool) collect($images)->contains(fn ($u) => str_contains($u, '_wm'));
        }

        return $out;
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

        return static::canonicalProvince(end($parts) ?: null);
    }

    /**
     * Chuẩn hoá tên tỉnh/TP về 1 tên canonical thống nhất:
     * bỏ tiền tố "Tỉnh/Thành phố/TP.", gộp biến thể HCM/Hà Nội/BR-VT..., bỏ rác.
     * Idempotent (chạy lại trên kết quả không đổi).
     */
    public static function canonicalProvince(?string $s): ?string
    {
        if ($s === null) {
            return null;
        }
        $s = trim(preg_replace('/\s+/u', ' ', $s));
        if ($s === '') {
            return null;
        }

        // Chuỗi rác kiểu địa chỉ dài "... - tỉnh Long An": lấy phần sau "tỉnh/thành phố" cuối.
        if (mb_strlen($s) > 28 && preg_match('/(?:tỉnh|thành phố)\s+([^,\-)]+)\s*$/iu', $s, $m)) {
            $s = trim($m[1]);
        }

        // Bỏ ký tự rác đầu/cuối.
        $s = trim($s, " .)(-");
        // Bỏ tiền tố hành chính (kể cả dạng dính "TP.HCM", "TP.Hồ Chí Minh").
        $s = preg_replace('/^(tỉnh|thành\s*phố|tp)\.?\s*/iu', '', $s);
        $s = trim($s, " .)(-");

        $key = mb_strtolower($s, 'UTF-8');

        $aliases = [
            'hồ chí minh' => 'Hồ Chí Minh', 'hcm' => 'Hồ Chí Minh', 'tphcm' => 'Hồ Chí Minh',
            'hcmc' => 'Hồ Chí Minh', 'sài gòn' => 'Hồ Chí Minh', 'sai gon' => 'Hồ Chí Minh',
            'thủ đức' => 'Hồ Chí Minh', 'q.7' => 'Hồ Chí Minh', 'quận 7' => 'Hồ Chí Minh',
            'hà nội' => 'Hà Nội', 'ha noi' => 'Hà Nội', 'hanoi' => 'Hà Nội',
            'bà rịa vũng tàu' => 'Bà Rịa - Vũng Tàu', 'bà rịa - vũng tàu' => 'Bà Rịa - Vũng Tàu',
            'br-vt' => 'Bà Rịa - Vũng Tàu', 'brvt' => 'Bà Rịa - Vũng Tàu',
            'bình dươn' => 'Bình Dương',
            'thừa thiên huế' => 'Thừa Thiên Huế', 'huế' => 'Thừa Thiên Huế',
            'vinh' => 'Nghệ An', 'vinh cũ' => 'Nghệ An',
            'thanh hoá' => 'Thanh Hóa',
            'việt nam' => null, 'vietnam' => null, 'vn' => null,
        ];
        if (array_key_exists($key, $aliases)) {
            return $aliases[$key];
        }

        // Không giống tên tỉnh (còn dấu địa chỉ) → coi như không xác định.
        if (str_contains($s, ' - ') || preg_match('/(đường|số|xã|huyện|quận|phường)/iu', $s)) {
            return null;
        }

        return $s !== '' ? $s : null;
    }

    /**
     * Tách chuỗi địa chỉ "[số/đường,] Phường/Xã, Quận/Huyện, Tỉnh/Thành" thành cấu trúc.
     * Phân loại theo TIỀN TỐ; quận/huyện có thể KHÔNG có tiền tố (dạng bare "Bình Tân",
     * "Sơn Trà") khi đứng sau phường. Lưu VERBATIM (không đổi tên hành chính cũ/mới).
     *
     * @return array{ward:?string,district:?string,province:?string,street:?string}
     */
    public static function parseAddress(string $address): array
    {
        $out = ['ward' => null, 'district' => null, 'province' => null, 'street' => null];
        $parts = array_values(array_filter(array_map('trim', explode(',', $address)), fn ($p) => $p !== ''));
        if (empty($parts)) {
            return $out;
        }

        // Đoạn cuối = tỉnh/thành (chuẩn hoá canonical).
        $out['province'] = static::canonicalProvince(array_pop($parts));
        if (empty($parts)) {
            return $out;
        }

        $wardRe     = '/^(phường|xã|thị\s*trấn)([\s\d]|$)/iu';
        $districtRe = '/^(quận|huyện|thành\s*phố|thị\s*xã)([\s\d]|$)/iu';

        $wardIdx = null;
        $districtIdx = null;
        foreach ($parts as $i => $seg) {
            if ($out['ward'] === null && preg_match($wardRe, $seg)) {
                $out['ward'] = $seg;
                $wardIdx = $i;
            } elseif ($out['district'] === null && preg_match($districtRe, $seg)) {
                $out['district'] = $seg;
                $districtIdx = $i;
            }
        }

        // Quận/huyện dạng bare (không tiền tố): chỉ nhận khi có phường đứng trước
        // (tránh nhầm số nhà/đường thành quận). Lấy đoạn đầu tiên sau phường.
        if ($out['district'] === null && $wardIdx !== null) {
            for ($i = $wardIdx + 1; $i < count($parts); $i++) {
                if ($parts[$i] !== $out['ward']) {
                    $out['district'] = $parts[$i];
                    $districtIdx = $i;
                    break;
                }
            }
        }

        // Street = các đoạn đầu trước cấp hành chính đầu tiên (phường/quận).
        $adminIdxs = array_filter([$wardIdx, $districtIdx], fn ($x) => $x !== null);
        $firstAdmin = $adminIdxs === [] ? null : min($adminIdxs);
        $street = $firstAdmin === null
            ? implode(', ', $parts)
            : implode(', ', array_slice($parts, 0, $firstAdmin));
        $out['street'] = $street !== '' ? $street : null;

        return $out;
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
