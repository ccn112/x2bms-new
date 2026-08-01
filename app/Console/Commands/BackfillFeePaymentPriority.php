<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\BillingFamily;
use App\Models\FeeType;
use Illuminate\Console\Command;

/**
 * Backfill `fee_types.payment_priority` sang thứ tự mặc định theo 5 billing family
 * (Phase B4, D4-bis): QL(100) → Nước(200) → Điện(300) → Xe(400) → Khác(900).
 *
 * Trước lệnh này, `payment_priority` mặc định ĐỒNG LOẠT `100` cho MỌI loại phí (cột
 * `unsignedSmallInteger` default 100, thêm ở `2026_07_26_000001_create_apartment_wallets.php`),
 * nên `StatementLine::allocationSortKey()` chỉ phân biệt được thứ tự nhờ `is_critical`,
 * chưa hề phản ánh gia đình phí D4 chốt.
 *
 * Suy family qua `BillingFamily::fromFeeType()` — CHỖ DUY NHẤT chứa logic này (mirror
 * `BackfillFeeCategoryFamily`, không viết lại một bản suy family thứ hai).
 *
 * Guard "đừng ghi đè giá trị BQL đã tự tay đặt": dùng cột `payment_priority_locked_at`
 * (migration `2026_08_01_000001_...`). Hôm nay CHƯA có UI nào ghi cột này (không UI nào
 * sửa `payment_priority` cấp tenant cả — override theo dự án đi qua bảng riêng
 * `fee_type_priority_overrides`, không đụng cột này) nên lần chạy đầu tiên sẽ đổi gần hết.
 * Cột lock có mặt để lệnh này AN TOÀN chạy LẶP LẠI sau này (thêm fee_type mới, hoặc một
 * UI tương lai cho phép sửa tay) mà không xoá mất giá trị người dùng thật đã đặt.
 */
class BackfillFeePaymentPriority extends Command
{
    protected $signature = 'billing:backfill-fee-priority
                            {--dry-run : Chỉ đếm, không ghi}';

    protected $description = 'Backfill fee_types.payment_priority theo thứ tự mặc định của billing family (D4-bis)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $changed = 0;
        $unchanged = 0;
        $locked = 0;
        $byFamily = [];

        FeeType::query()
            ->select(['id', 'category', 'code', 'name', 'payment_priority', 'payment_priority_locked_at'])
            ->chunkById(200, function ($feeTypes) use ($dryRun, &$changed, &$unchanged, &$locked, &$byFamily): void {
                foreach ($feeTypes as $feeType) {
                    if ($feeType->payment_priority_locked_at !== null) {
                        $locked++;

                        continue;
                    }

                    $family = BillingFamily::fromFeeType($feeType);
                    $target = $family->defaultPriority();
                    $byFamily[$family->value] = ($byFamily[$family->value] ?? 0) + 1;

                    if ((int) $feeType->payment_priority === $target) {
                        $unchanged++;

                        continue;
                    }

                    $changed++;
                    if (! $dryRun) {
                        $feeType->update(['payment_priority' => $target]);
                    }
                }
            });

        $this->info(($dryRun ? '[dry-run] ' : '')."Sẽ đổi: {$changed} dòng · giữ nguyên (đã đúng): {$unchanged} dòng · bỏ qua (đã khoá tay): {$locked} dòng");
        foreach ($byFamily as $family => $count) {
            $this->line("  {$family}: {$count}");
        }

        return self::SUCCESS;
    }
}
