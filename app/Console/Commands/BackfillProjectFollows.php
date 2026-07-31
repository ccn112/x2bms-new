<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\UserProjectFollow;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Backfill Giai đoạn 4 (follow dự án) — `COMMUNITY_DB_MAPPING.md` §4,
 * `COMMUNITY_RISK_ROLLBACK.md` R3.
 *
 * Chốt 2026-07-31 (chủ dự án): CHỈ backfill 5 dự án đã nối chính xác qua
 * `projects.public_project_id` (Cách A) — KHÔNG dùng khớp mờ tên. 22 dự án
 * còn lại đã có màn `Sa/Pages/ProjectCatalogLinking` để SuperAdmin tự nối tay
 * (Cách C); một khi nối xong, chạy lại lệnh này (idempotent) sẽ backfill
 * thêm follow cho dự án vừa nối — không cần sửa gì ở đây.
 *
 * Nguồn: `user_public_projects.public_project_id` (chọn lúc đăng ký, trỏ
 * danh mục) → `projects.public_project_id` (đã nối) → `projects.id` thật.
 * Không nối được thì BỎ QUA dòng đó, không đoán — nối nhầm dự án là cho
 * người lạ vào kênh nội dung của dự án khác (R3).
 */
class BackfillProjectFollows extends Command
{
    protected $signature = 'community:backfill-project-follows
                            {--rollback : Xoá toàn bộ user_project_follows do lệnh này tạo (an toàn — user_public_projects không bị đụng)}
                            {--dry-run : Chỉ đếm, không ghi}';

    protected $description = 'Backfill user_project_follows từ user_public_projects, chỉ với dự án đã nối chính xác (projects.public_project_id)';

    public function handle(): int
    {
        if ($this->option('rollback')) {
            $n = UserProjectFollow::query()->delete();
            $this->info("Đã xoá {$n} dòng user_project_follows. user_public_projects giữ nguyên.");

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');

        $linkedProjects = Project::query()->whereNotNull('public_project_id')->pluck('id', 'public_project_id');
        if ($linkedProjects->isEmpty()) {
            $this->warn('Chưa có dự án nào nối với danh mục công khai (projects.public_project_id) — không có gì để backfill.');

            return self::SUCCESS;
        }

        $rows = DB::table('user_public_projects')
            ->whereIn('public_project_id', $linkedProjects->keys())
            ->select('user_id', 'public_project_id', 'created_at')
            ->get();

        $created = 0;
        foreach ($rows as $row) {
            $projectId = $linkedProjects[$row->public_project_id] ?? null;
            if ($projectId === null) {
                continue;
            }

            if ($dryRun) {
                $created++;

                continue;
            }

            $follow = UserProjectFollow::firstOrNew(['user_id' => $row->user_id, 'project_id' => $projectId]);
            if (! $follow->exists) {
                $follow->followed_at = $row->created_at ?? now();
                $follow->save();
                $created++;
            }
        }

        $this->info(($dryRun ? '[dry-run] ' : '')."Backfill {$created} follow, qua {$linkedProjects->count()} dự án đã nối.");

        return self::SUCCESS;
    }
}
