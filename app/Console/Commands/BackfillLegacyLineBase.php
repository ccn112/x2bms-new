<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\StatementLine;
use Illuminate\Console\Command;

/**
 * P1a / ADR-003 — Chốt `statement_lines.legacy_paid_amount` cho dữ liệu cũ.
 *
 * `legacy_paid_amount` = phần `paid_amount` đã có TRƯỚC khi hệ thống có ledger
 * (seed: ~1.088 bảng kê paid/partial nhưng chỉ 13 allocations). Công thức:
 *   legacy = max(paid_amount − Σledger, 0)
 * với ledger = payment_allocations(line) ∪ apartment_wallet_transactions(out,ref=line).
 *
 * Idempotent: chỉ chốt dòng `legacy_paid_amount IS NULL`. Chạy lại vô hại. Tầng
 * model cũng tự chốt lazy (`ensureLegacyBase()`) nên command này chỉ để chốt
 * EAGER có kiểm soát + báo cáo trước khi mở luồng ghi tiền mới.
 *
 * KHÔNG đổi `paid_amount`. Đảo ngược: rollback migration (drop cột) là đủ.
 */
class BackfillLegacyLineBase extends Command
{
    protected $signature = 'billing:backfill-legacy-line-base
                            {--dry-run : Chỉ đếm/báo, không ghi}';

    protected $description = 'Chốt statement_lines.legacy_paid_amount = max(paid_amount − Σledger, 0) cho dòng chưa chốt (P1a/ADR-003)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $set = 0;
        $checked = 0;
        $withLegacy = 0;

        StatementLine::query()
            ->whereNull('legacy_paid_amount')
            ->chunkById(500, function ($lines) use (&$set, &$checked, &$withLegacy, $dryRun) {
                foreach ($lines as $line) {
                    $checked++;
                    $legacy = bcsub((string) ($line->paid_amount ?? 0), $line->ledgerPaidAmount(), 2);
                    if (bccomp($legacy, '0', 2) < 0) {
                        $legacy = '0';
                    }
                    if (bccomp($legacy, '0', 2) > 0) {
                        $withLegacy++;
                    }
                    if (! $dryRun) {
                        $line->forceFill(['legacy_paid_amount' => $legacy])->save();
                        $set++;
                    }
                }
            });

        $this->info(($dryRun ? '[dry-run] ' : '')
            ."Kiểm {$checked} dòng chưa chốt; {$withLegacy} dòng có legacy>0"
            .($dryRun ? '.' : "; đã chốt {$set} dòng."));

        return self::SUCCESS;
    }
}
