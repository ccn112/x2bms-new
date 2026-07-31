<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Statement;
use App\Models\StatementLine;
use Illuminate\Console\Command;

/**
 * Đối chiếu Phase B3 (D3) — `docs/delivery/TECH_DEBT_REGISTER.md` M5: không có
 * bất biến tầng DB cho `statements.paid_amount = Σ statement_lines.paid_amount`,
 * và có 2 đường ghi tiền khác nhau (`ResidentPaymentClaimReviewer`,
 * `ApartmentWalletService`) có thể lệch nếu một đường quên gọi
 * `Statement::recomputePaidAmount()`.
 *
 * Không tự sửa `statement_lines.paid_amount > amount` (dòng phí nhận quá tiền
 * của chính nó) — sửa nghĩa là phải quyết định HOÀN TRẢ khoản nào, đó là quyết
 * định nghiệp vụ không được đoán tự động. Lệnh này chỉ BÁO, không ghi gì cho
 * trường hợp đó.
 */
class ReconcileStatementBalances extends Command
{
    protected $signature = 'billing:reconcile-statement-balances
                            {--dry-run : Chỉ đếm/báo, không ghi}';

    protected $description = 'Đối chiếu statements.paid_amount với SUM(statement_lines.paid_amount); báo dòng phí bị trả quá tiền';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $overpaidLines = StatementLine::query()
            ->whereColumn('paid_amount', '>', 'amount')
            ->with('statement')
            ->get();

        if ($overpaidLines->isNotEmpty()) {
            $this->warn("Phát hiện {$overpaidLines->count()} dòng phí trả QUÁ tiền của chính nó (không tự sửa):");
            foreach ($overpaidLines as $line) {
                $this->line("  StatementLine #{$line->id} (statement {$line->statement?->code}): paid_amount={$line->paid_amount} > amount={$line->amount}");
            }
        }

        $fixed = 0;
        $checked = 0;

        Statement::query()->chunkById(200, function ($statements) use (&$fixed, &$checked, $dryRun) {
            foreach ($statements as $statement) {
                $checked++;
                $expected = (string) $statement->lines()->sum('paid_amount');
                $actual = (string) $statement->paid_amount;

                if (bccomp($expected, $actual, 2) !== 0) {
                    $fixed++;
                    $this->line("Statement #{$statement->id} ({$statement->code}): paid_amount lưu={$actual}, đúng phải={$expected}".($dryRun ? ' [dry-run]' : ' — đã sửa'));
                    if (! $dryRun) {
                        $statement->recomputePaidAmount();
                    }
                }
            }
        });

        $this->info(($dryRun ? '[dry-run] ' : '')."Kiểm {$checked} bảng kê, lệch {$fixed}.");

        return self::SUCCESS;
    }
}
