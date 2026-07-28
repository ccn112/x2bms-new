<?php

namespace App\Console\Commands;

use App\Models\PublicProject;
use App\Services\Projects\BdsProjectImporter;
use Illuminate\Console\Command;

/**
 * Backfill ẢNH + TOẠ ĐỘ + CHI TIẾT cho dự án CŨ còn thiếu (lấy trước khi có enrich,
 * hoặc enrich bị skip/chặn). Lặp public_projects có source_url mà thiếu images/detail,
 * gọi lại BdsProjectImporter::enrichDetail(). Idempotent, có delay, bỏ qua êm khi bị chặn.
 *
 * Ví dụ: php artisan projects:enrich-missing --limit=400
 *        php artisan projects:enrich-missing --only=detail --limit=200
 */
class EnrichMissingProjects extends Command
{
    protected $signature = 'projects:enrich-missing {--limit=300 : Số dự án xử lý mỗi lần} {--only=images : images|detail|all — tiêu chí "còn thiếu"}';

    protected $description = 'Bổ sung ảnh/toạ độ/chi tiết cho dự án batdongsan còn thiếu (enrichDetail)';

    public function handle(BdsProjectImporter $importer): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $only  = (string) $this->option('only');
        $delay = (int) config('bds.delay_ms', 400);

        $query = PublicProject::query()
            ->whereNotNull('metadata_json->source_url')
            ->where(function ($q) use ($only) {
                if ($only === 'detail') {
                    $q->whereNull('metadata_json->detail');
                } elseif ($only === 'all') {
                    $q->whereNull('metadata_json->images')->orWhereNull('metadata_json->detail');
                } else { // images (default)
                    $q->whereNull('metadata_json->images');
                }
            })
            ->orderBy('id');

        $total = (clone $query)->count();
        $rows = $query->limit($limit)->get();
        $this->info("Còn thiếu ($only): $total dự án. Xử lý ".$rows->count()." dự án lần này.");

        $ok = 0;
        $blocked = 0;
        $bar = $this->output->createProgressBar($rows->count());
        $bar->start();

        foreach ($rows as $p) {
            try {
                $done = $importer->enrichDetail($p);
                $done ? $ok++ : $blocked++;
            } catch (\Throwable $e) {
                $blocked++;
            }
            $bar->advance();
            if ($delay > 0) {
                usleep($delay * 1000);
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Xong: enrich OK=$ok, bỏ qua/chặn=$blocked.");
        if ($blocked > 0) {
            $this->warn('Có dự án bị chặn/rỗng (Cloudflare) — chạy lại lệnh sau để tiếp tục (idempotent).');
        }

        return self::SUCCESS;
    }
}
