<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\Attachment;
use App\Models\BillingPeriod;
use App\Models\Building;
use App\Models\FeeType;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Project;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\Statement;
use App\Models\StatementLine;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Billing\ResidentPaymentClaimReviewer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * D10 — cư dân chọn TỪNG DÒNG PHÍ khi khai thanh toán. Claim lưu line_items;
 * BQL duyệt phân bổ ĐÚNG dòng chọn (không quét ưu tiên D4). Test đi vào từng con
 * số: dòng nào nhận bao nhiêu, dòng không chọn phải nguyên vẹn.
 */
class FeePaymentLineItemsTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string,mixed> */
    private function scene(string $tag): array
    {
        $tenant = Tenant::create(['code' => "TEN-$tag", 'name' => "Tenant $tag"]);
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => "PRJ-$tag", 'name' => "Project $tag"]);
        $building = Building::create([
            'tenant_id' => $tenant->id, 'project_id' => $project->id, 'code' => "BLD-$tag", 'name' => "B $tag",
        ]);
        $apartment = Apartment::create([
            'tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => "APT-$tag", 'area_sqm' => 80,
        ]);
        $user = User::create([
            'name' => "U $tag", 'email' => strtolower($tag).'-d10@test.vn',
            'password' => bcrypt('secret'), 'account_type' => 'resident', 'tenant_id' => $tenant->id,
        ]);
        $resident = Resident::create([
            'tenant_id' => $tenant->id, 'building_id' => $building->id, 'user_id' => $user->id,
            'code' => "RES-$tag", 'full_name' => "R $tag",
        ]);
        ResidentApartmentRelation::create([
            'tenant_id' => $tenant->id, 'resident_id' => $resident->id, 'apartment_id' => $apartment->id,
            'role' => 'owner', 'is_primary' => true,
        ]);
        $period = BillingPeriod::create([
            'tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => '2026-07',
            'label' => 'T7', 'period_month' => '2026-07-01', 'is_current' => true,
        ]);
        $ql = FeeType::create(['tenant_id' => $tenant->id, 'code' => 'QL', 'name' => 'Phí quản lý']);
        $rac = FeeType::create(['tenant_id' => $tenant->id, 'code' => 'RAC', 'name' => 'Phí vệ sinh']);
        $statement = Statement::create([
            'tenant_id' => $tenant->id, 'building_id' => $building->id, 'billing_period_id' => $period->id,
            'apartment_id' => $apartment->id, 'code' => "BK-$tag", 'total_amount' => 1_200_000,
            'paid_amount' => 0, 'status' => 'issued', 'approval_status' => 'published', 'published_at' => now(),
        ]);
        $lineQl = StatementLine::create([
            'statement_id' => $statement->id, 'fee_type_id' => $ql->id, 'fee_type' => 'Phí quản lý',
            'quantity' => 1, 'unit_price' => 1_000_000, 'amount' => 1_000_000, 'paid_amount' => 0,
        ]);
        $lineRac = StatementLine::create([
            'statement_id' => $statement->id, 'fee_type_id' => $rac->id, 'fee_type' => 'Phí vệ sinh',
            'quantity' => 1, 'unit_price' => 200_000, 'amount' => 200_000, 'paid_amount' => 0,
        ]);
        $att = Attachment::create([
            'tenant_id' => $tenant->id, 'disk' => 'public', 'path' => "u/$tag.jpg",
            'file_name' => 'ct.jpg', 'mime_type' => 'image/jpeg', 'size' => 100, 'uploaded_by' => $user->id,
        ]);

        return compact('user', 'statement', 'lineQl', 'lineRac', 'att');
    }

    private function claimBody(array $s, array $lineItems, int $amount): array
    {
        return [
            'statement_id' => $s['statement']->id,
            'amount' => $amount,
            'paid_at' => now()->subHour()->toIso8601String(),
            'attachment_ids' => [$s['att']->id],
            'line_items' => $lineItems,
        ];
    }

    public function test_duyet_phan_bo_dung_dong_chon_khong_cham_dong_khac(): void
    {
        $s = $this->scene('D1');
        Sanctum::actingAs($s['user'], ['resident']);

        // Chỉ trả dòng QL (1.000.000), KHÔNG trả RAC.
        $res = $this->postJson('/api/v1/resident/payments/claim',
            $this->claimBody($s, [
                ['statement_line_id' => $s['lineQl']->id, 'amount' => 1_000_000],
            ], 1_000_000))->assertCreated();

        $payment = Payment::findOrFail($res->json('data.id'));
        $this->assertCount(1, $payment->claimed_line_items);

        (new ResidentPaymentClaimReviewer())->approve($payment);

        // QL nhận đủ 1.000.000; RAC KHÔNG bị chạm.
        $this->assertEqualsWithDelta(1_000_000, (float) $s['lineQl']->refresh()->paid_amount, 1);
        $this->assertEqualsWithDelta(0, (float) $s['lineRac']->refresh()->paid_amount, 1);
        $this->assertSame(1, PaymentAllocation::where('payment_id', $payment->id)->count());
        $this->assertSame($s['lineQl']->id,
            PaymentAllocation::where('payment_id', $payment->id)->value('statement_line_id'));
    }

    public function test_so_tien_dong_vuot_con_no_thi_422(): void
    {
        $s = $this->scene('D2');
        Sanctum::actingAs($s['user'], ['resident']);

        $this->postJson('/api/v1/resident/payments/claim',
            $this->claimBody($s, [
                ['statement_line_id' => $s['lineQl']->id, 'amount' => 2_000_000],
            ], 2_000_000))->assertStatus(422);
    }

    public function test_dong_khong_thuoc_bang_ke_thi_404(): void
    {
        $s = $this->scene('D3');
        $other = $this->scene('D3B');
        Sanctum::actingAs($s['user'], ['resident']);

        $this->postJson('/api/v1/resident/payments/claim',
            $this->claimBody($s, [
                ['statement_line_id' => $other['lineQl']->id, 'amount' => 100_000],
            ], 100_000))->assertStatus(404);
    }

    public function test_chon_dong_ma_khong_kem_hoa_don_thi_422(): void
    {
        $s = $this->scene('D4');
        Sanctum::actingAs($s['user'], ['resident']);

        $this->postJson('/api/v1/resident/payments/claim', [
            'amount' => 100_000,
            'paid_at' => now()->subHour()->toIso8601String(),
            'attachment_ids' => [$s['att']->id],
            'line_items' => [['statement_line_id' => $s['lineQl']->id, 'amount' => 100_000]],
        ])->assertStatus(422);
    }

    public function test_tong_dong_vuot_so_tien_khai_thi_422(): void
    {
        $s = $this->scene('D5');
        Sanctum::actingAs($s['user'], ['resident']);

        // Khai 500.000 nhưng tổng dòng 1.200.000.
        $this->postJson('/api/v1/resident/payments/claim',
            $this->claimBody($s, [
                ['statement_line_id' => $s['lineQl']->id, 'amount' => 1_000_000],
                ['statement_line_id' => $s['lineRac']->id, 'amount' => 200_000],
            ], 500_000))->assertStatus(422);
    }
}
