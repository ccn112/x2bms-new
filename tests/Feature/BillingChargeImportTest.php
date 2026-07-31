<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\BillingPeriod;
use App\Models\Building;
use App\Models\FeeType;
use App\Models\ImportBatch;
use App\Models\Meter;
use App\Models\Project;
use App\Models\Statement;
use App\Models\StatementLine;
use App\Models\Tenant;
use App\Models\Vehicle;
use App\Support\Import\Profiles\BillingChargeImportProfile;
use App\Support\Import\StagingImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Tests\TestCase;

/**
 * Phase B1 — Billing Charge Import (kế toán nhập khoản phí), theo
 * `docs/BILLING_IMPORT_SPEC_20260731.md` §7 (kiểm thử tối thiểu).
 */
class BillingChargeImportTest extends TestCase
{
    use RefreshDatabase;

    private function makeScope(string $tag): array
    {
        $tenant = Tenant::create(['code' => "TEN-BI-$tag", 'name' => "Tenant BI $tag"]);
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => "PRJ-BI-$tag", 'name' => "Project BI $tag"]);
        $building = Building::create([
            'tenant_id' => $tenant->id, 'project_id' => $project->id,
            'code' => "BLD-BI-$tag", 'name' => "Building BI $tag",
        ]);
        $period = BillingPeriod::create([
            'tenant_id' => $tenant->id, 'building_id' => $building->id,
            'code' => '2026-07', 'label' => 'Tháng 7/2026', 'period_month' => '2026-07-01',
            'due_date' => '2026-07-15', 'is_current' => true,
        ]);

        FeeType::create(['tenant_id' => $tenant->id, 'code' => 'QL', 'name' => 'Phí quản lý', 'category' => 'management', 'unit' => 'per_sqm']);
        FeeType::create(['tenant_id' => $tenant->id, 'code' => 'OTO', 'name' => 'Phí gửi ô tô', 'category' => 'parking', 'unit' => 'per_vehicle']);
        FeeType::create(['tenant_id' => $tenant->id, 'code' => 'DIEN', 'name' => 'Tiền điện', 'category' => 'utility', 'unit' => 'per_unit']);
        FeeType::create(['tenant_id' => $tenant->id, 'code' => 'NUOC', 'name' => 'Phí nước sinh hoạt', 'category' => 'utility', 'unit' => 'per_m3']);

        return compact('tenant', 'project', 'building', 'period');
    }

    private function apartment(array $scope, string $code): Apartment
    {
        return Apartment::create(['tenant_id' => $scope['tenant']->id, 'building_id' => $scope['building']->id, 'code' => $code]);
    }

    /** @param  array<string,string>  $overrides */
    private function row(array $overrides = []): array
    {
        return array_merge([
            'Mã căn hộ' => 'A-01',
            'Kỳ phí' => '2026-07',
            'Mã loại phí' => 'QL',
            'Tài sản' => '',
            'Tên khoản hiện cho cư dân' => '',
            'Kỳ dịch vụ từ' => '',
            'Kỳ dịch vụ đến' => '',
            'Chỉ số đầu' => '',
            'Chỉ số cuối' => '',
            'Số lượng' => '',
            'Đơn giá' => '',
            'Thành tiền' => '1000000',
            'VAT %' => '',
            'Miễn giảm' => '',
            'Hạn thanh toán' => '',
            'Ghi chú' => '',
        ], $overrides);
    }

    /** @param  list<array<string,string>>  $rows */
    private function writeXlsx(array $rows): string
    {
        $dir = storage_path('app/tmp');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $path = $dir.'/test_billing_import_'.uniqid().'.xlsx';

        $writer = SimpleExcelWriter::create($path);
        foreach ($rows as $r) {
            $writer->addRow($r);
        }
        $writer->close();

        return $path;
    }

    /** @param  list<array<string,string>>  $rows */
    private function stageAndCommit(array $rows, array $scope): ImportBatch
    {
        $context = ['tenant_id' => $scope['tenant']->id, 'building_id' => $scope['building']->id, 'user_id' => null];
        $importer = app(StagingImporter::class);
        $batch = $importer->stage($this->writeXlsx($rows), 'test.xlsx', new BillingChargeImportProfile, $context);
        $importer->commit($batch->fresh(), new BillingChargeImportProfile, $context);

        return $batch->fresh();
    }

    public function test_import_2_lan_cung_file_khong_nhan_doi(): void
    {
        $scope = $this->makeScope('T1');
        $this->apartment($scope, 'A-01');
        $rows = [$this->row()];

        $batch1 = $this->stageAndCommit($rows, $scope);
        $this->assertSame(1, StatementLine::count());
        $this->assertSame(1, (int) $batch1->valid_rows);

        $this->stageAndCommit($rows, $scope);
        $this->assertSame(1, StatementLine::count(), 'import lại cùng file phải CẬP NHẬT, không nhân đôi dòng');

        $statement = Statement::sole();
        $this->assertSame('1000000.00', (string) $statement->total_amount);
    }

    public function test_so_le_khac_0_bi_chan(): void
    {
        $scope = $this->makeScope('T2');
        $this->apartment($scope, 'A-01');

        $batch = $this->stageAndCommit([$this->row(['Thành tiền' => '518000.50'])], $scope);

        $this->assertSame(0, (int) $batch->valid_rows);
        $this->assertSame(0, StatementLine::count());
        $row = $batch->rows()->first();
        $messages = collect($row->validation_errors)->pluck('message')->implode(' | ');
        $this->assertStringContainsString('không có số lẻ', $messages);
    }

    public function test_dinh_dang_hang_nghin_khac_nhau_ra_cung_mot_ket_qua(): void
    {
        $scope = $this->makeScope('T3');
        $this->apartment($scope, 'A-01');
        $this->apartment($scope, 'A-02');

        $this->stageAndCommit([
            $this->row(['Mã căn hộ' => 'A-01', 'Thành tiền' => '518.000']),
            $this->row(['Mã căn hộ' => 'A-02', 'Thành tiền' => '518,000']),
        ], $scope);

        $amounts = StatementLine::query()->pluck('amount')->map(fn ($v) => (string) $v)->unique()->values();
        $this->assertCount(1, $amounts);
        $this->assertSame('518000.00', $amounts[0]);
    }

    public function test_bks_khong_thuoc_can_bi_chan(): void
    {
        $scope = $this->makeScope('T4');
        $apartment = $this->apartment($scope, 'A-01');
        Vehicle::create(['tenant_id' => $scope['tenant']->id, 'building_id' => $scope['building']->id, 'apartment_id' => $apartment->id, 'plate_no' => '51K-111111', 'type' => 'car']);

        $batch = $this->stageAndCommit([
            $this->row(['Mã loại phí' => 'OTO', 'Tài sản' => '51K-999999', 'Thành tiền' => '1200000']),
        ], $scope);

        $this->assertSame(0, (int) $batch->valid_rows);
        $this->assertSame(0, StatementLine::count());
        $messages = collect($batch->rows()->first()->validation_errors)->pluck('message')->implode(' | ');
        $this->assertStringContainsString('không thuộc căn', $messages);
    }

    public function test_can_2_dong_ho_dien_thieu_cot_tai_san_bi_chan(): void
    {
        $scope = $this->makeScope('T5');
        $apartment = $this->apartment($scope, 'A-01');
        Meter::create(['tenant_id' => $scope['tenant']->id, 'apartment_id' => $apartment->id, 'code' => 'DH-A01-E1', 'type' => 'electric']);
        Meter::create(['tenant_id' => $scope['tenant']->id, 'apartment_id' => $apartment->id, 'code' => 'DH-A01-E2', 'type' => 'electric']);

        $batch = $this->stageAndCommit([
            $this->row(['Mã loại phí' => 'DIEN', 'Tài sản' => '', 'Thành tiền' => '500000']),
        ], $scope);

        $this->assertSame(0, (int) $batch->valid_rows);
        $messages = collect($batch->rows()->first()->validation_errors)->pluck('message')->implode(' | ');
        $this->assertStringContainsString('2 đồng hồ điện', $messages);
    }

    public function test_no_cu_don_ky_luu_dung_khong_gop_voi_dong_ky_hien_tai(): void
    {
        $scope = $this->makeScope('T6');
        $apartment = $this->apartment($scope, 'A-01');
        Vehicle::create(['tenant_id' => $scope['tenant']->id, 'building_id' => $scope['building']->id, 'apartment_id' => $apartment->id, 'plate_no' => '51K-838888', 'type' => 'car']);

        $this->stageAndCommit([
            $this->row(['Mã loại phí' => 'OTO', 'Tài sản' => '51K-838888', 'Thành tiền' => '1200000', 'Kỳ dịch vụ từ' => '2026-07-01', 'Kỳ dịch vụ đến' => '2026-07-31']),
            $this->row(['Mã loại phí' => 'OTO', 'Tài sản' => '51K-838888', 'Thành tiền' => '1200000', 'Kỳ dịch vụ từ' => '2026-06-01', 'Kỳ dịch vụ đến' => '2026-06-30', 'Ghi chú' => 'Nợ kỳ trước dồn sang']),
        ], $scope);

        $this->assertSame(2, StatementLine::count(), 'nợ cũ dồn kỳ phải là DÒNG RIÊNG, không gộp với dòng kỳ hiện tại');
        $periods = StatementLine::query()->pluck('service_period_start')->sort()->values();
        $this->assertSame('2026-06-01', (string) $periods[0]);
        $this->assertSame('2026-07-01', (string) $periods[1]);

        $statement = Statement::sole();
        $this->assertSame('2400000.00', (string) $statement->total_amount);
    }

    public function test_bang_ke_sinh_ra_luon_pending_khong_lo_cho_cu_dan(): void
    {
        $scope = $this->makeScope('T7');
        $this->apartment($scope, 'A-01');

        $this->stageAndCommit([$this->row()], $scope);

        $statement = Statement::sole();
        $this->assertSame(Statement::APPROVAL_PENDING, $statement->approval_status);
        $this->assertNull($statement->published_at);
        $this->assertFalse($statement->isVisibleToResident());
    }

    public function test_total_amount_la_tong_cac_dong_sau_moi_lan_import(): void
    {
        $scope = $this->makeScope('T8');
        $this->apartment($scope, 'A-01');

        $this->stageAndCommit([
            $this->row(['Mã loại phí' => 'QL', 'Thành tiền' => '1000000']),
            $this->row(['Mã loại phí' => 'NUOC', 'Thành tiền' => '250000']),
        ], $scope);

        $statement = Statement::sole();
        $this->assertSame('1250000.00', (string) $statement->total_amount);
        $this->assertEquals($statement->lines()->sum('amount'), (float) $statement->total_amount);
    }

    public function test_hoan_tac_lo_khi_pending_sach_khi_published_bi_tu_choi(): void
    {
        $scope = $this->makeScope('T9');
        $this->apartment($scope, 'A-01');

        $batchPending = $this->stageAndCommit([$this->row()], $scope);
        $profile = new BillingChargeImportProfile;

        $deleted = $profile->rollbackBatch($batchPending);
        $this->assertSame(1, $deleted);
        $this->assertSame(0, StatementLine::count());
        $this->assertNotNull($batchPending->fresh()->rolled_back_at);

        // Kịch bản 2: bảng kê đã published thì KHÔNG được hoàn tác.
        $this->apartment($scope, 'A-02');
        $batchPublished = $this->stageAndCommit([$this->row(['Mã căn hộ' => 'A-02'])], $scope);
        Statement::query()->where('apartment_id', Apartment::where('code', 'A-02')->sole()->id)
            ->update(['approval_status' => Statement::APPROVAL_PUBLISHED, 'published_at' => now()]);

        $this->expectException(\RuntimeException::class);
        $profile->rollbackBatch($batchPublished);
    }
}
