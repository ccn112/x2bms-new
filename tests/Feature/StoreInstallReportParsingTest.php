<?php

namespace Tests\Feature;

use App\Services\Analytics\StoreReports\AppStoreReportClient;
use App\Services\Analytics\StoreReports\AppStoreSalesTsvParser;
use App\Services\Analytics\StoreReports\GooglePlayReportClient;
use App\Services\Analytics\StoreReports\PlayInstallsCsvParser;
use App\Services\Analytics\StoreReports\StoreInstallSyncer;
use App\Services\Analytics\StoreReports\StoreReportFormatException;
use Tests\TestCase;

/**
 * Ghép số lượt cài từ hai store (chốt 2026-07-30 — chủ dự án cấp key sau, nên
 * CHƯA chạy thật với credential).
 *
 * Vẫn test được phần dễ sai nhất mà không cần key: **bóc file**. Google trả CSV
 * UTF-16LE với tên cột đổi theo thời gian; Apple trả gzip-TSV gộp mọi app của cùng
 * vendor và nhiều loại giao dịch trong cùng một ngày. Sai ở đây là ra số cài sai mà
 * không ai biết.
 */
class StoreInstallReportParsingTest extends TestCase
{
    // ------------------------------------------------------------ Google Play

    private function playCsv(): string
    {
        return implode("\n", [
            'Date,Package Name,Daily Device Installs,Daily Device Uninstalls,Daily Device Upgrades,Active Device Installs',
            '2026-07-01,vn.x2bms.resident_mobile,12,2,5,340',
            '2026-07-02,vn.x2bms.resident_mobile,18,1,7,357',
            '2026-07-03,vn.x2bms.resident_mobile,,,,',
        ]);
    }

    public function test_boc_duoc_csv_utf8(): void
    {
        $rows = (new PlayInstallsCsvParser)->parse($this->playCsv());

        $this->assertCount(3, $rows);
        $this->assertSame('2026-07-01', $rows[0]['stat_date']);
        $this->assertSame(12, $rows[0]['installs']);
        $this->assertSame(2, $rows[0]['uninstalls']);
        $this->assertSame(5, $rows[0]['updates']);
        $this->assertSame(340, $rows[0]['active_devices']);
    }

    public function test_boc_duoc_csv_utf16le_co_bom(): void
    {
        // File thật của Play Console là UTF-16LE kèm BOM, KHÔNG phải UTF-8. Đọc
        // thẳng sẽ ra ký tự NUL xen giữa từng chữ và không khớp tên cột nào.
        $utf16 = "\xFF\xFE".mb_convert_encoding($this->playCsv(), 'UTF-16LE', 'UTF-8');

        $rows = (new PlayInstallsCsvParser)->parse($utf16);

        $this->assertCount(3, $rows);
        $this->assertSame(18, $rows[1]['installs'],
            'hỏng bước đổi encoding thì mọi cột đều không khớp');
    }

    public function test_o_rong_tra_ve_null_chu_khong_phai_so_0(): void
    {
        // 0 nghĩa là "hôm đó không ai tải"; rỗng nghĩa là "store không cấp số".
        // Trả 0 cho ô rỗng là bịa số liệu.
        $rows = (new PlayInstallsCsvParser)->parse($this->playCsv());

        $this->assertNull($rows[2]['installs']);
        $this->assertNull($rows[2]['active_devices']);
    }

    public function test_nhan_ca_bo_ten_cot_kieu_user(): void
    {
        // Play đã đổi bộ tên metric; CSV tải về có thể mang tên "User" thay vì
        // "Device". Không nhận cả hai thì im lặng mất số.
        $csv = "Date,Package Name,Daily User Installs,Daily User Uninstalls\n"
            .'2026-07-01,vn.x2bms.resident_mobile,9,1';

        $rows = (new PlayInstallsCsvParser)->parse($csv);

        $this->assertSame(9, $rows[0]['installs']);
        $this->assertSame(1, $rows[0]['uninstalls']);
    }

    public function test_doi_dinh_dang_thi_nem_loi_chu_khong_tra_ve_rong(): void
    {
        // Mảng rỗng trông giống "hôm đó không ai tải" — số 0 giả còn tệ hơn không
        // có số. Store đổi định dạng thì phải có người biết để sửa parser.
        $this->expectException(StoreReportFormatException::class);

        (new PlayInstallsCsvParser)->parse("Ngay,Goi,So luot\n2026-07-01,abc,5");
    }

