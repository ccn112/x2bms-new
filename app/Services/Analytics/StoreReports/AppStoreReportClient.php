<?php

namespace App\Services\Analytics\StoreReports;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Tải báo cáo SALES/SUMMARY của App Store Connect (số lượt tải = cột `Units`).
 *
 * Auth là **JWT ES256 tự ký** từ khoá `.p8`, sống tối đa 20 phút. Ở đây ký 15 phút.
 *
 * Khác Google ở hai điểm quan trọng:
 * - Apple trả theo **NGÀY** (`frequency=DAILY`), không phải theo tháng.
 * - Response là **gzip chứa TSV**, và HTTP **404 nghĩa là "ngày đó không có dữ
 *   liệu"** (chưa chốt số, hoặc không có lượt tải nào) — là trạng thái bình thường
 *   chứ không phải lỗi.
 *
 * ⚠️ Chưa chạy thật với credential (chủ dự án cấp key sau — chốt 30/07). Phần bóc
 * TSV đã test bằng file mẫu, xem `StoreInstallReportParsingTest`.
 */
class AppStoreReportClient
{
    private const URL = 'https://api.appstoreconnect.apple.com/v1/salesReports';

    public function isConfigured(): bool
    {
        $c = config('store_reports.apple');

        return ! empty($c['issuer_id'])
            && ! empty($c['key_id'])
            && ! empty($c['vendor_number'])
            && ! empty($c['private_key'])
            && is_file((string) $c['private_key']);
    }

    /**
     * Tải + bóc báo cáo của MỘT NGÀY.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchDay(Carbon $day): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Chưa cấu hình App Store Connect (xem config/store_reports.php).');
        }

        $c = config('store_reports.apple');

        $res = Http::withToken($this->jwt())
            ->withHeaders(['Accept' => 'application/a-gzip'])
            ->timeout(60)
            ->get(self::URL, [
                'filter[reportType]' => 'SALES',
                'filter[reportSubType]' => 'SUMMARY',
                'filter[frequency]' => 'DAILY',
                'filter[vendorNumber]' => $c['vendor_number'],
                'filter[reportDate]' => $day->toDateString(),
            ]);

        if ($res->status() === 404) {
            return []; // ngày đó chưa/không có dữ liệu — bình thường
        }
        if (! $res->successful()) {
            throw new RuntimeException(
                'Tải báo cáo App Store thất bại ('.$res->status().'): '
                .mb_substr($res->body(), 0, 300));
        }

        return app(AppStoreSalesTsvParser::class)->parse($res->body(), $c['sku'] ?: null);
    }

    /**
     * JWT ES256. `openssl_sign` trả chữ ký dạng **DER**, còn JWS cần **R||S thô**
     * (mỗi phần 32 byte) — thiếu bước đổi này là Apple luôn trả 401 mà không nói
     * vì sao.
     */
    private function jwt(): string
    {
        $c = config('store_reports.apple');
        $key = (string) file_get_contents((string) $c['private_key']);

        $header = ['alg' => 'ES256', 'kid' => $c['key_id'], 'typ' => 'JWT'];
        $now = time();
        $claims = [
            'iss' => $c['issuer_id'],
            'iat' => $now,
            'exp' => $now + 900,               // Apple cho tối đa 20 phút
            'aud' => 'appstoreconnect-v1',
        ];

        $signing = $this->b64(json_encode($header)).'.'.$this->b64(json_encode($claims));

        $der = '';
        if (! openssl_sign($signing, $der, $key, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Không ký được JWT cho App Store (khoá .p8 sai?).');
        }

        return $signing.'.'.$this->b64($this->derToRawSignature($der));
    }

    /** DER SEQUENCE{INTEGER r, INTEGER s} → r||s, mỗi phần đệm về đúng 32 byte. */
    private function derToRawSignature(string $der): string
    {
        $offset = 0;
        if (($der[$offset++] ?? '') !== "\x30") {
            throw new RuntimeException('Chữ ký DER không hợp lệ.');
        }
        $len = ord($der[$offset++]);
        if ($len > 0x80) {
            $offset += $len - 0x80;            // độ dài nhiều byte
        }

        $read = function () use ($der, &$offset): string {
            if (($der[$offset++] ?? '') !== "\x02") {
                throw new RuntimeException('Chữ ký DER không hợp lệ (thiếu INTEGER).');
            }
            $n = ord($der[$offset++]);
            $v = substr($der, $offset, $n);
            $offset += $n;

            // Bỏ byte 0x00 mà DER thêm vào khi byte đầu ≥ 0x80.
            return str_pad(ltrim($v, "\x00"), 32, "\x00", STR_PAD_LEFT);
        };

        return $read().$read();
    }

    private function b64(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
}
