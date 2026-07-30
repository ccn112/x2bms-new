<?php

namespace App\Services\Analytics\StoreReports;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Tải file CSV số lượt cài từ bucket Cloud Storage của Play Console.
 *
 * Play **không có API trả số lượt cài** (Play Developer Reporting API chỉ có
 * Android vitals). Cơ chế thật: Play Console tự đẩy CSV theo tháng vào một bucket
 * GCS thuộc nhà phát triển, đường dẫn
 * `gs://{bucket}/stats/installs/installs_{package}_{yyyyMM}_overview.csv`.
 *
 * Không dùng SDK Google: chỉ cần hai request (đổi JWT lấy access token, rồi tải
 * object) nên thêm cả bộ google/cloud-storage vào dự án là không đáng.
 *
 * ⚠️ Chưa chạy thật với credential (chủ dự án cấp key sau — chốt 30/07). Phần bóc
 * CSV thì đã test bằng file mẫu, xem `StoreInstallReportParsingTest`.
 */
class GooglePlayReportClient
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const SCOPE = 'https://www.googleapis.com/auth/devstorage.read_only';

    public function isConfigured(): bool
    {
        $c = config('store_reports.google');

        return ! empty($c['bucket'])
            && ! empty($c['package'])
            && ! empty($c['credentials'])
            && is_file((string) $c['credentials']);
    }

    /**
     * Tải + bóc báo cáo của MỘT THÁNG.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchMonth(Carbon $month): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Chưa cấu hình Google Play reports (xem config/store_reports.php).');
        }

        $c = config('store_reports.google');
        $object = sprintf('stats/installs/installs_%s_%s_overview.csv',
            $c['package'], $month->format('Ym'));

        $url = sprintf('https://storage.googleapis.com/storage/v1/b/%s/o/%s?alt=media',
            urlencode((string) $c['bucket']), urlencode($object));

        $res = Http::withToken($this->accessToken())->timeout(60)->get($url);

        // 404 = tháng đó chưa có báo cáo (tháng hiện tại thường chưa chốt). Đây là
        // trạng thái BÌNH THƯỜNG, không phải lỗi — trả rỗng để người gọi bỏ qua.
        if ($res->status() === 404) {
            return [];
        }
        if (! $res->successful()) {
            throw new RuntimeException(
                'Tải báo cáo Play thất bại ('.$res->status().'): '.mb_substr($res->body(), 0, 300));
        }

        return app(PlayInstallsCsvParser::class)->parse($res->body());
    }

    /** Đổi JWT tự ký của service account lấy access token (RS256). */
    private function accessToken(): string
    {
        $path = (string) config('store_reports.google.credentials');
        $json = json_decode((string) file_get_contents($path), true);
        if (! is_array($json) || empty($json['client_email']) || empty($json['private_key'])) {
            throw new RuntimeException("File service account không hợp lệ: $path");
        }

        $now = time();
        $claims = [
            'iss' => $json['client_email'],
            'scope' => self::SCOPE,
            'aud' => self::TOKEN_URL,
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $signing = $this->b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT']))
            .'.'.$this->b64(json_encode($claims));

        $sig = '';
        if (! openssl_sign($signing, $sig, $json['private_key'], OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Không ký được JWT cho Google (khoá sai định dạng?).');
        }

        $res = Http::asForm()->timeout(30)->post(self::TOKEN_URL, [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $signing.'.'.$this->b64($sig),
        ]);

        if (! $res->successful() || empty($res->json('access_token'))) {
            throw new RuntimeException(
                'Lấy access token Google thất bại: '.mb_substr($res->body(), 0, 300));
        }

        return (string) $res->json('access_token');
    }

    private function b64(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
}
