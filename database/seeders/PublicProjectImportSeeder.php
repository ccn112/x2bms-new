<?php

namespace Database\Seeders;

use App\Models\PublicProject;
use Illuminate\Database\Seeder;

/**
 * Nạp dự án PUBLIC từ file xuất `projects:export-json` (nguồn chính từ nay).
 * Nguồn: database/seeders/data/public_projects_export.json.
 * Idempotent: updateOrCreate theo `code`, ghi thẳng theo cột (KHÔNG gọi batdongsan).
 *
 * Vòng đồng bộ local -> server:
 *   [local]  php artisan projects:fetch-more ...  (thu thập + làm giàu)
 *            php artisan projects:export-json      (dump JSON, commit)
 *   [server] git pull && php artisan db:seed --class=PublicProjectImportSeeder
 */
class PublicProjectImportSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/public_projects_export.json');
        if (! is_file($path)) {
            $this->command?->warn("Không thấy $path — chạy `php artisan projects:export-json` ở local trước.");

            return;
        }

        $rows = json_decode(file_get_contents($path), true) ?: [];
        $n = 0;

        foreach ($rows as $r) {
            $code = $r['code'] ?? null;
            if (! $code) {
                continue;
            }

            PublicProject::updateOrCreate(
                ['code' => $code],
                [
                    'name'           => $r['name'] ?? $code,
                    'developer_name' => $r['developer_name'] ?? null,
                    'address'        => $r['address'] ?? null,
                    'province'       => $r['province'] ?? null,
                    'project_type'   => $r['project_type'] ?? null,
                    'status'         => $r['status'] ?? 'planning',
                    'blocks'         => $r['blocks'] ?? 0,
                    'apartments'     => $r['apartments'] ?? 0,
                    'amenities_json' => $r['amenities_json'] ?? null,
                    'description'    => $r['description'] ?? null,
                    'is_public'      => $r['is_public'] ?? true,
                    'metadata_json'  => $r['metadata_json'] ?? null,
                ],
            );
            $n++;
        }

        $this->command?->info("PublicProjectImportSeeder: upsert $n dự án.");
    }
}
