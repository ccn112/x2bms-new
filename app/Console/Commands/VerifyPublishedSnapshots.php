<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Statement;
use App\Services\Billing\StatementSnapshotBuilder;
use Illuminate\Console\Command;

/**
 * AUDIT — Bảng kê ĐÃ PHÁT HÀNH phải BẤT BIẾN (D15). Với mỗi bảng kê published có
 * snapshot, dựng lại snapshot từ dữ liệu SỐNG rồi so `checksum`. Lệch = nội dung
 * bảng kê cư dân đã nhận bị thay đổi sau phát hành → phải điều tra (không được xảy ra).
 *
 * Chỉ báo, không sửa. Trả FAILURE nếu có lệch (để CI/cron bắt được).
 */
class VerifyPublishedSnapshots extends Command
{
    protected $signature = 'billing:verify-published-snapshots {--limit=0 : In tối đa N bản lệch (0=tất cả)}';

    protected $description = 'Đối soát bảng kê published: dữ liệu sống có khớp snapshot đã chốt không (D15 immutability)';

    public function handle(): int
    {
        $builder = new StatementSnapshotBuilder;
        $limit = (int) $this->option('limit');

        $checked = 0;
        $drift = 0;
        $noSnapshot = 0;
        $printed = 0;

        Statement::query()
            ->where('approval_status', Statement::APPROVAL_PUBLISHED)
            ->whereNotNull('published_at')
            ->with('lines')
            ->chunkById(200, function ($statements) use ($builder, &$checked, &$drift, &$noSnapshot, &$printed, $limit) {
                foreach ($statements as $statement) {
                    $checked++;

                    if ($statement->snapshot_checksum === null) {
                        $noSnapshot++;

                        continue;
                    }

                    $live = $builder->checksum($builder->build($statement));
                    if ($live !== $statement->snapshot_checksum) {
                        $drift++;
                        if ($limit === 0 || $printed < $limit) {
                            $this->error("  LỆCH: {$statement->code} — snapshot khác dữ liệu sống (bảng kê đã phát hành bị đổi).");
                            $printed++;
                        }
                    }
                }
            });

        if ($noSnapshot > 0) {
            $this->warn("  {$noSnapshot} bảng kê published KHÔNG có snapshot (phát hành trước P3 hoặc bằng đường khác).");
        }

        $this->info("Kiểm {$checked} bảng kê published · lệch {$drift} · thiếu snapshot {$noSnapshot}.");

        return $drift === 0 ? self::SUCCESS : self::FAILURE;
    }
}
