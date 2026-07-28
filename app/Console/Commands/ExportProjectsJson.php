<?php

namespace App\Console\Commands;

use App\Models\PublicProject;
use Illuminate\Console\Command;

/**
 * Xuất TẤT CẢ dự án nguồn batdongsan ra JSON để đồng bộ local -> server.
 * Trên server chỉ cần: git pull + `php artisan db:seed --class=PublicProjectImportSeeder`
 * (KHÔNG gọi batdongsan, tránh Cloudflare chặn).
 *
 * Ví dụ: php artisan projects:export-json
 */
class ExportProjectsJson extends Command
{
    protected $signature = 'projects:export-json {--path=database/seeders/data/public_projects_export.json : Đường dẫn file xuất}';

    protected $description = 'Dump public_projects (nguồn batdongsan) ra JSON cho seeder đồng bộ';

    public function handle(): int
    {
        $cols = [
            'code', 'name', 'developer_name', 'address', 'ward', 'district', 'province',
            'latitude', 'longitude', 'project_type', 'status', 'blocks', 'apartments',
            'amenities_json', 'description', 'is_public', 'metadata_json',
        ];

        $path = $this->option('path');
        $abs = str_starts_with($path, '/') || preg_match('/^[A-Za-z]:/', $path) ? $path : base_path($path);

        $dir = dirname($abs);
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        // Ghi STREAM theo chunk để không nạp toàn bộ 6k dự án + metadata vào RAM
        // (trước đây ->get()->map()->all() làm hết 128MB). Tự quản dấu phẩy để
        // tạo 1 mảng JSON hợp lệ; mỗi record encode riêng.
        $fh = fopen($abs, 'w');
        fwrite($fh, "[\n");

        $count = 0;
        $withDetail = 0;
        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

        PublicProject::query()
            ->with('developer')
            ->where('metadata_json->source', 'batdongsan.com.vn')
            ->orderBy('code')
            ->chunkById(200, function ($chunk) use ($fh, $cols, $flags, &$count, &$withDetail) {
                foreach ($chunk as $p) {
                    $row = $p->only($cols);
                    $row['developer'] = $p->developer
                        ? $p->developer->only(['name', 'slug', 'code', 'website', 'logo_path', 'description', 'source', 'metadata_json'])
                        : null;

                    if (! empty($row['metadata_json']['detail'])) {
                        $withDetail++;
                    }

                    fwrite($fh, ($count > 0 ? ",\n" : '').'  '.json_encode($row, $flags));
                    $count++;
                }
            });

        fwrite($fh, "\n]\n");
        fclose($fh);

        $this->info('Đã xuất '.$count.' dự án -> '.$abs.' ('.$withDetail.' có detail).');

        return self::SUCCESS;
    }
}
