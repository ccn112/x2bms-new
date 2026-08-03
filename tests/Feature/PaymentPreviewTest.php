<?php

namespace Tests\Feature;

use App\Models\Apartment;
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * P4 — Preview phân bổ trước thanh toán (read-only). Ví dụ canonical:
 * chọn 2 charge (còn nợ 100k + 800k), trả 900k → phân bổ 100k + 800k, thừa 0.
 */
class PaymentPreviewTest extends TestCase
{
    use RefreshDatabase;

    private function makeResident(string $tag): array
    {
        $tenant = Tenant::create(['code' => "TEN-$tag", 'name' => "T$tag"]);
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => "PRJ-$tag", 'name' => 'P']);
        $building = Building::create(['tenant_id' => $tenant->id, 'project_id' => $project->id, 'code' => "BLD-$tag", 'name' => 'B']);
        $apartment = Apartment::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => "APT-$tag"]);
        $period = BillingPeriod::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => '2026-05', 'label' => 'T5', 'period_month' => '2026-05-01']);
        $feeType = FeeType::create(['tenant_id' => $tenant->id, 'code' => "QL-$tag", 'name' => 'Phí quản lý', 'category' => 'management', 'is_critical' => false, 'payment_priority' => 100]);
        $user = User::create(['name' => "U$tag", 'email' => strtolower($tag).'-pv@test.vn', 'password' => bcrypt('x'), 'account_type' => 'resident']);
        $resident = Resident::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'user_id' => $user->id, 'code' => "RES-$tag", 'full_name' => 'CD']);
        ResidentApartmentRelation::create(['tenant_id' => $tenant->id, 'resident_id' => $resident->id, 'apartment_id' => $apartment->id, 'role' => 'owner', 'is_primary' => true]);

        return compact('tenant', 'building', 'apartment', 'period', 'feeType', 'user');
    }

    private function line(array $ctx, float $amount, float $paid): StatementLine
    {
        $st = Statement::create([
            'tenant_id' => $ctx['tenant']->id, 'building_id' => $ctx['building']->id, 'billing_period_id' => $ctx['period']->id,
            'apartment_id' => $ctx['apartment']->id, 'code' => 'BK-'.uniqid(), 'total_amount' => $amount, 'paid_amount' => $paid,
            'status' => 'issued', 'approval_status' => Statement::APPROVAL_PENDING,
        ]);

        return StatementLine::create([
            'statement_id' => $st->id, 'fee_type_id' => $ctx['feeType']->id, 'fee_type' => 'Phí quản lý',
            'fee_category' => 'management', 'amount' => $amount, 'paid_amount' => $paid, 'status' => 'issued', 'service_period_start' => '2026-05-01',
        ]);
    }

    public function test_preview_900k_ra_100k_va_800k_thua_0(): void
    {
        $ctx = $this->makeResident('PV1');
        $a = $this->line($ctx, 500_000, 400_000); // còn nợ 100k
        $b = $this->line($ctx, 800_000, 0);        // còn nợ 800k

        Sanctum::actingAs($ctx['user'], ['resident']);
        $res = $this->postJson('/api/v1/resident/billing/payment-preview', [
            'line_ids' => [$a->id, $b->id], 'amount' => 900_000,
        ])->assertOk();

        $this->assertSame(900000, $res->json('data.allocated'));
        $this->assertSame(0, $res->json('data.unallocated'));
        $byLine = collect($res->json('data.lines'))->keyBy('line_id');
        $this->assertSame(100000, $byLine[(string) $a->id]['allocated']);
        $this->assertSame(800000, $byLine[(string) $b->id]['allocated']);
    }

    public function test_tra_du_thi_bao_phan_thua_khong_phan_bo(): void
    {
        $ctx = $this->makeResident('PV2');
        $a = $this->line($ctx, 500_000, 0); // còn nợ 500k

        Sanctum::actingAs($ctx['user'], ['resident']);
        $res = $this->postJson('/api/v1/resident/billing/payment-preview', [
            'line_ids' => [$a->id], 'amount' => 700_000,
        ])->assertOk();

        $this->assertSame(500000, $res->json('data.allocated'));
        $this->assertSame(200000, $res->json('data.unallocated'), 'phần thừa không phân bổ vượt phần còn nợ');
    }

    public function test_dong_cua_can_khac_bi_chan_403(): void
    {
        $ctx = $this->makeResident('PV3');
        $other = $this->makeResident('PV4');
        $foreign = $this->line($other, 500_000, 0);

        Sanctum::actingAs($ctx['user'], ['resident']);
        $this->postJson('/api/v1/resident/billing/payment-preview', [
            'line_ids' => [$foreign->id], 'amount' => 100_000,
        ])->assertStatus(403);
    }
}
