<?php

namespace App\Console\Commands;

use App\Models\BillingPeriod;
use App\Models\Building;
use App\Models\Statement;
use App\Models\StatementLine;
use App\Services\Billing\Engine\BillingRunner;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Đối soát ENGINE vs SỐ KẾ TOÁN import (plan §5, TEST_PLAN §3B) — "bộ test vàng".
 * Chạy engine DRY-RUN + so TỪNG dòng với `statement_lines` đã import (source!=engine)
 * theo khóa (fee_type, subject, service_period_start). Phân loại 3 mức:
 *   khớp tuyệt đối · lệch ≤ 1 đồng (làm tròn) · lệch thật.
 * KHÔNG ghi gì. Dùng để nghiệm thu trước khi bật engine.
 *
 *   php artisan billing:reconcile-engine <building_id> <period_code> [--family=management]
 */
class BillingReconcileEngine extends Command
{
    protected $signature = 'billing:reconcile-engine
        {building : ID tòa}
        {period : mã kỳ (billing_periods.code)}
        {--family=management : family đối soát (P2.1: management)}
        {--show=15 : số dòng lệch in ra}';

    protected $description = 'Đối soát engine (dry-run) vs số kế toán import — bộ test vàng';

    public function handle(BillingRunner $runner): int
    {
        $building = Building::withoutGlobalScopes()->find((int) $this->argument('building'));
        $period = $building
            ? BillingPeriod::withoutGlobalScopes()->where('building_id', $building->id)->where('code', $this->argument('period'))->first()
            : null;
        if ($building === null || $period === null) {
            $this->error('Không thấy tòa/kỳ.');

            return self::FAILURE;
        }
        if ($this->option('family') !== 'management') {
            $this->error("Family '{$this->option('family')}' chưa hỗ trợ (P2.1 management).");

            return self::FAILURE;
        }

        $start = Carbon::parse($period->period_month ?? ($this->argument('period').'-01'))->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $gen = $runner->managementDrafts((int) $building->tenant_id, $building->id, $start->toDateString(), $end->toDateString());
        if (isset($gen['error'])) {
            $this->error('Không tính được: '.$gen['error'].' (cần khai fee_rates cho tenant/kỳ).');

            return self::FAILURE;
        }

        $exact = 0;
        $rounding = 0;
        $realDelta = 0;
        $missing = 0;
        $deltas = [];

        foreach ($gen['drafts'] as $apartmentId => $draft) {
            $stmt = Statement::withoutGlobalScopes()
                ->where('building_id', $building->id)->where('apartment_id', $apartmentId)
                ->where('billing_period_id', $period->id)->first();
            $line = $stmt === null ? null : StatementLine::withoutGlobalScopes()
                ->where('statement_id', $stmt->id)
                ->where('fee_type_id', $draft->feeTypeId)
                ->where('service_period_start', $draft->servicePeriodStart)
                ->where('source', '!=', 'engine')   // so với dòng KẾ TOÁN, không so với chính engine
                ->first();

            if ($line === null) {
                $missing++;

                continue;
            }
            $diff = $draft->amount - (int) round((float) $line->amount);
            if ($diff === 0) {
                $exact++;
            } elseif (abs($diff) <= 1) {
                $rounding++;
            } else {
                $realDelta++;
                if (count($deltas) < (int) $this->option('show')) {
                    $deltas[] = sprintf('  căn #%d: engine %s vs kế toán %s (lệch %s)',
                        $apartmentId, number_format($draft->amount), number_format((float) $line->amount), number_format($diff));
                }
            }
        }

        $this->info("Đối soát family=management · tòa #{$building->id} · kỳ {$period->code} · đơn giá ".number_format($gen['unit_price'] ?? 0));
        $this->line('  Căn engine tính được : '.count($gen['drafts']));
        $this->line("  ✅ Khớp tuyệt đối     : {$exact}");
        $this->line("  ≈ Lệch ≤ 1đ (làm tròn): {$rounding}");
        $this->line("  ❌ Lệch THẬT          : {$realDelta}");
        $this->line("  ⚠ Không có dòng kế toán tương ứng: {$missing}");
        foreach ($deltas as $d) {
            $this->warn($d);
        }
        if ($realDelta > 0) {
            $this->comment('Mọi dòng lệch THẬT phải giải thích được trước khi bật engine (TEST_PLAN §1.5).');
        }

        return self::SUCCESS;
    }
}
