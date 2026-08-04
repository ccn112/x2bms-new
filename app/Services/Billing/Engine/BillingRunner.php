<?php

declare(strict_types=1);

namespace App\Services\Billing\Engine;

use App\Models\Apartment;
use App\Models\BillingRun;
use App\Models\FeeRate;
use App\Models\FeeType;
use App\Models\Statement;
use App\Models\StatementLine;
use Illuminate\Support\Facades\DB;

/**
 * Engine tính phí — ORCHESTRATOR (plan §4). Đọc input, gọi generator THUẦN, ghi
 * `statement_lines`. Nguyên tắc bắt buộc đã áp:
 *  - Engine KHÔNG phát hành: statement ghi `approval_status = pending` (D1, §4-4).
 *  - Idempotent theo khóa upsert `(statement_id, fee_type_id, subject, service_period_start)` (§4-5).
 *  - Tiền số nguyên đồng, làm tròn half-up trong generator (D7, §4-6).
 *  - Snapshot công thức vào từng dòng (`calculation_snapshot`) (§4-2).
 *
 * P2.1: family `management`. Các family khác (vehicle/electricity/water/other) thêm
 * generator theo cùng khung, đối soát số kế toán import (§5) trước khi bật.
 */
class BillingRunner
{
    public function __construct(private readonly ManagementFeeGenerator $management) {}

    /**
     * Sinh ChargeDraft phí quản lý cho từng căn của tòa (THUẦN — không ghi DB).
     * Dùng cho cả dry-run/commit lẫn công cụ đối soát `billing:reconcile-engine`.
     *
     * @return array{drafts:array<int,ChargeDraft>,unit_price?:int,error?:string}
     */
    public function managementDrafts(int $tenantId, int $buildingId, string $periodStart, string $periodEnd): array
    {
        $feeType = FeeType::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)->where('category', 'management')
            ->where('status', 'active')->orderBy('id')->first();
        if ($feeType === null) {
            return ['drafts' => [], 'error' => 'no_management_fee_type'];
        }

        $rate = FeeRate::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)->where('fee_type_id', $feeType->id)->where('status', 'active')
            ->where('effective_from', '<=', $periodStart)
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $periodStart))
            ->orderByDesc('effective_from')->first();
        if ($rate === null) {
            return ['drafts' => [], 'error' => 'no_active_rate'];
        }

        $unitPrice = (int) round((float) $rate->amount, 0, PHP_ROUND_HALF_UP);
        $ftMeta = ['fee_type_id' => (int) $feeType->id, 'fee_type_name' => (string) $feeType->name, 'vat_percent' => (float) ($feeType->vat_percent ?? 0)];

        $drafts = [];
        foreach (Apartment::withoutGlobalScopes()->where('building_id', $buildingId)->get() as $apt) {
            $draft = $this->management->generate($ftMeta, (float) $apt->area_sqm, $unitPrice, $periodStart, $periodEnd, (int) $rate->id);
            if ($draft !== null) {
                $drafts[(int) $apt->id] = $draft;
            }
        }

        return ['drafts' => $drafts, 'unit_price' => $unitPrice];
    }

    /**
     * Chạy family MANAGEMENT cho một tòa + kỳ.
     *
     * @return array{family:string,unit_price?:int,apartments:int,total:int,committed:bool,error?:string}
     */
    public function runManagement(
        int $tenantId,
        int $buildingId,
        int $billingPeriodId,
        string $periodStart,
        string $periodEnd,
        bool $commit = false,
        ?int $userId = null,
    ): array {
        $gen = $this->managementDrafts($tenantId, $buildingId, $periodStart, $periodEnd);
        if (isset($gen['error'])) {
            return ['family' => 'management', 'apartments' => 0, 'total' => 0, 'committed' => false, 'error' => $gen['error']];
        }
        $drafts = $gen['drafts'];
        $unitPrice = $gen['unit_price'];
        $total = array_sum(array_map(fn (ChargeDraft $d) => $d->amount, $drafts));

        if (! $commit) {
            return ['family' => 'management', 'unit_price' => $unitPrice, 'apartments' => count($drafts), 'total' => $total, 'committed' => false];
        }

        DB::transaction(function () use ($drafts, $tenantId, $buildingId, $billingPeriodId, $userId, $total) {
            foreach ($drafts as $apartmentId => $draft) {
                $stmt = Statement::withoutGlobalScopes()->firstOrCreate(
                    ['tenant_id' => $tenantId, 'building_id' => $buildingId, 'apartment_id' => $apartmentId, 'billing_period_id' => $billingPeriodId],
                    ['total_amount' => 0, 'paid_amount' => 0, 'status' => 'issued', 'approval_status' => Statement::APPROVAL_PENDING],
                );

                StatementLine::withoutGlobalScopes()->updateOrCreate(
                    $draft->naturalKey() + ['statement_id' => $stmt->id],
                    $draft->toLineAttributes(),
                );

                // Tổng bảng kê = Σ mọi dòng (kể cả dòng import cũ nếu có) — không cộng tay.
                $stmt->forceFill(['total_amount' => (int) StatementLine::withoutGlobalScopes()->where('statement_id', $stmt->id)->sum('amount')])->save();
                $stmt->recomputePaidAmount();
            }

            BillingRun::withoutGlobalScopes()->updateOrCreate(
                ['tenant_id' => $tenantId, 'building_id' => $buildingId, 'billing_period_id' => $billingPeriodId],
                [
                    'code' => 'RUN-'.$billingPeriodId.'-'.$buildingId,
                    'status' => 'completed', 'approval_status' => Statement::APPROVAL_PENDING,
                    'total_billed' => $total, 'statements_count' => count($drafts), 'apartment_count' => count($drafts),
                    'run_at' => now(), 'created_by_id' => $userId,
                    'note' => 'Engine P2.1 management (dry-run→commit).',
                ],
            );
        });

        return ['family' => 'management', 'unit_price' => $unitPrice, 'apartments' => count($drafts), 'total' => $total, 'committed' => true];
    }
}
