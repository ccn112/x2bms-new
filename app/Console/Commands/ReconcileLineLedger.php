<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\StatementLine;
use Illuminate\Console\Command;

/**
 * P1a / ADR-003 — Đối chiếu bất biến LEDGER cấp DÒNG PHÍ:
 *
 *   statement_lines.paid_amount == legacy_paid_amount + Σ ledger
 *   (ledger = payment_allocations(line) ∪ apartment_wallet_transactions(out,ref=line,confirmed))
 *
 * Đây là bất biến sâu hơn `billing:reconcile-statement-balances` (chỉ kiểm
 * statement = Σ lines). Không có DB CHECK cross-table cho bất biến này (MySQL
 * không hỗ trợ), nên lệnh + test là nơi bảo vệ.
 *
 * Dòng `legacy_paid_amount IS NULL` (chưa chốt): expected = paid_amount theo
 * định nghĩa → không bao giờ lệch. Chạy `billing:backfill-legacy-line-base`
 * trước để kiểm có ý nghĩa.
 *
 * `--fix`: gọi `recomputePaidFromLedger()` (an toàn — legacy giữ nguyên, chỉ
 * dựng lại paid_amount = legacy + Σledger). KHÔNG bao giờ xoá tiền seed.
 */
class ReconcileLineLedger extends Command
{
    protected $signature = 'billing:reconcile-line-ledger
                            {--fix : Dựng lại paid_amount lệch từ ledger (mặc định chỉ báo)}';

    protected $description = 'Đối chiếu statement_lines.paid_amount = legacy_paid_amount + Σ ledger (P1a/ADR-003)';

    public function handle(): int
    {
        $fix = (bool) $this->option('fix');
        $checked = 0;
        $mismatched = 0;
        $overpaid = 0;

        StatementLine::query()
            ->with(['feeType', 'statement'])
            ->chunkById(500, function ($lines) use (&$checked, &$mismatched, &$overpaid, $fix) {
                foreach ($lines as $line) {
                    $checked++;

                    $ledger = $line->ledgerPaidAmount();
                    $legacy = $line->legacy_paid_amount !== null
                        ? (string) $line->legacy_paid_amount
                        : (function () use ($line, $ledger) {
                            $l = bcsub((string) ($line->paid_amount ?? 0), $ledger, 2);

                            return bccomp($l, '0', 2) < 0 ? '0' : $l;
                        })();

                    $expected = bcadd($legacy, $ledger, 2);
                    $actual = (string) ($line->paid_amount ?? 0);

                    if (bccomp($expected, $actual, 2) !== 0) {
                        $mismatched++;
                        $this->line("  Line #{$line->id} (statement {$line->statement?->code}): paid_amount lưu={$actual}, đúng phải={$expected} (legacy={$legacy}, ledger={$ledger})"
                            .($fix ? ' — đã dựng lại' : ' [chỉ báo]'));
                        if ($fix) {
                            $line->recomputePaidFromLedger();
                        }
                    }

                    if (bccomp($actual, (string) $line->amount, 2) > 0) {
                        $overpaid++;
                    }
                }
            });

        if ($overpaid > 0) {
            $this->warn("Có {$overpaid} dòng phí `paid_amount > amount` (trả quá tiền dòng) — cần quyết định hoàn trả, KHÔNG tự sửa.");
        }

        $this->info(($fix ? '' : '[chỉ báo] ')."Kiểm {$checked} dòng, lệch {$mismatched}.");

        return $mismatched > 0 && ! $fix ? self::FAILURE : self::SUCCESS;
    }
}
