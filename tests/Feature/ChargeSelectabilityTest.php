<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\BillingPeriod;
use App\Models\Building;
use App\Models\FeeType;
use App\Models\LiabilityPeriod;
use App\Models\Project;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\Statement;
use App\Models\StatementLine;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * A1 — Cờ non-selectable trên khoản phí (ChargeSelectability).
 *
 * Một khoản KHÔNG được cư dân tick trả khi:
 *  - `paid`         : đã trả hết (chỉ gặp ở chi tiết bảng kê — cây D6 lọc outstanding).
 *  - `former_owner` : service_period rơi vào kỳ chịu-trách-nhiệm role=former_owner.
 *
 * Ba mệnh đề phải đúng cùng lúc:
 *  - Cây D6 đánh `selectable=false, reason=former_owner` cho dòng của chủ cũ,
 *    `selectable=true` cho dòng hiện hành.
 *  - `POST debts/by-service/pay` TỪ CHỐI (422) khi client vẫn gửi dòng chủ cũ —
 *    không tin mỗi UI.
 *  - Chi tiết bảng kê đánh `reason=paid` cho dòng đã trả đủ.
 */
class ChargeSelectabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_dong_chu_cu_bi_khoa_tick_va_pay_tu_choi(): void
    {
        [$tenant, $building, $apartment, $user] = $this->seedResident('CS1');

        $ql = FeeType::create([
            'tenant_id' => $tenant->id, 'code' => 'QL', 'name' => 'Phí quản lý',
            'category' => 'management', 'status' => 'active',
        ]);

        // Nợ kỳ 03/2026 — RƠI vào kỳ chủ cũ (liable_to = 2026-04-30).
        $formerStmt = $this->publishedStatement($tenant, $building, $apartment, '2026-03');
        $formerLine = StatementLine::create([
            'statement_id' => $formerStmt->id, 'fee_type' => 'Phí quản lý',
            'fee_type_id' => $ql->id, 'fee_category' => 'management',
            'service_period_start' => '2026-03-01', 'amount' => 500_000, 'paid_amount' => 0,
        ]);

        // Nợ kỳ 07/2026 — SAU khi chuyển chủ → của chủ hiện hành, trả được.
        $currentStmt = $this->publishedStatement($tenant, $building, $apartment, '2026-07');
        $currentLine = StatementLine::create([
            'statement_id' => $currentStmt->id, 'fee_type' => 'Phí quản lý',
            'fee_type_id' => $ql->id, 'fee_category' => 'management',
            'service_period_start' => '2026-07-01', 'amount' => 500_000, 'paid_amount' => 0,
        ]);

        LiabilityPeriod::create([
            'tenant_id' => $tenant->id, 'apartment_id' => $apartment->id,
            'role' => 'former_owner', 'liable_from' => '2026-01-01', 'liable_to' => '2026-04-30',
            'scope' => null, 'source' => 'test',
        ]);

        Sanctum::actingAs($user, ['resident']);

        $months = collect($this->getJson('/api/v1/resident/debts/by-service')->assertOk()->json('data.families'))
            ->flatMap(fn ($f) => collect($f['fee_types'])->flatMap(fn ($ft) => collect($ft['subjects'])->flatMap(fn ($s) => $s['months'])))
            ->keyBy('line_id');

        $former = $months[(string) $formerLine->id];
        $this->assertFalse($former['selectable'], 'dòng chủ cũ bị khoá tick');
        $this->assertSame('former_owner', $former['non_selectable_reason']);
        $this->assertSame('Nợ của chủ cũ', $former['non_selectable_label']);

        $current = $months[(string) $currentLine->id];
        $this->assertTrue($current['selectable'], 'dòng chủ hiện hành trả được');
        $this->assertNull($current['non_selectable_reason']);

        // Server TỪ CHỐI khi client cố gửi dòng chủ cũ để trả.
        $this->postJson('/api/v1/resident/debts/by-service/pay', [
            'line_ids' => [$formerLine->id], 'amount' => 500_000,
        ])->assertStatus(422)->assertJsonPath('error.code', 'charge_not_selectable');

        // Còn dòng chủ hiện hành thì trả được (không bị chặn nhầm).
        $this->postJson('/api/v1/resident/debts/by-service/pay', [
            'line_ids' => [$currentLine->id], 'amount' => 500_000,
        ])->assertOk();
    }

    public function test_dong_da_tra_du_danh_dau_paid_o_chi_tiet_bang_ke(): void
    {
        [$tenant, $building, $apartment, $user] = $this->seedResident('CS2');

        $ql = FeeType::create([
            'tenant_id' => $tenant->id, 'code' => 'QL', 'name' => 'Phí quản lý',
            'category' => 'management', 'status' => 'active',
        ]);
        $stmt = $this->publishedStatement($tenant, $building, $apartment, '2026-07');
        $paid = StatementLine::create([
            'statement_id' => $stmt->id, 'fee_type' => 'Phí quản lý',
            'fee_type_id' => $ql->id, 'fee_category' => 'management',
            'service_period_start' => '2026-07-01', 'amount' => 500_000, 'paid_amount' => 500_000,
        ]);
        $unpaid = StatementLine::create([
            'statement_id' => $stmt->id, 'fee_type' => 'Phí quản lý',
            'fee_type_id' => $ql->id, 'fee_category' => 'management',
            'service_period_start' => '2026-07-01', 'amount' => 300_000, 'paid_amount' => 0,
        ]);

        Sanctum::actingAs($user, ['resident']);

        $lines = collect($this->getJson("/api/v1/resident/statements/{$stmt->id}")->assertOk()->json('data.families'))
            ->flatMap(fn ($f) => collect($f['fee_definitions'])->flatMap(fn ($d) => $d['lines']))
            ->keyBy('id');

        $this->assertFalse($lines[$paid->id]['selectable']);
        $this->assertSame('paid', $lines[$paid->id]['non_selectable_reason']);
        $this->assertTrue($lines[$unpaid->id]['selectable']);
    }

    /** @return array{0:Tenant,1:Building,2:Apartment,3:User} */
    private function seedResident(string $suffix): array
    {
        $tenant = Tenant::create(['code' => "TEN-{$suffix}", 'name' => 'T']);
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => "PRJ-{$suffix}", 'name' => 'P']);
        $building = Building::create(['tenant_id' => $tenant->id, 'project_id' => $project->id, 'code' => "BLD-{$suffix}", 'name' => 'B']);
        $apartment = Apartment::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => "APT-{$suffix}"]);
        $user = User::create([
            'name' => 'CD', 'email' => strtolower($suffix).'@test.vn',
            'password' => bcrypt('x'), 'account_type' => 'resident',
        ]);
        $resident = Resident::create([
            'tenant_id' => $tenant->id, 'building_id' => $building->id, 'user_id' => $user->id,
            'code' => "RES-{$suffix}", 'full_name' => 'CD',
        ]);
        ResidentApartmentRelation::create([
            'tenant_id' => $tenant->id, 'resident_id' => $resident->id, 'apartment_id' => $apartment->id,
            'role' => 'owner', 'is_primary' => true,
        ]);

        return [$tenant, $building, $apartment, $user];
    }

    private function publishedStatement(Tenant $tenant, Building $building, Apartment $apartment, string $ym): Statement
    {
        $period = BillingPeriod::create([
            'tenant_id' => $tenant->id, 'building_id' => $building->id,
            'code' => $ym, 'label' => 'Tháng '.$ym, 'period_month' => $ym.'-01',
        ]);

        return Statement::create([
            'tenant_id' => $tenant->id, 'building_id' => $building->id,
            'apartment_id' => $apartment->id, 'billing_period_id' => $period->id,
            'total_amount' => '0', 'paid_amount' => 0, 'status' => 'issued',
            'approval_status' => Statement::APPROVAL_PUBLISHED, 'published_at' => now(),
        ]);
    }
}
