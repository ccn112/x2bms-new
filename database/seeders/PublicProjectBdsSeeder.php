<?php

namespace Database\Seeders;

use App\Models\PublicProject;
use App\Services\Projects\BdsProjectImporter;
use Illuminate\Database\Seeder;

/**
 * Seed metadata dự án PUBLIC từ dữ liệu thu thập batdongsan.com.vn.
 * Nguồn: database/seeders/data/bds_projects.json (thu thập 2026-07-27).
 * Idempotent: upsert theo `code`. Chỉ metadata thư mục công khai.
 *
 * Logic chuẩn hoá dùng chung với BdsProjectImporter (nút "Lấy tiếp" ở /sa).
 */
class PublicProjectBdsSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/bds_projects.json');
        if (! is_file($path)) {
            $this->command?->warn("Không thấy $path — bỏ qua.");

            return;
        }

        $rows = json_decode(file_get_contents($path), true) ?: [];
        $n = 0;

        foreach ($rows as $r) {
            $name = trim($r['name'] ?? '');
            if ($name === '') {
                continue;
            }

            $url  = $r['url'] ?? '';
            $code = BdsProjectImporter::codeFrom($url, $name);
            [$apartments, $blocks, $area] = BdsProjectImporter::parseConfigs($r['configs'] ?? []);
            $addr = BdsProjectImporter::parseAddress($r['location'] ?? '');
            $developerName = BdsProjectImporter::developer($r);
            $developerId = $developerName
                ? optional(\App\Models\Developer::upsertByName($developerName, ['source' => 'batdongsan.com.vn']))->id
                : null;

            PublicProject::updateOrCreate(
                ['code' => $code],
                [
                    'name'           => $name,
                    'developer_name' => $developerName,
                    'developer_id'   => $developerId,
                    'address'        => $r['location'] ?? null,
                    'ward'           => $addr['ward'],
                    'district'       => $addr['district'],
                    'province'       => $addr['province'],
                    'project_type'   => BdsProjectImporter::projectType($url),
                    'status'         => BdsProjectImporter::status($r['status'] ?? ''),
                    'blocks'         => $blocks,
                    'apartments'     => $apartments,
                    'description'    => $r['summary'] ?? null,
                    'is_public'      => true,
                    'metadata_json'  => [
                        'source'      => 'batdongsan.com.vn',
                        'city'        => $r['city'] ?? null,
                        'source_url'  => $url ? 'https://batdongsan.com.vn'.$url : null,
                        'image'       => $r['img'] ?? null,
                        'area'        => $area,
                        'configs_raw' => $r['configs'] ?? [],
                        'status_raw'  => $r['status'] ?? null,
                        'imported_at' => '2026-07-27',
                    ],
                ],
            );
            $n++;
        }

        $this->command?->info("PublicProjectBdsSeeder: upsert $n dự án.");
    }
}
