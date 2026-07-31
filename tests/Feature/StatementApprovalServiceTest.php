<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\BillingPeriod;
use App\Models\Building;
use App\Models\Project;
use App\Models\Statement;
use App\Models\StatementApproval;
use App\Models\StatementPublishLog;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Billing\StatementApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Phase B2 — Duyệt & phát hành có maker-checker (D1). Trước bản này KHÔNG dòng
 * code nào set `approval_status='published'` (audit 2026-07-31).
 */
class StatementApprovalServiceTest extends TestCase
{
    use RefreshDatabase;

    private function scope(string $tag): array
    {
        $tenant = Tenant::create(['code' => "TEN-SA-$tag", 'name' => "Tenant SA $tag"]);
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => "PRJ-SA-$tag", 'name' => "Project SA $tag"]);
        $building = Building::create(['tenant_id' => $tenant->id, 'project_id' => $project->id, 'code' => "BLD-SA-$tag", 'name' => "Building SA $tag"]);
        $apartment = Apartment::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => "APT-SA-$tag"]);
        $period = BillingPeriod::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => '2026-07', 'label' => 'Tháng 7/2026', 'period_month' => '2026-07-01']);

        return compact('tenant', 'project', 'building', 'apartment', 'period');
    }

    private function statement(array $s, ?int $createdBy = null): Statement
    {
        return Statement::create([
            'tenant_id' => $s['tenant']->id,
            'building_id' => $s['building']->id,
            'billing_period_id' => $s['period']->id,
            'apartment_id' => $s['apartment']->id,
            'code' => 'BK-TEST-'.$s['apartment']->id,
            'total_amount' => 1_000_000,
            'paid_amount' => 0,
            'status' => 'issued',
            'approval_status' => Statement::APPROVAL_PENDING,
            'created_by_user_id' => $createdBy,
        ]);
    }

    private function user(string $tag): User
    {
        return User::create(['name' => "User $tag", 'email' => strtolower($tag).'-sa@test.vn', 'password' => bcrypt('secret')]);
    }

    public function test_duyet_tu_pending_thanh_cong(): void
    {
        $scope = $this->scope('T1');
        $maker = $this->user('T1-maker');
        $checker = $this->user('T1-checker');
        $statement = $this->statement($scope, $maker->id);

        $result = (new StatementApprovalService)->approve($statement, $checker, 'Đã đối chiếu sao kê');

        $this->assertSame(Statement::APPROVAL_APPROVED, $result->approval_status);
        $this->assertSame($checker->id, $result->approved_by_user_id);
        $this->assertSame(1, StatementApproval::where('statement_id', $statement->id)->where('status', 'approved')->count());
    }

    public function test_khong_duyet_duoc_tu_trang_thai_khac_pending(): void
    {
        $scope = $this->scope('T2');
        $checker = $this->user('T2-checker');
        $statement = $this->statement($scope);
        $statement->update(['approval_status' => Statement::APPROVAL_APPROVED]);

        $this->expectException(InvalidArgumentException::class);
        (new StatementApprovalService)->approve($statement, $checker);
    }

    public function test_khong_the_tu_duyet_bang_ke_minh_tao(): void
    {
        $scope = $this->scope('T3');
        $maker = $this->user('T3-maker');
        $statement = $this->statement($scope, $maker->id);

        $this->expectException(InvalidArgumentException::class);
        (new StatementApprovalService)->approve($statement, $maker);
    }

    public function test_bang_ke_cu_khong_co_created_by_van_duyet_duoc(): void
    {
        // created_by_user_id = null (bảng kê tạo trước khi cột này tồn tại) —
        // không có dữ liệu để so sánh thì không giả định là tự duyệt.
        $scope = $this->scope('T4');
        $checker = $this->user('T4-checker');
        $statement = $this->statement($scope, null);

        $result = (new StatementApprovalService)->approve($statement, $checker);
        $this->assertSame(Statement::APPROVAL_APPROVED, $result->approval_status);
    }

    public function test_phat_hanh_tu_approved_ghi_publish_log_va_published_at(): void
    {
        $scope = $this->scope('T5');
        $maker = $this->user('T5-maker');
        $publisher = $this->user('T5-publisher');
        $statement = $this->statement($scope, $maker->id);
        (new StatementApprovalService)->approve($statement, $publisher);

        $result = (new StatementApprovalService)->publish($statement->fresh(), $publisher, 'Phát hành kỳ 07/2026');

        $this->assertSame(Statement::APPROVAL_PUBLISHED, $result->approval_status);
        $this->assertNotNull($result->published_at);
        $this->assertTrue($result->isVisibleToResident());
        $this->assertSame(1, StatementPublishLog::where('billing_period_id', $scope['period']->id)->count());
    }

    public function test_khong_phat_hanh_duoc_tu_pending(): void
    {
        $scope = $this->scope('T6');
        $publisher = $this->user('T6-publisher');
        $statement = $this->statement($scope);

        $this->expectException(InvalidArgumentException::class);
        (new StatementApprovalService)->publish($statement, $publisher);
    }

    public function test_tu_choi_tu_pending_va_tu_approved(): void
    {
        $scope = $this->scope('T7');
        $rejecter = $this->user('T7-rejecter');
        $pending = $this->statement($scope);
        $approved = $this->statement($scope);
        $approved->update(['approval_status' => Statement::APPROVAL_APPROVED]);

        (new StatementApprovalService)->reject($pending, $rejecter, 'Sai số liệu');
        $this->assertSame(Statement::APPROVAL_REJECTED, $pending->fresh()->approval_status);

        (new StatementApprovalService)->reject($approved, $rejecter, 'Phát hiện lỗi sau duyệt');
        $this->assertSame(Statement::APPROVAL_REJECTED, $approved->fresh()->approval_status);
    }

    public function test_khong_tu_choi_duoc_bang_ke_da_phat_hanh(): void
    {
        $scope = $this->scope('T8');
        $publisher = $this->user('T8-publisher');
        $statement = $this->statement($scope);
        (new StatementApprovalService)->approve($statement, $publisher);
        (new StatementApprovalService)->publish($statement->fresh(), $publisher);

        $this->expectException(InvalidArgumentException::class);
        (new StatementApprovalService)->reject($statement->fresh(), $publisher, 'quá muộn');
    }
}
