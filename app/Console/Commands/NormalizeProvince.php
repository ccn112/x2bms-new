<?php

namespace App\Console\Commands;

use App\Models\PublicProject;
use App\Services\Projects\BdsProjectImporter;
use Illuminate\Console\Command;

/**
 * Chuẩn hoá cột public_projects.province về tên canonical thống nhất
 * (gộp "TP.HCM"/"Thành phố Hồ Chí Minh" → "Hồ Chí Minh", bỏ tiền tố Tỉnh/TP, …).
 * Dùng chung logic BdsProjectImporter::canonicalProvince(). Idempotent.
 *
 * php artisan projects:normalize-province [--dry-run]
 */
class NormalizeProvince extends Command
{
    protected $signature = 'projects:normalize-province {--dry-run : Chỉ xem thay đổi, không ghi}';

    protected $description = 'Gộp/chuẩn hoá nhãn tỉnh trùng ở public_projects.province';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        $before = PublicProject::query()->distinct()->count('province');
        $groups = PublicProject::query()
            ->selectRaw('province, count(*) c')
            ->groupBy('province')
            ->pluck('c', 'province');

        $changed = 0;
        $rowsAffected = 0;
        foreach ($groups as $old => $count) {
            $oldStr = $old === null ? null : (string) $old;
            $new = BdsProjectImporter::canonicalProvince($oldStr);
            if ($new === $oldStr) {
                continue;
            }
            $changed++;
            $rowsAffected += (int) $count;
            $this->line(sprintf('  [%4d] %-40s → %s', $count, $oldStr ?? '(null)', $new ?? '(null)'));
            if (! $dry) {
                PublicProject::query()->where('province', $old)->update(['province' => $new]);
            }
        }

        $after = $dry ? '(dry-run)' : PublicProject::query()->distinct()->count('province');
        $this->newLine();
        $this->info("Distinct province: TRƯỚC=$before, SAU=$after. Nhãn gộp=$changed, dòng cập nhật=$rowsAffected.".($dry ? ' [DRY-RUN]' : ''));

        return self::SUCCESS;
    }
}
