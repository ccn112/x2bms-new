<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\ApartmentWalletTransaction;
use App\Models\BillingPeriod;
use App\Models\Building;
use App\Models\FeeType;
use App\Models\Project;
use App\Models\Statement;
use App\Models\StatementLine;
use App\Models\Tenant;
use App\Services\Resident\ApartmentWalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * P1a / ADR-003 — `statement_lines.paid_amount` là PROJECTION:
 *   paid_amount = legacy_paid_amount + Σ ledger
 * với ledger = payment_allocations(line) ∪ apartment_wallet_transactions(out,ref=line).
 *
 * Khoá: đường ví/D6 phải SINH ledger row (không ghi thẳng paid_amount); legacy base
 * của dữ liệu cũ được GIỮ NGUYÊN; reconcile phát hiện & dựng lại drift không phá tiền.
 */
class LedgerUnificationP1aTest extends TestCase
{
    use RefreshDatabase;

    private function scope(string $tag): array
    {
        $tenant = Tenant::create(['code' => "TEN-LP-$tag", 'name' => "Tenant LP $tag"]);
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => "PRJ-LP-$tag", 'name' => "Project LP $tag"]);
        $building = Building::create(['tenant_id' => $tenant->id, 'project_id' => $project->id, 'code' => "BLD-LP-$tag", 'name' => "Building LP $tag"]);
        $apartment = Apartment::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => "APT-LP-$tag"]);
        $period = BillingPeriod::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => '2026-07', 'label' => 'Tháng 7/2026', 'period_month' => '2026-07-01']);
        $feeType = FeeType::create(['tenant_id' => $tenant->id, 'code' => "QL-$tag", 'name' => 'Phí quản lý', 'category' => 'management', 'is_critical' => false, 'payment_priority' => 100]);

        return compact('tenant', 'project', 'building', 'apartment', 'period', 'feeType');
    }

    private function lineWith(array $s, float $amount, float $paid = 0): StatementLine
    {
        $st = Statement::create([
            'tenant_id' => $s['tenant']->id, 'building_id' => $s['building']->id,
            'billing_period_id' => $s['period']->id, 'apartment_id' => $s['apartment']->id,
            'code' => 'BK-LP-'.$s['apartment']->id.'-'.random_int(1000, 9999),
            'total_amount' => $amount, 'paid_amount' => 0, 'status' => 'issued',
        ]);

        return StatementLine::create([
            'statement_id' => $st->id, 'fee_type_id' => $s['feeType']->id, 'fee_type' => $s['feeType']->name,
            'fee_category' => $s['feeType']->category, 'amount' => $amount, 'paid_amount' => $paid, 'status' => 'issued',
        ]);
    }

    public function test_vi_settlement_sinh_wallet_out_ledger_va_paid_amount_khop(): void
    {
        $s = $this->scope('A');
        $line = $this->lineWith($s, 1_000_000);

        $service = new ApartmentWalletService;
        $wallet = $service->walletFor($s['apartment']);
        $service->credit($wallet, '1000000');
        $service->autoSettleOutstanding($wallet->fresh());

        $line->refresh();

        // Có đúng ledger row wallet-out trỏ tới dòng này.
        $out = ApartmentWalletTransaction::withoutGlobalScopes()
            ->where('reference_type', $line->getMorphClass())
            ->where('reference_id', $line->id)
            ->where('direction', 'out')->where('status', 'confirmed')->sum('amount');
        $this->assertSame(0, bccomp('1000000', (string) $out, 2));

        // paid_amount = legacy(0) + ledger; legacy chốt = 0 (dòng mới không có nợ trả trước).
        $this->assertSame('0.00', (string) $line->legacy_paid_amount);
        $this->assertSame('1000000.00', $line->ledgerPaidAmount());
        $this->assertSame('1000000.00', (string) $line->paid_amount);
        $this->assertSame('paid', $line->status);
    }

    public function test_legacy_base_duoc_giu_khi_dong_da_co_paid_amount_truoc(): void
    {
        // Dòng "cũ": paid_amount=300k nhưng KHÔNG có ledger (giống seed). Settle thêm
        // 500k từ ví → paid phải = 300k(legacy) + 500k(ledger) = 800k, KHÔNG mất 300k.
        $s = $this->scope('B');
        $line = $this->lineWith($s, 1_000_000, 300_000);

        $service = new ApartmentWalletService;
        $wallet = $service->walletFor($s['apartment']);
        $service->credit($wallet, '500000');
        $service->autoSettleOutstanding($wallet->fresh());

        $line->refresh();
        $this->assertSame('300000.00', (string) $line->legacy_paid_amount, 'Legacy base phải chốt = paid cũ chưa có ledger');
        $this->assertSame('500000.00', $line->ledgerPaidAmount());
        $this->assertSame('800000.00', (string) $line->paid_amount, 'paid = legacy + ledger, không mất 300k cũ');
    }

    public function test_reconcile_line_ledger_dung_lai_drift_khong_pha_tien(): void
    {
        $s = $this->scope('C');
        $line = $this->lineWith($s, 1_000_000);

        $service = new ApartmentWalletService;
        $wallet = $service->walletFor($s['apartment']);
        $service->credit($wallet, '400000');
        $service->autoSettleOutstanding($wallet->fresh());

        $line->refresh();
        $this->assertSame('400000.00', (string) $line->paid_amount);

        // Bơm drift thủ công (giả lập một đường ghi tay lỗi).
        $line->forceFill(['paid_amount' => 999_999])->save();

        $code = Artisan::call('billing:reconcile-line-ledger', ['--fix' => true]);
        $this->assertSame(0, $code);

        $line->refresh();
        $this->assertSame('400000.00', (string) $line->paid_amount, 'Reconcile dựng lại paid = legacy + ledger');
    }

    public function test_reconcile_bao_loi_khi_drift_va_khong_fix(): void
    {
        $s = $this->scope('D');
        $line = $this->lineWith($s, 1_000_000);
        $line->ensureLegacyBase();
        $line->forceFill(['paid_amount' => 123_456])->save();

        $code = Artisan::call('billing:reconcile-line-ledger');
        $this->assertSame(1, $code, 'Không --fix mà có lệch phải trả FAILURE để CI bắt được');
    }
}
