<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\LiabilityPeriod;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * P1b — Backfill `liability_periods` từ `resident_apartment_relations`.
 *
 * Tạo 1 liability "owner, mọi family (`['all']`), MỞ" cho mỗi quan hệ CHỦ HỘ
 * (`role='owner'`) hiện có. Đây là baseline "ai chịu nợ" tối thiểu — KHÔNG đoán
 * owner/tenant split (chia family theo vai) vì đó là quyết định nhập liệu của BQL,
 * làm sau khi có màn liability (Phase 5). `liable_from` = `start_date` của quan hệ
 * nếu có, không thì ngày tạo quan hệ.
 *
 * Idempotent: bỏ qua nếu đã có liability owner-mở cho đúng (apartment, resident).
 * Không đụng dữ liệu khác. Đảo ngược: rollback migration `liability_periods`.
 */
class BackfillLiabilityPeriods extends Command
{
    protected $signature = 'billing:backfill-liability-periods
                            {--dry-run : Chỉ đếm/báo, không ghi}';

    protected $description = 'Backfill liability_periods (owner, mọi family, mở) từ resident_apartment_relations (P1b)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $created = 0;
        $skipped = 0;

        DB::table('resident_apartment_relations')
            ->where('role', 'owner')
            ->when(
                Schema::hasColumn('resident_apartment_relations', 'deleted_at'),
                fn ($q) => $q->whereNull('deleted_at'),
            )
            ->orderBy('id')
            ->chunk(500, function ($relations) use (&$created, &$skipped, $dryRun) {
                foreach ($relations as $rel) {
                    $exists = LiabilityPeriod::query()
                        ->withoutGlobalScopes()
                        ->where('apartment_id', $rel->apartment_id)
                        ->where('resident_id', $rel->resident_id)
                        ->where('role', 'owner')
                        ->whereNull('liable_to')
                        ->exists();

                    if ($exists) {
                        $skipped++;

                        continue;
                    }

                    if ($dryRun) {
                        $created++;

                        continue;
                    }

                    LiabilityPeriod::create([
                        'tenant_id' => $rel->tenant_id,
                        'apartment_id' => $rel->apartment_id,
                        'resident_id' => $rel->resident_id,
                        'role' => 'owner',
                        'liable_from' => $rel->start_date ?? (isset($rel->created_at) ? substr((string) $rel->created_at, 0, 10) : null),
                        'liable_to' => null,
                        'scope' => ['all'],
                        'source' => 'backfill',
                    ]);
                    $created++;
                }
            });

        $this->info(($dryRun ? '[dry-run] ' : '')."Tạo {$created} liability owner-mở; bỏ qua {$skipped} (đã có).");

        return self::SUCCESS;
    }
}
