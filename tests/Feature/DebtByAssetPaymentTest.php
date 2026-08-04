<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\ApartmentWallet;
use App\Models\ApartmentWalletBucket;
use App\Models\BillingPeriod;
use App\Models\Building;
use App\Models\FeeType;
use App\Models\Project;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\Statement;
use App\Models\StatementLine;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * D6 Slice B — POST /api/v1/resident/debts/by-service/pay (claim-by-asset).
 *
 * Cư dân chọn các tháng còn nợ của MỘT tài sản (chiếc xe) rồi trả trước. Kiểm:
 *  - Tiền phân bổ đúng các dòng đã chọn theo allocationSortKey (nợ cũ trước).
 *  - Tiền thừa earmark vào NGĂN ví theo chiều tài sản (subject), không mất.
 *  - Lần trả sau tự trừ phần dư trong ngăn trước khi cần thêm tiền mới.
 *  - Người không phải cư dân của căn → 403.
 *  - Tiền không đủ → phân bổ theo thứ tự, dòng sau còn nợ, không có dư.
 */
class DebtByAssetPaymentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Dựng một căn hộ có chủ + 1 xe ô tô nợ phí gửi xe các tháng cho trước.
     * Trả về [user, apartment, vehicle, feeType, lines(array ym=>StatementLine)].
     */
    private function scope(string $tag, array $months): array
    {
        $tenant = Tenant::create(['code' => "TEN-$tag", 'name' => "T $tag"]);
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => "PRJ-$tag", 'name' => "P $tag"]);
        $building = Building::create(['tenant_id' => $tenant->id, 'project_id' => $project->id, 'code' => "BLD-$tag", 'name' => "B $tag"]);
        $apartment = Apartment::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => "APT-$tag"]);

        $user = User::create([
            'name' => "Chủ xe $tag", 'email' => "owner-$tag@test.vn",
            'password' => bcrypt('secret'), 'account_type' => 'resident',
        ]);
        $resident = Resident::create([
            'tenant_id' => $tenant->id, 'building_id' => $building->id, 'user_id' => $user->id,
            'code' => "RES-$tag", 'full_name' => "Chủ xe $tag",
        ]);
        ResidentApartmentRelation::create([
            'tenant_id' => $tenant->id, 'resident_id' => $resident->id, 'apartment_id' => $apartment->id,
            'role' => 'owner', 'is_primary' => true,
        ]);

        $oto = FeeType::create([
            'tenant_id' => $tenant->id, 'code' => "OTO-$tag", 'name' => 'Phí gửi ô tô',
            'category' => 'parking', 'unit' => 'per_vehicle', 'is_recurring' => true, 'status' => 'active',
            'is_critical' => false, 'payment_priority' => 100,
        ]);
        $vehicle = Vehicle::create([
            'tenant_id' => $tenant->id, 'building_id' => $building->id, 'apartment_id' => $apartment->id,
            'resident_id' => $resident->id, 'plate_no' => '51K-838888', 'type' => 'car',
            'monthly_fee' => 1_500_000, 'status' => 'active',
        ]);

        $lines = [];
        foreach ($months as $ym) {
            $period = BillingPeriod::create([
                'tenant_id' => $tenant->id, 'building_id' => $building->id,
                'code' => $ym, 'label' => 'Tháng '.$ym, 'period_month' => $ym.'-01',
            ]);
            $statement = Statement::create([
                'tenant_id' => $tenant->id, 'building_id' => $building->id,
                'apartment_id' => $apartment->id, 'billing_period_id' => $period->id,
                'total_amount' => '1500000', 'paid_amount' => 0, 'status' => 'issued',
                'approval_status' => Statement::APPROVAL_PUBLISHED, 'published_at' => now(),
            ]);
            $lines[$ym] = StatementLine::create([
                'statement_id' => $statement->id, 'fee_type' => 'Phí gửi ô tô',
                'fee_type_id' => $oto->id, 'fee_category' => 'parking',
                'subject_type' => $vehicle->getMorphClass(), 'subject_id' => $vehicle->id,
                'service_period_start' => $ym.'-01', 'service_period_end' => $ym.'-28',
                'amount' => 1_500_000, 'paid_amount' => 0, 'status' => 'issued',
            ]);
        }

        return compact('user', 'apartment', 'vehicle', 'oto', 'lines');
    }

    public function test_thanh_toan_xuyen_nhieu_dich_vu_du_vao_quy_chung(): void
    {
        $s = $this->scope('MIX', ['2026-05']);
        $apt = $s['apartment'];
        $vehLine = $s['lines']['2026-05']; // phí xe 1.500.000

        // Thêm dòng phí quản lý (không gắn tài sản) ở kỳ khác.
        $ql = FeeType::create(['tenant_id' => $apt->tenant_id, 'code' => 'QL-MIX', 'name' => 'Phí quản lý', 'category' => 'management', 'status' => 'active', 'payment_priority' => 100]);
        $period = BillingPeriod::create(['tenant_id' => $apt->tenant_id, 'building_id' => $apt->building_id, 'code' => '2026-06', 'label' => 'T6', 'period_month' => '2026-06-01']);
        $stmt = Statement::create(['tenant_id' => $apt->tenant_id, 'building_id' => $apt->building_id, 'apartment_id' => $apt->id, 'billing_period_id' => $period->id, 'total_amount' => '500000', 'paid_amount' => 0, 'status' => 'issued', 'approval_status' => Statement::APPROVAL_PUBLISHED, 'published_at' => now()]);
        $qlLine = StatementLine::create(['statement_id' => $stmt->id, 'fee_type' => 'Phí quản lý', 'fee_type_id' => $ql->id, 'fee_category' => 'management', 'service_period_start' => '2026-06-01', 'amount' => 500000, 'paid_amount' => 0, 'status' => 'issued']);

        Sanctum::actingAs($s['user'], ['resident']);

        // Trả 2.100.000 XUYÊN 2 dịch vụ (xe 1.5tr + quản lý 500k = 2tr), KHÔNG gửi
        // subject_type → phân bổ hết + dư 100k vào QUỸ CHUNG (không earmark tài sản).
        $res = $this->postJson('/api/v1/resident/debts/by-service/pay', [
            'line_ids' => [$vehLine->id, $qlLine->id],
            'amount' => 2_100_000,
        ])->assertOk();

        $data = $res->json('data');
        $this->assertSame(2_000_000, $data['allocated']);
        $this->assertSame(100_000, $data['overflow']);
        $this->assertSame('paid', $vehLine->fresh()->status);
        $this->assertSame('paid', $qlLine->fresh()->status);

        // Dư 100k ở QUỸ CHUNG (wallet.balance), KHÔNG vào ngăn tài sản.
        $wallet = ApartmentWallet::where('apartment_id', $apt->id)->first();
        $this->assertSame('100000.00', (string) $wallet->balance);
        $this->assertSame(0, ApartmentWalletBucket::where('wallet_id', $wallet->id)->whereNotNull('subject_id')->count());
    }

    public function test_phan_bo_dung_line_da_chon_va_tien_thua_vao_ngan_tai_san(): void
    {
        $s = $this->scope('D6B1', ['2026-05', '2026-06', '2026-07']);
        $lines = $s['lines'];
        Sanctum::actingAs($s['user'], ['resident']);

        // Trả 5.000.000 cho 3 tháng nợ (4.500.000) → dư 500.000 vào ngăn xe.
        $res = $this->postJson('/api/v1/resident/debts/by-service/pay', [
            'subject_type' => 'vehicle',
            'subject_id' => $s['vehicle']->id,
            'line_ids' => [$lines['2026-05']->id, $lines['2026-06']->id, $lines['2026-07']->id],
            'amount' => 5_000_000,
        ])->assertOk();

        $data = $res->json('data');
        $this->assertSame(4_500_000, $data['allocated']);
        $this->assertSame(500_000, $data['overflow']);

        // Cả 3 dòng phải hết nợ.
        foreach ($lines as $line) {
            $this->assertSame('1500000.00', (string) $line->fresh()->paid_amount);
            $this->assertSame('paid', $line->fresh()->status);
        }
        // Bảng kê được recompute.
        $this->assertSame('paid', $lines['2026-05']->fresh()->statement->status);

        // Tiền thừa nằm ở NGĂN theo chiều tài sản (đúng chiếc xe), không phải quỹ chung.
        $bucket = ApartmentWalletBucket::where('subject_type', $s['vehicle']->getMorphClass())
            ->where('subject_id', $s['vehicle']->id)->first();
        $this->assertNotNull($bucket, 'có ngăn earmark theo xe');
        $this->assertSame('500000.00', (string) $bucket->balance);
    }

    public function test_lan_tra_sau_tu_tru_tu_ngan_tai_san(): void
    {
        $s = $this->scope('D6B2', ['2026-05', '2026-06']);
        $lines = $s['lines'];
        Sanctum::actingAs($s['user'], ['resident']);

        // Lần 1: trả 2.000.000 cho THÁNG 05 (nợ 1.5tr) → dư 500k vào ngăn xe.
        $this->postJson('/api/v1/resident/debts/by-service/pay', [
            'subject_type' => 'vehicle', 'subject_id' => $s['vehicle']->id,
            'line_ids' => [$lines['2026-05']->id], 'amount' => 2_000_000,
        ])->assertOk();

        $bucket = ApartmentWalletBucket::where('subject_type', $s['vehicle']->getMorphClass())
            ->where('subject_id', $s['vehicle']->id)->first();
        $this->assertSame('500000.00', (string) $bucket->balance);

        // Lần 2: THÁNG 06 (nợ 1.5tr), chỉ trả THÊM 1.000.000 — cộng 500k dư trong
        // ngăn = 1.5tr, đủ trả hết dòng. Ngăn về 0.
        $res = $this->postJson('/api/v1/resident/debts/by-service/pay', [
            'subject_type' => 'vehicle', 'subject_id' => $s['vehicle']->id,
            'line_ids' => [$lines['2026-06']->id], 'amount' => 1_000_000,
        ])->assertOk();

        $data = $res->json('data');
        $this->assertSame(1_500_000, $data['allocated'], 'dùng cả phần dư cũ trong ngăn');
        $this->assertSame(0, $data['overflow']);
        $this->assertSame('1500000.00', (string) $lines['2026-06']->fresh()->paid_amount);
        $this->assertSame('paid', $lines['2026-06']->fresh()->status);
        $this->assertSame('0.00', (string) $bucket->fresh()->balance);
    }

    public function test_nguoi_khong_phai_cu_dan_cua_can_bi_tu_choi_403(): void
    {
        $s = $this->scope('D6B3', ['2026-05']);
        // Người dùng khác, là cư dân của MỘT căn khác → có apartmentIds nhưng không
        // phải căn đang trả.
        $other = $this->scope('D6B3X', ['2026-05']);
        Sanctum::actingAs($other['user'], ['resident']);

        $this->postJson('/api/v1/resident/debts/by-service/pay', [
            'subject_type' => 'vehicle', 'subject_id' => $s['vehicle']->id,
            'line_ids' => [$s['lines']['2026-05']->id], 'amount' => 1_500_000,
        ])->assertStatus(403);

        // Dòng của căn kia KHÔNG bị đụng tới.
        $this->assertSame('0.00', (string) $s['lines']['2026-05']->fresh()->paid_amount);
    }

    public function test_tien_khong_du_phan_bo_theo_thu_tu_dong_sau_con_no(): void
    {
        $s = $this->scope('D6B4', ['2026-05', '2026-06']);
        $lines = $s['lines'];
        Sanctum::actingAs($s['user'], ['resident']);

        // Trả 2.000.000 cho 2 tháng (tổng nợ 3tr): tháng CŨ (05) trả đủ 1.5tr trước,
        // tháng 06 chỉ còn 0.5tr → còn nợ 1tr. Không có dư.
        $res = $this->postJson('/api/v1/resident/debts/by-service/pay', [
            'subject_type' => 'vehicle', 'subject_id' => $s['vehicle']->id,
            'line_ids' => [$lines['2026-06']->id, $lines['2026-05']->id],
            'amount' => 2_000_000,
        ])->assertOk();

        $data = $res->json('data');
        $this->assertSame(2_000_000, $data['allocated']);
        $this->assertSame(0, $data['overflow']);

        $this->assertSame('1500000.00', (string) $lines['2026-05']->fresh()->paid_amount, 'nợ cũ trả trước');
        $this->assertSame('paid', $lines['2026-05']->fresh()->status);
        $this->assertSame('500000.00', (string) $lines['2026-06']->fresh()->paid_amount);
        $this->assertSame('partial', $lines['2026-06']->fresh()->status);

        $bucket = ApartmentWalletBucket::where('subject_type', $s['vehicle']->getMorphClass())
            ->where('subject_id', $s['vehicle']->id)->first();
        $this->assertSame('0.00', (string) $bucket->balance);
    }

    public function test_dong_khong_thuoc_tai_san_da_chon_bi_tu_choi_422(): void
    {
        $s = $this->scope('D6B5', ['2026-05']);
        Sanctum::actingAs($s['user'], ['resident']);

        // Khai subject là đồng hồ nhưng dòng lại gắn xe → lệch tài sản.
        $this->postJson('/api/v1/resident/debts/by-service/pay', [
            'subject_type' => 'meter', 'subject_id' => 999,
            'line_ids' => [$s['lines']['2026-05']->id], 'amount' => 1_500_000,
        ])->assertStatus(422);
    }

    public function test_du_earmark_xe_X_khong_tra_no_xe_Y(): void
    {
        // Đúng nghĩa "ngăn tiền thừa theo tài sản": dư của xe X earmark riêng, KHÔNG
        // được auto-trả nợ xe Y (ngăn khác subject) — tránh rò chéo tài sản.
        $s = $this->scope('ISOX', ['2026-05']);
        $apt = $s['apartment'];
        $xLine = $s['lines']['2026-05'];

        // Xe Y cùng căn, nợ riêng kỳ 2026-06 (1.5tr).
        $vehicleY = Vehicle::create([
            'tenant_id' => $apt->tenant_id, 'building_id' => $apt->building_id, 'apartment_id' => $apt->id,
            'plate_no' => '99Y-000009', 'type' => 'car', 'monthly_fee' => 1_500_000, 'status' => 'active',
        ]);
        $periodY = BillingPeriod::create(['tenant_id' => $apt->tenant_id, 'building_id' => $apt->building_id, 'code' => '2026-06', 'label' => 'T6', 'period_month' => '2026-06-01']);
        $stmtY = Statement::create(['tenant_id' => $apt->tenant_id, 'building_id' => $apt->building_id, 'apartment_id' => $apt->id, 'billing_period_id' => $periodY->id, 'total_amount' => '1500000', 'paid_amount' => 0, 'status' => 'issued', 'approval_status' => Statement::APPROVAL_PUBLISHED, 'published_at' => now()]);
        $yLine = StatementLine::create(['statement_id' => $stmtY->id, 'fee_type' => 'Phí gửi ô tô', 'fee_type_id' => $s['oto']->id, 'fee_category' => 'parking', 'subject_type' => $vehicleY->getMorphClass(), 'subject_id' => $vehicleY->id, 'service_period_start' => '2026-06-01', 'service_period_end' => '2026-06-28', 'amount' => 1_500_000, 'paid_amount' => 0, 'status' => 'issued']);

        Sanctum::actingAs($s['user'], ['resident']);

        // Trả DƯ xe X: 2tr cho line 1.5tr → dư 500k vào NGĂN xe X.
        $this->postJson('/api/v1/resident/debts/by-service/pay', [
            'subject_type' => 'vehicle', 'subject_id' => $s['vehicle']->id,
            'line_ids' => [$xLine->id], 'amount' => 2_000_000,
        ])->assertOk();
        $xBucket = ApartmentWalletBucket::where('subject_type', $s['vehicle']->getMorphClass())->where('subject_id', $s['vehicle']->id)->first();
        $this->assertSame('500000.00', (string) $xBucket->balance);

        // Trả xe Y chỉ 1tr (thiếu 500k) — KHÔNG được lấy từ ngăn xe X.
        $this->postJson('/api/v1/resident/debts/by-service/pay', [
            'subject_type' => 'vehicle', 'subject_id' => $vehicleY->id,
            'line_ids' => [$yLine->id], 'amount' => 1_000_000,
        ])->assertOk();

        $this->assertSame('1000000.00', (string) $yLine->fresh()->paid_amount, 'Y chỉ trả bằng tiền mới nạp');
        $this->assertNotSame('paid', $yLine->fresh()->status, 'Y vẫn còn nợ 500k');
        $this->assertSame('500000.00', (string) $xBucket->fresh()->balance, 'ngăn xe X KHÔNG bị xe Y rút');
    }
}
