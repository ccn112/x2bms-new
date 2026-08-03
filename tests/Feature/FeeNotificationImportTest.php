<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\BillingPeriod;
use App\Models\Building;
use App\Models\FeeType;
use App\Models\Project;
use App\Models\Statement;
use App\Models\Tenant;
use App\Support\Import\Profiles\FeeNotificationImportProfile;
use App\Support\Import\StagingImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Tests\TestCase;

/**
 * OPTION A — import "thông báo phí" mẫu CŨ (hệ thống tự tính thành tiền).
 * Số liệu lấy từ file thật `import_thong_bao_phi-HPO-05.2026.xlsx`.
 */
class FeeNotificationImportTest extends TestCase
{
    use RefreshDatabase;

    private array $ctx;

    private Building $building;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::create(['code' => 'TEN-HPO', 'name' => 'HPO']);
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => 'PRJ-HPO', 'name' => 'Happy One']);
        $this->building = Building::create(['tenant_id' => $tenant->id, 'project_id' => $project->id, 'code' => 'BLD-HPO', 'name' => 'Toà A']);
        BillingPeriod::create(['tenant_id' => $tenant->id, 'building_id' => $this->building->id, 'code' => '202605', 'label' => 'Tháng 5/2026', 'period_month' => '2026-05-01', 'due_date' => '2026-05-25']);

        foreach ([
            ['PQL', 'Phí quản lý', 'management'],
            ['NUOC', 'Phí nước', 'utility'],
            ['XEMAY', 'Phí xe máy', 'parking'],
        ] as [$code, $name, $cat]) {
            FeeType::create(['tenant_id' => $tenant->id, 'code' => $code, 'name' => $name, 'category' => $cat, 'is_critical' => false, 'payment_priority' => 100]);
        }

        foreach (['A-0101', 'A-0102'] as $code) {
            Apartment::create(['tenant_id' => $tenant->id, 'building_id' => $this->building->id, 'code' => $code]);
        }

        $this->ctx = ['tenant_id' => $tenant->id, 'building_id' => $this->building->id, 'user_id' => null];
    }

    private const HEADERS = [
        'Mã căn hộ', 'Ngày bắt đầu tính phí', 'Ngày kết thúc', 'Kỳ', 'Hạn thanh toán', 'Mã dịch vụ',
        'Loại giá áp dụng', 'Số lượng sử dụng', 'Đơn giá cố định', 'Chỉ số đầu', 'Chỉ số cuối',
        'Định mức 1', 'Đơn giá 1', 'Định mức 2', 'Đơn giá 2', 'Định mức 3', 'Đơn giá 3',
        'Giảm giá', 'Loại giảm giá', 'Ghi chú', 'Biển số xe',
    ];

    /** Sinh file xlsx mẫu cũ (header tiếng Việt), trả path. Mỗi dòng đủ 21 cột. */
    private function writeXlsx(array $rows): string
    {
        $template = array_fill_keys(self::HEADERS, '');
        $path = sys_get_temp_dir().'/fee_notif_'.uniqid().'.xlsx';
        $writer = SimpleExcelWriter::create($path);
        foreach ($rows as $r) {
            $writer->addRow(array_merge($template, $r));
        }
        $writer->close();

        return $path;
    }

    private function sampleRows(): array
    {
        $base = ['Ngày bắt đầu tính phí' => '01/05/2026', 'Ngày kết thúc' => '31/05/2026', 'Kỳ' => '202605', 'Hạn thanh toán' => '25-05-2026'];

        return [
            // PQL cố định: 1 × 1.911.000
            $base + ['Mã căn hộ' => 'A-0101', 'Mã dịch vụ' => 'PQL', 'Loại giá áp dụng' => '1', 'Số lượng sử dụng' => '1', 'Đơn giá cố định' => '1911000'],
            // NUOC lũy tiến: Định mức 1 = 2 × 12.075 = 24.150
            ['Ngày bắt đầu tính phí' => '28/03/2026', 'Ngày kết thúc' => '28/04/2026', 'Kỳ' => '202605', 'Hạn thanh toán' => '25-05-2026',
                'Mã căn hộ' => 'A-0101', 'Mã dịch vụ' => 'NUOC', 'Loại giá áp dụng' => '2', 'Chỉ số đầu' => '60', 'Chỉ số cuối' => '62', 'Định mức 1' => '2', 'Đơn giá 1' => '12075'],
            // XEMAY cố định: 3 × 120.000 = 360.000
            $base + ['Mã căn hộ' => 'A-0101', 'Mã dịch vụ' => 'XEMAY', 'Loại giá áp dụng' => '1', 'Số lượng sử dụng' => '3', 'Đơn giá cố định' => '120000'],
        ];
    }

    private function stageAndCommit(string $path): void
    {
        $profile = new FeeNotificationImportProfile;
        $importer = new StagingImporter;
        $batch = $importer->stage($path, basename($path), $profile, $this->ctx);
        $importer->commit($batch, $profile, $this->ctx);
    }

    public function test_tinh_dung_thanh_tien_va_map_family(): void
    {
        $this->stageAndCommit($this->writeXlsx($this->sampleRows()));

        $statement = Statement::where('code', 'BK-202605-A-0101')->firstOrFail();
        $this->assertSame(Statement::APPROVAL_PENDING, $statement->approval_status);
        $this->assertNull($statement->published_at);

        $lines = $statement->lines()->get()->keyBy('fee_category');
        $this->assertSame('1911000.00', (string) $lines['management']->amount);
        $this->assertSame('24150.00', (string) $lines['water']->amount);
        $this->assertSame('360000.00', (string) $lines['vehicle']->amount);

        // total_amount là tổng các dòng: 1.911.000 + 24.150 + 360.000 = 2.295.150
        $this->assertSame('2295150.00', (string) $statement->fresh()->total_amount);

        // snapshot lưu cách tính (nguồn legacy)
        $water = $lines['water'];
        $this->assertSame('legacy_import', $water->source);
        $this->assertSame('metered', $water->calculation_snapshot['method']);
        $this->assertSame('2', $water->calculation_snapshot['consumption']);
        $this->assertSame('legacy_import', $water->calculation_snapshot['source']);

        // cư dân KHÔNG thấy khi còn pending (D1)
        $this->assertFalse($statement->fresh()->isVisibleToResident());
    }

    public function test_import_2_lan_khong_nhan_doi(): void
    {
        $rows = $this->sampleRows();
        $this->stageAndCommit($this->writeXlsx($rows));
        $this->stageAndCommit($this->writeXlsx($rows));

        $statement = Statement::where('code', 'BK-202605-A-0101')->firstOrFail();
        $this->assertSame(3, $statement->lines()->count(), 'Re-import cùng dữ liệu không nhân đôi dòng');
        $this->assertSame('2295150.00', (string) $statement->total_amount);
    }

    public function test_ma_dich_vu_la_bi_chan(): void
    {
        $rows = [[
            'Ngày bắt đầu tính phí' => '01/05/2026', 'Ngày kết thúc' => '31/05/2026', 'Kỳ' => '202605', 'Hạn thanh toán' => '25-05-2026',
            'Mã căn hộ' => 'A-0101', 'Mã dịch vụ' => 'KHONGCO', 'Loại giá áp dụng' => '1', 'Số lượng sử dụng' => '1', 'Đơn giá cố định' => '100000',
        ]];
        $this->stageAndCommit($this->writeXlsx($rows));

        $this->assertSame(0, Statement::count(), 'Mã dịch vụ không tồn tại → dòng bị chặn, không tạo bảng kê');
    }

    public function test_gia_0_tao_dong_0_dong(): void
    {
        $rows = [[
            'Ngày bắt đầu tính phí' => '01/05/2026', 'Ngày kết thúc' => '31/05/2026', 'Kỳ' => '202605', 'Hạn thanh toán' => '25-05-2026',
            'Mã căn hộ' => 'A-0102', 'Mã dịch vụ' => 'PQL', 'Loại giá áp dụng' => '1', 'Số lượng sử dụng' => '1', 'Đơn giá cố định' => '0',
        ]];
        $this->stageAndCommit($this->writeXlsx($rows));

        $line = Statement::where('code', 'BK-202605-A-0102')->firstOrFail()->lines()->sole();
        $this->assertSame('0.00', (string) $line->amount);
    }

    public function test_luy_tien_nhieu_bac_cong_don(): void
    {
        // NUOC 3 bậc: 10×5.000 + 5×7.000 + 3×10.000 = 50.000 + 35.000 + 30.000 = 115.000
        $rows = [[
            'Ngày bắt đầu tính phí' => '01/05/2026', 'Ngày kết thúc' => '31/05/2026', 'Kỳ' => '202605', 'Hạn thanh toán' => '25-05-2026',
            'Mã căn hộ' => 'A-0101', 'Mã dịch vụ' => 'NUOC', 'Loại giá áp dụng' => '2', 'Chỉ số đầu' => '100', 'Chỉ số cuối' => '118',
            'Định mức 1' => '10', 'Đơn giá 1' => '5000', 'Định mức 2' => '5', 'Đơn giá 2' => '7000', 'Định mức 3' => '3', 'Đơn giá 3' => '10000',
        ]];
        $this->stageAndCommit($this->writeXlsx($rows));

        $line = Statement::where('code', 'BK-202605-A-0101')->firstOrFail()->lines()->where('fee_category', 'water')->sole();
        $this->assertSame('115000.00', (string) $line->amount);
        $this->assertSame('18', $line->calculation_snapshot['consumption']);
        $this->assertCount(3, $line->calculation_snapshot['tiers']);
    }

    public function test_tru_giam_gia(): void
    {
        // PQL 1×1.000.000 giảm 150.000 → 850.000
        $rows = [[
            'Ngày bắt đầu tính phí' => '01/05/2026', 'Ngày kết thúc' => '31/05/2026', 'Kỳ' => '202605', 'Hạn thanh toán' => '25-05-2026',
            'Mã căn hộ' => 'A-0101', 'Mã dịch vụ' => 'PQL', 'Loại giá áp dụng' => '1', 'Số lượng sử dụng' => '1', 'Đơn giá cố định' => '1000000', 'Giảm giá' => '150000',
        ]];
        $this->stageAndCommit($this->writeXlsx($rows));

        $line = Statement::where('code', 'BK-202605-A-0101')->firstOrFail()->lines()->where('fee_category', 'management')->sole();
        $this->assertSame('850000.00', (string) $line->amount);
        $this->assertSame(150000, $line->calculation_snapshot['discount_vnd']);
    }

    public function test_suy_price_type_metered_khi_co_chi_so_du_bo_trong_loai_gia(): void
    {
        // Không điền "Loại giá áp dụng" nhưng có chỉ số → tự hiểu là lũy tiến.
        $rows = [[
            'Ngày bắt đầu tính phí' => '28/03/2026', 'Ngày kết thúc' => '28/04/2026', 'Kỳ' => '202605', 'Hạn thanh toán' => '25-05-2026',
            'Mã căn hộ' => 'A-0101', 'Mã dịch vụ' => 'NUOC', 'Chỉ số đầu' => '481', 'Chỉ số cuối' => '505', 'Định mức 1' => '24', 'Đơn giá 1' => '12075',
        ]];
        $this->stageAndCommit($this->writeXlsx($rows));

        $line = Statement::where('code', 'BK-202605-A-0101')->firstOrFail()->lines()->where('fee_category', 'water')->sole();
        $this->assertSame('289800.00', (string) $line->amount);
        $this->assertSame('metered', $line->calculation_snapshot['method']);
    }

    public function test_chan_them_dong_vao_bang_ke_da_published(): void
    {
        // Import PQL → statement pending; phát hành; import thêm XEMAY cùng căn/kỳ → phải bị chặn.
        $this->stageAndCommit($this->writeXlsx([[
            'Ngày bắt đầu tính phí' => '01/05/2026', 'Ngày kết thúc' => '31/05/2026', 'Kỳ' => '202605', 'Hạn thanh toán' => '25-05-2026',
            'Mã căn hộ' => 'A-0101', 'Mã dịch vụ' => 'PQL', 'Loại giá áp dụng' => '1', 'Số lượng sử dụng' => '1', 'Đơn giá cố định' => '1911000',
        ]]));

        $statement = Statement::where('code', 'BK-202605-A-0101')->firstOrFail();
        $statement->update(['approval_status' => Statement::APPROVAL_PUBLISHED, 'published_at' => now()]);

        $this->stageAndCommit($this->writeXlsx([[
            'Ngày bắt đầu tính phí' => '01/05/2026', 'Ngày kết thúc' => '31/05/2026', 'Kỳ' => '202605', 'Hạn thanh toán' => '25-05-2026',
            'Mã căn hộ' => 'A-0101', 'Mã dịch vụ' => 'XEMAY', 'Loại giá áp dụng' => '1', 'Số lượng sử dụng' => '3', 'Đơn giá cố định' => '120000',
        ]]));

        // Bảng kê đã published KHÔNG được thêm dòng XEMAY.
        $this->assertSame(1, $statement->fresh()->lines()->count(), 'Không thêm dòng vào bảng kê đã phát hành');
        $this->assertNull($statement->fresh()->lines()->where('fee_category', 'vehicle')->first());
    }
}
