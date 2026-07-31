<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\BillingFamily;
use App\Models\FeeType;
use App\Models\StatementLine;
use Illuminate\Console\Command;

/**
 * Backfill `statement_lines.fee_category` sang 5 billing family (D2,
 * `docs/BILLING_OWNER_DECISIONS_20260731.md`) — điều kiện schema bắt buộc trước khi
 * `BillingChargeImportProfile` chạy (spec §6).
 *
 * Có 2211/7212 dòng đã có `fee_category`, nhưng mang giá trị CŨ (`management|parking|
 * service` — copy thẳng từ `fee_types.category`), không phải 5 family. Riêng `parking`
 * SAI hẳn thành `vehicle`. Nên lệnh này GHI ĐÈ toàn bộ theo nguồn `fee_type_id` — không
 * chỉ lấp chỗ NULL — để không còn hai bộ giá trị trộn lẫn trong cùng một cột.
 *
 * 4792 dòng cũ hơn không có `fee_type_id` (chỉ có chuỗi tự do `fee_type`, từ trước khi
 * cột này tồn tại) — suy family bằng từ khoá tên, theo đúng thứ tự ưu tiên của
 * `BillingFamily::splitUtility()` (xe trước điện, vì "Phí gửi xe ô tô" không được rơi
 * vào `Other` chỉ vì không có `fee_type_id`).
 */
class BackfillFeeCategoryFamily extends Command
{
    protected $signature = 'billing:backfill-fee-family
                            {--dry-run : Chỉ đếm, không ghi}';

    protected $description = 'Backfill statement_lines.fee_category sang 5 billing family (D2)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        /** @var array<int, BillingFamily> $familyByFeeType */
        $familyByFeeType = FeeType::query()->get(['id', 'category', 'code', 'name'])
            ->mapWithKeys(fn (FeeType $ft) => [$ft->id => BillingFamily::fromFeeType($ft)])
            ->all();

        $changed = 0;
        $unchanged = 0;
        $byFamily = [];

        StatementLine::query()
            ->select(['id', 'fee_type_id', 'fee_type', 'fee_category'])
            ->chunkById(500, function ($lines) use ($familyByFeeType, $dryRun, &$changed, &$unchanged, &$byFamily): void {
                foreach ($lines as $line) {
                    $family = $line->fee_type_id !== null && isset($familyByFeeType[$line->fee_type_id])
                        ? $familyByFeeType[$line->fee_type_id]
                        : $this->resolveLegacyFamily($line->fee_type);

                    $byFamily[$family->value] = ($byFamily[$family->value] ?? 0) + 1;

                    if ($line->fee_category === $family->value) {
                        $unchanged++;

                        continue;
                    }

                    $changed++;
                    if (! $dryRun) {
                        $line->update(['fee_category' => $family->value]);
                    }
                }
            });

        $this->info(($dryRun ? '[dry-run] ' : '')."Sẽ đổi: {$changed} dòng · giữ nguyên: {$unchanged} dòng");
        foreach ($byFamily as $family => $count) {
            $this->line("  {$family}: {$count}");
        }

        return self::SUCCESS;
    }

    /**
     * Suy family từ chuỗi tự do `fee_type` (dòng cũ không có `fee_type_id`). Đây KHÔNG
     * phải bản sao của `BillingFamily::fromParts()` — đó là hàm cho dữ liệu có
     * `category` chuẩn từ `fee_types`; hàm này là lối thoát một lần cho dữ liệu cũ
     * không còn `category` nào để tra.
     */
    private function resolveLegacyFamily(?string $legacyLabel): BillingFamily
    {
        $haystack = mb_strtolower(trim((string) $legacyLabel));

        if ($haystack === '') {
            return BillingFamily::Other;
        }

        if (str_contains($haystack, 'quản lý') || str_contains($haystack, 'quan ly')) {
            return BillingFamily::Management;
        }

        // Xe trước điện: "Phí gửi xe ô tô" không được rơi vào Electricity/Other.
        if (str_contains($haystack, 'xe') && (str_contains($haystack, 'gửi') || str_contains($haystack, 'gui') || str_contains($haystack, 'ô tô') || str_contains($haystack, 'oto'))) {
            return BillingFamily::Vehicle;
        }

        if (str_contains($haystack, 'nước') || str_contains($haystack, 'nuoc')) {
            return BillingFamily::Water;
        }

        if (str_contains($haystack, 'điện') || str_contains($haystack, 'dien')) {
            return BillingFamily::Electricity;
        }

        return BillingFamily::Other;
    }
}