    // -------------------------------------------------------------- App Store

    private function appleTsv(): string
    {
        $h = "Provider\tProvider Country\tSKU\tDeveloper\tTitle\tVersion\t"
            ."Product Type Identifier\tUnits\tDeveloper Proceeds\tBegin Date\tEnd Date\t"
            ."Customer Currency\tCountry Code\tVendor Identifier";

        return implode("\n", [
            $h,
            // Tải mới (nhóm 1) — PHẢI tính
            "APPLE\tVN\tX2RES\tX2\tX2-BMS\t1.0\t1\t7\t0\t07/01/2026\t07/01/2026\tVND\tVN\tX2RES",
            "APPLE\tVN\tX2RES\tX2\tX2-BMS\t1.0\t1F\t3\t0\t07/01/2026\t07/01/2026\tVND\tVN\tX2RES",
            // Cập nhật (nhóm 7) — KHÔNG được tính
            "APPLE\tVN\tX2RES\tX2\tX2-BMS\t1.0\t7\t50\t0\t07/01/2026\t07/01/2026\tVND\tVN\tX2RES",
            // App KHÁC của cùng vendor — KHÔNG được tính
            "APPLE\tVN\tOTHER\tX2\tKhac\t1.0\t1\t99\t0\t07/01/2026\t07/01/2026\tVND\tVN\tOTHER",
            // Ngày khác
            "APPLE\tVN\tX2RES\tX2\tX2-BMS\t1.0\t1\t5\t0\t07/02/2026\t07/02/2026\tVND\tVN\tX2RES",
        ]);
    }

    public function test_apple_chi_tinh_luot_tai_moi_va_dung_app(): void
    {
        $rows = (new AppStoreSalesTsvParser)->parse($this->appleTsv(), 'X2RES');

        $this->assertCount(2, $rows);
        $this->assertSame('2026-07-01', $rows[0]['stat_date']);
        $this->assertSame(10, $rows[0]['installs'],
            '7 + 3 lượt tải mới; KHÔNG cộng 50 lượt cập nhật và 99 của app khác');
        $this->assertSame('2026-07-02', $rows[1]['stat_date']);
        $this->assertSame(5, $rows[1]['installs']);
    }

    public function test_apple_giai_nen_duoc_gzip(): void
    {
        // Endpoint thật trả gzip, không phải TSV trần.
        $rows = (new AppStoreSalesTsvParser)->parse(gzencode($this->appleTsv()), 'X2RES');

        $this->assertSame(10, $rows[0]['installs']);
    }

    public function test_apple_thieu_cot_thi_nem_loi(): void
    {
        $this->expectException(StoreReportFormatException::class);

        (new AppStoreSalesTsvParser)->parse("Provider\tSKU\n APPLE\tX2RES");
    }

    // ------------------------------------------- chưa cấu hình thì phải nói thật

    public function test_chua_co_key_thi_bao_not_configured_chu_khong_bia_so(): void
    {
        config()->set('store_reports.google', ['bucket' => null, 'package' => null, 'credentials' => null]);
        config()->set('store_reports.apple', ['issuer_id' => null, 'key_id' => null,
            'vendor_number' => null, 'private_key' => null, 'sku' => null]);

        $this->assertFalse(app(GooglePlayReportClient::class)->isConfigured());
        $this->assertFalse(app(AppStoreReportClient::class)->isConfigured());

        $result = app(StoreInstallSyncer::class)->sync();

        $this->assertSame('not_configured', $result['google_play']['status']);
        $this->assertSame('not_configured', $result['app_store']['status']);
        $this->assertSame(0, $result['google_play']['rows']);
        $this->assertSame(0, $result['app_store']['rows']);
    }

    public function test_lenh_dong_bo_khong_fail_khi_chua_co_key(): void
    {
        // Cron chạy hằng ngày trong lúc chờ cấp key — không được kêu lỗi mỗi ngày.
        config()->set('store_reports.google.bucket', null);
        config()->set('store_reports.apple.issuer_id', null);

        $this->artisan('x2:sync-store-installs')->assertSuccessful();
    }
}
