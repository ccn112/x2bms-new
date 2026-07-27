<?php

namespace App\Console\Commands;

use App\Services\Projects\BdsProjectImporter;
use Illuminate\Console\Command;

/**
 * Lấy tiếp metadata dự án từ batdongsan.com.vn cho các khu vực.
 * Cùng service với nút "Lấy tiếp" ở SuperAdmin (/sa). Dùng cho cron/CLI.
 *
 * Ví dụ: php artisan projects:fetch-more --pages=1 --city=ha-noi
 *        php artisan projects:fetch-more --pages=3            (tất cả khu vực)
 */
class FetchMoreProjects extends Command
{
    protected $signature = 'projects:fetch-more {--pages=3 : Số trang lấy mỗi khu vực} {--city=* : Key khu vực (bỏ trống = tất cả)} {--no-detail : Không làm giàu từ trang chi tiết}';

    protected $description = 'Thu thập tiếp dự án batdongsan.com.vn (upsert public_projects)';

    public function handle(BdsProjectImporter $importer): int
    {
        $pages  = max(1, (int) $this->option('pages'));
        $cities = (array) $this->option('city');
        if (empty($cities)) {
            $cities = array_keys(config('bds.cities', []));
        }
        $enrich = ! $this->option('no-detail');

        $this->info('Lấy tiếp '.$pages.' trang cho: '.implode(', ', $cities).($enrich ? ' (kèm chi tiết)' : ' (không chi tiết)'));

        $results = $importer->fetchMore($cities, $pages, $enrich);

        $blocked = false;
        foreach ($results as $key => $r) {
            $line = sprintf(
                '  %-10s | +%d mới, ~%d cập nhật, %d trang%s',
                $key,
                $r['added'],
                $r['updated'],
                $r['pagesFetched'],
                $r['stoppedReason'] ? '  (dừng: '.$r['stoppedReason'].')' : ''
            );
            if ($r['stoppedReason'] === 'blocked') {
                $blocked = true;
                $this->warn($line);
            } else {
                $this->line($line);
            }
        }

        if ($blocked) {
            $this->warn('Có khu vực bị chặn (Cloudflare). Cân nhắc đặt BDS_TRANSPORT=curl hoặc chạy lại sau / dùng proxy.');
        }

        return self::SUCCESS;
    }
}
