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
            'code', 'name', 'developer_name', 'address', 'province', 'project_type',
            'status', 'blocks', 'apartments', 'amenities_json', 'description',
            'is_public', 'metadata_json',
        ];

        $rows = PublicProject::query()
            ->where('metadata_json->source', 'batdongsan.com.vn')
            ->orderBy('code')
            ->get()
            ->map(fn (PublicProject $p) => $p->only($cols))
            ->values()
            ->all();

        $path = $this->option('path');
        $abs = str_starts_with($path, '/') || preg_match('/^[A-Za-z]:/', $path) ? $path : base_path($path);

        $dir = dirname($abs);
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents($abs, json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $withDetail = collect($rows)->filter(fn ($r) => ! empty($r['metadata_json']['detail']))->count();
        $this->info('Đã xuất '.count($rows).' dự án -> '.$abs.' ('.$withDetail.' có detail).');

        return self::SUCCESS;
    }
}
