<?php

namespace App\Console\Commands;

use App\Models\Building;
use App\Models\BillingPeriod;
use App\Services\Billing\Engine\BillingRunner;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Engine tính phí — chạy family cho một tòa + kỳ (Phase 2, plan §5).
 * MẶC ĐỊNH DRY-RUN (không ghi) để đối soát với số kế toán import; `--commit` mới ghi
 * `statement_lines` (approval_status=pending, engine KHÔNG phát hành).
 *
 *   php artisan billing:run <building_id> <period_code>            # dry-run
 *   php artisan billing:run <building_id> <period_code> --commit   # ghi thật (pending)
 */
class BillingRun extends Command
{
    protected $signature = 'billing:run
        {building : ID tòa nhà}
        {period : mã kỳ (billing_periods.code, vd 2026-07)}
        {--family=management : family cần tính (P2.1: management)}
        {--commit : Ghi thật (mặc định chỉ dry-run)}';

    protected $description = 'Engine tính phí: chạy family cho tòa+kỳ (dry-run mặc định)';

    public function handle(BillingRunner $runner): int
    {
        $building = Building::withoutGlobalScopes()->find((int) $this->argument('building'));
        if ($building === null) {
            $this->error('Không thấy tòa #'.$this->argument('building'));

            return self::FAILURE;
        }
        $period = BillingPeriod::withoutGlobalScopes()
            ->where('building_id', $building->id)->where('code', $this->argument('period'))->first();
        if ($period === null) {
            $this->error('Không thấy kỳ '.$this->argument('period').' cho tòa #'.$building->id);

            return self::FAILURE;
        }

        $start = Carbon::parse($period->period_month ?? ($this->argument('period').'-01'))->startOfMonth();
        $end = $start->copy()->endOfMonth();

        if (($family = $this->option('family')) !== 'management') {
            $this->error("Family '{$family}' chưa hỗ trợ (P2.1 mới có management).");

            return self::FAILURE;
        }

        $r = $runner->runManagement(
            (int) $building->tenant_id, $building->id, $period->id,
            $start->toDateString(), $end->toDateString(),
            commit: (bool) $this->option('commit'),
        );

        if (isset($r['error'])) {
            $this->error('Lỗi: '.$r['error']);

            return self::FAILURE;
        }

        $this->info(($r['committed'] ? '[GHI THẬT]' : '[DRY-RUN]').' family='.$r['family']
            .' · đơn giá='.number_format($r['unit_price'] ?? 0)
            .' · số căn='.$r['apartments'].' · TỔNG='.number_format($r['total']).' đ');
        if (! $r['committed']) {
            $this->comment('Chưa ghi. Thêm --commit để ghi (approval_status=pending, cần duyệt để phát hành).');
        }

        return self::SUCCESS;
    }
}
