<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\BillingPeriod;
use App\Models\Building;
use App\Models\Project;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\Statement;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Cư dân CHỈ thấy bảng kê ĐÃ PHÁT HÀNH — quyết định chủ dự án D1 (2026-07-31,
 * `docs/BILLING_OWNER_DECISIONS_20260731.md`): kế toán nhập → trưởng ban QL duyệt →
 * phát hành. Trước bản này `StatementController` KHÔNG lọc gì, và trên DB dev có 130
 * bảng kê `approval_status=pending` — chỉ chưa lộ vì căn của account demo tình cờ toàn
 * bản đã publish.
 *
 * Đây là họ lỗi ĐÃ TỪNG XẢY RA với `events.status` (sự kiện BQL tạo không lên được app),
 * nên khoá lại bằng test thay vì tin vào việc nhớ.
 *
 * Test cả BA đường đọc dùng chung `Statement::scopeVisibleToResident`, vì lỗ hổng thật
 * không nằm ở một endpoint: nếu danh sách lọc mà công nợ tổng không lọc thì cư dân thấy
 * một con số nợ không có hóa đơn nào giải thích.
 */
class ResidentStatementVisibilityTest extends TestCase
{
    use RefreshDatabase;

    /** Dựng (tenant, project, building, apartment, resident, user, period). */
    private function makeResident(string $tag): array
    {
        $tenant = Tenant::create(['code' => "TEN-$tag", 'name' => "Tenant $tag"]);
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => "PRJ-$tag", 'name' => "Project $tag"]);
        $building = Building::create([
            'tenant_id' => $tenant->id, 'project_id' => $project->id,
            'code' => "BLD-$tag", 'name' => "Building $tag",
        ]);
        $apartment = Apartment::create([
            'tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => "APT-$tag",
        ]);
        $user = User::create([
            'name' => "User $tag", 'email' => strtolower($tag).'-vis@test.vn',
            'password' => bcrypt('secret'), 'account_type' => 'resident',
        ]);
        $resident = Resident::create([
            'tenant_id' => $tenant->id, 'building_id' => $building->id, 'user_id' => $user->id,
            'code' => "RES-$tag", 'full_name' => "Resident $tag",
        ]);
        ResidentApartmentRelation::create([
            'tenant_id' => $tenant->id, 'resident_id' => $resident->id, 'apartment_id' => $apartment->id,
            'role' => 'owner', 'is_primary' => true,
        ]);
        $period = BillingPeriod::create([
            'tenant_id' => $tenant->id, 'building_id' => $building->id,
            'code' => '2026-07', 'label' => 'Tháng 7/2026', 'period_month' => '2026-07-01',
            'is_current' => true,
        ]);

        return compact('tenant', 'project', 'building', 'apartment', 'user', 'resident', 'period');
    }

    /**
     * @param  string  $approval  pending|approved|published|rejected
     * @param  bool  $stamped  có `published_at` hay không
     */
    private function statement(
        array $ctx,
        string $code,
        string $approval,
        bool $stamped,
        float $total = 5_000_000,
        float $paid = 0,
    ): Statement {
        return Statement::create([
            'tenant_id' => $ctx['tenant']->id,
            'building_id' => $ctx['building']->id,
            'billing_period_id' => $ctx['period']->id,
            'apartment_id' => $ctx['apartment']->id,
            'code' => $code,
            'total_amount' => $total,
            'paid_amount' => $paid,
            'status' => $paid > 0 ? 'partial' : 'issued',
            'approval_status' => $approval,
            'issued_at' => now()->subDays(2),
            'published_at' => $stamped ? now()->subDay() : null,
            'due_date' => now()->addDays(10)->toDateString(),
        ]);
    }

    public function test_danh_sach_chi_tra_bang_ke_da_phat_hanh(): void
    {
        $ctx = $this->makeResident('V1');

        $published = $this->statement($ctx, 'ST-PUBLISHED', Statement::APPROVAL_PUBLISHED, true);
        $this->statement($ctx, 'ST-PENDING', Statement::APPROVAL_PENDING, false);
        $this->statement($ctx, 'ST-APPROVED-CHUA-PHAT-HANH', Statement::APPROVAL_APPROVED, false);
        $this->statement($ctx, 'ST-REJECTED', Statement::APPROVAL_REJECTED, false);

        Sanctum::actingAs($ctx['user'], ['resident']);

        $res = $this->getJson('/api/v1/resident/statements')->assertOk();

        $ids = collect($res->json('data'))->pluck('id')->all();

        $this->assertSame([$published->id], $ids,
            'chỉ bảng kê published mới được ra danh sách; approved-nhưng-chưa-phát-hành cũng KHÔNG');
    }

    public function test_approval_published_ma_thieu_published_at_thi_van_khong_thay(): void
    {
        // Vì sao khoá riêng ca này: `approval_status` là cột chuỗi, một mass-update lỡ tay
        // (kiểu `MyWork::decide()`) đặt được nó mà không đặt `published_at`. Mốc thời gian
        // là bằng chứng khó giả hơn, nên scope đòi CẢ HAI.
        $ctx = $this->makeResident('V2');
        $half = $this->statement($ctx, 'ST-NUA-VOI', Statement::APPROVAL_PUBLISHED, false);

        Sanctum::actingAs($ctx['user'], ['resident']);

        $this->getJson('/api/v1/resident/statements')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson("/api/v1/resident/statements/{$half->id}")
            ->assertStatus(404);
    }

    public function test_chi_tiet_bang_ke_chua_phat_hanh_tra_404_khong_phai_403(): void
    {
        // 403 vẫn tiết lộ "có một bảng kê ở đây mà bạn không được xem" → cư dân sẽ gọi BQL
        // hỏi về hóa đơn chưa chốt. 404 là câu trả lời đúng.
        $ctx = $this->makeResident('V3');
        $pending = $this->statement($ctx, 'ST-PENDING-DETAIL', Statement::APPROVAL_PENDING, false);

        Sanctum::actingAs($ctx['user'], ['resident']);

        $this->getJson("/api/v1/resident/statements/{$pending->id}")
            ->assertStatus(404);
    }

    public function test_chi_tiet_bang_ke_da_phat_hanh_van_xem_duoc(): void
    {
        $ctx = $this->makeResident('V4');
        $ok = $this->statement($ctx, 'ST-OK-DETAIL', Statement::APPROVAL_PUBLISHED, true);

        Sanctum::actingAs($ctx['user'], ['resident']);

        $this->getJson("/api/v1/resident/statements/{$ok->id}")
            ->assertOk()
            ->assertJsonPath('data.code', 'ST-OK-DETAIL');
    }

    public function test_cong_no_tong_khong_cong_bang_ke_chua_phat_hanh(): void
    {
        // Bất biến quan trọng nhất của bộ test này: con số ở `billing/summary` PHẢI khớp
        // với danh sách. Nếu tổng cộng cả bản pending thì cư dân thấy nợ 8tr mà mở danh
        // sách chỉ có hóa đơn 5tr — lệch kiểu này làm cư dân gọi BQL, tệ hơn cả việc lộ.
        $ctx = $this->makeResident('V5');

        $this->statement($ctx, 'ST-PUB-5TR', Statement::APPROVAL_PUBLISHED, true, 5_000_000);
        $this->statement($ctx, 'ST-PENDING-3TR', Statement::APPROVAL_PENDING, false, 3_000_000);

        Sanctum::actingAs($ctx['user'], ['resident']);

        $this->getJson('/api/v1/resident/billing/summary')
            ->assertOk()
            ->assertJsonPath('data.current_debt', '5000000')
            ->assertJsonPath('data.unpaid_statement_count', 1);
    }

    public function test_xu_huong_khong_tinh_bang_ke_chua_phat_hanh(): void
    {
        $ctx = $this->makeResident('V6');

        $this->statement($ctx, 'ST-TREND-PUB', Statement::APPROVAL_PUBLISHED, true, 4_000_000);
        $this->statement($ctx, 'ST-TREND-PENDING', Statement::APPROVAL_PENDING, false, 9_000_000);

        Sanctum::actingAs($ctx['user'], ['resident']);

        $res = $this->getJson('/api/v1/resident/billing/summary/trend?months=6')->assertOk();

        $bars = $res->json('data.bars');
        $this->assertCount(1, $bars, 'một kỳ phí → một cột');
        $this->assertSame('4000000', $bars[0]['value'],
            'cột xu hướng chỉ cộng bảng kê đã phát hành, không nhảy trước khi BQL chốt');
    }

    public function test_bang_ke_can_ho_khac_van_khong_thay_du_da_phat_hanh(): void
    {
        // Hồi quy cho cách ly căn hộ — bản sửa D1 thêm điều kiện lọc, không được làm hỏng
        // điều kiện scope theo căn đã có.
        $mine = $this->makeResident('V7');
        $other = $this->makeResident('V8');

        $theirs = $this->statement($other, 'ST-CUA-NGUOI-KHAC', Statement::APPROVAL_PUBLISHED, true);

        Sanctum::actingAs($mine['user'], ['resident']);

        $this->getJson('/api/v1/resident/statements')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson("/api/v1/resident/statements/{$theirs->id}")
            ->assertStatus(404);
    }
}
