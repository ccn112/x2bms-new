<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\BillingPeriod;
use App\Models\Building;
use App\Models\Project;
use App\Models\Statement;
use App\Models\StatementLine;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Billing\StatementApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * P3 — Phát hành chụp snapshot BẤT BIẾN (D15). Bảng kê đã phát hành không được đổi;
 * nếu dữ liệu sống lệch snapshot, command audit phải bắt được.
 */
class StatementPublishSnapshotTest extends TestCase
{
    use RefreshDatabase;

    private function makePendingStatement(): array
    {
        $tenant = Tenant::create(['code' => 'TEN-SNP', 'name' => 'SNP']);
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => 'PRJ-SNP', 'name' => 'P']);
        $building = Building::create(['tenant_id' => $tenant->id, 'project_id' => $project->id, 'code' => 'BLD-SNP', 'name' => 'B']);
        $apartment = Apartment::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => 'A-SNP']);
        $period = BillingPeriod::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => '2026-05', 'label' => 'T5', 'period_month' => '2026-05-01']);
        $maker = User::create(['name' => 'Kế toán', 'email' => 'maker-snp@test.vn', 'password' => bcrypt('x'), 'account_type' => 'admin']);
        $checker = User::create(['name' => 'Trưởng ban', 'email' => 'checker-snp@test.vn', 'password' => bcrypt('x'), 'account_type' => 'admin']);

        $statement = Statement::create([
            'tenant_id' => $tenant->id, 'building_id' => $building->id, 'billing_period_id' => $period->id,
            'apartment_id' => $apartment->id, 'code' => 'BK-2026-05-A-SNP', 'total_amount' => 0, 'paid_amount' => 0,
            'status' => 'issued', 'approval_status' => Statement::APPROVAL_PENDING, 'created_by_user_id' => $maker->id,
        ]);
        foreach ([['Phí quản lý', 'management', 1911000], ['Phí nước', 'water', 24150]] as [$name, $cat, $amt]) {
            StatementLine::create([
                'statement_id' => $statement->id, 'fee_type' => $name, 'fee_category' => $cat,
                'amount' => $amt, 'paid_amount' => 0, 'status' => 'issued', 'service_period_start' => '2026-05-01',
            ]);
        }
        $statement->update(['total_amount' => $statement->lines()->sum('amount')]);

        return compact('statement', 'maker', 'checker');
    }

    public function test_phat_hanh_chup_snapshot_dung(): void
    {
        ['statement' => $s, 'maker' => $maker, 'checker' => $checker] = $this->makePendingStatement();
        $svc = new StatementApprovalService;

        $svc->approve($s, $checker);
        $svc->publish($s->fresh(), $checker);

        $s->refresh();
        $this->assertSame(Statement::APPROVAL_PUBLISHED, $s->approval_status);
        $this->assertNotNull($s->snapshot_checksum);
        $this->assertNotNull($s->snapshot_at);
        $this->assertSame('1935150', (string) $s->snapshot['total_amount']);
        $this->assertSame(2, $s->snapshot['line_count']);
    }

    public function test_snapshot_bat_bien_command_bat_duoc_drift(): void
    {
        ['statement' => $s, 'checker' => $checker] = $this->makePendingStatement();
        $svc = new StatementApprovalService;
        $svc->approve($s, $checker);
        $svc->publish($s->fresh(), $checker);

        $originalTotal = $s->fresh()->snapshot['total_amount'];

        // Giả lập DỮ LIỆU SỐNG bị đổi sau phát hành (đường ghi tay lỗi).
        $s->lines()->first()->forceFill(['amount' => 9_999_999])->save();

        // Snapshot KHÔNG đổi (bất biến).
        $this->assertSame($originalTotal, (string) $s->fresh()->snapshot['total_amount']);

        // Command audit phải trả FAILURE vì phát hiện lệch.
        $code = Artisan::call('billing:verify-published-snapshots');
        $this->assertSame(1, $code, 'verify phải FAIL khi bảng kê published bị đổi');
    }

    public function test_khong_drift_thi_verify_pass(): void
    {
        ['statement' => $s, 'checker' => $checker] = $this->makePendingStatement();
        $svc = new StatementApprovalService;
        $svc->approve($s, $checker);
        $svc->publish($s->fresh(), $checker);

        $this->assertSame(0, Artisan::call('billing:verify-published-snapshots'));
    }
}
