<?php

namespace App\Console\Commands;

use App\Models\ProjectMedia;
use App\Models\PublicProject;
use App\Services\Projects\ProjectMediaSync;
use Illuminate\Console\Command;

/**
 * Materialize ProjectMedia (thư viện ảnh) từ metadata_json.images (batdongsan, watermark)
 * + metadata_json.official_images (official). Dedup theo (public_project_id, file_url),
 * đặt 1 ảnh bìa (official_cover → cover_image → ảnh đầu). Idempotent.
 *
 * Ví dụ: php artisan projects:sync-media          (tất cả)
 *        php artisan projects:sync-media --limit=500
 */
class SyncProjectMedia extends Command
{
    protected $signature = 'projects:sync-media {--limit= : Giới hạn số dự án xử lý} {--id=* : Chỉ xử lý public_project id cụ thể}';

    protected $description = 'Đồng bộ ảnh từ metadata_json vào thư viện ProjectMedia (idempotent)';

    public function handle(ProjectMediaSync $sync): int
    {
        $query = PublicProject::query()
            ->where(function ($q) {
                $q->whereNotNull('metadata_json->images')
                    ->orWhereNotNull('metadata_json->official_images')
                    ->orWhereNotNull('metadata_json->cover_image');
            })
            ->orderBy('id');

        if ($ids = (array) $this->option('id')) {
            $ids = array_filter($ids);
            if ($ids) {
                $query->whereIn('id', $ids);
            }
        }
        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        $rows = $query->get();
        $this->info('Đồng bộ media cho '.$rows->count().' dự án...');

        $created = 0;
        $projectsWithMedia = 0;
        $bar = $this->output->createProgressBar($rows->count());
        $bar->start();
        foreach ($rows as $p) {
            $created += $sync->sync($p);
            if ($p->media()->count() > 0) {
                $projectsWithMedia++;
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine(2);

        $totalMedia = ProjectMedia::count();
        $this->info("Xong: +$created media mới. Dự án có ≥1 ảnh (lượt này): $projectsWithMedia. Tổng ProjectMedia: $totalMedia.");

        return self::SUCCESS;
    }
}
