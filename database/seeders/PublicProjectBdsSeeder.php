<?php

namespace Database\Seeders;

use App\Models\PublicProject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seed metadata dự án PUBLIC từ dữ liệu thu thập batdongsan.com.vn.
 * Nguồn: database/seeders/data/bds_projects.json (thu thập 2026-07-27).
 * Idempotent: upsert theo `code`. Chỉ metadata thư mục công khai.
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
            $code = $this->codeFrom($url, $name);
            [$apartments, $blocks, $area] = $this->parseConfigs($r['configs'] ?? []);

            PublicProject::updateOrCreate(
                ['code' => $code],
                [
                    'name'           => $name,
                    'developer_name' => $this->developer($r),
                    'address'        => $r['location'] ?? null,
                    'province'       => $this->province($r['location'] ?? ''),
                    'project_type'   => $this->projectType($url),
                    'status'         => $this->status($r['status'] ?? ''),
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

    private function codeFrom(string $url, string $name): string
    {
        if (preg_match('/pj(\d+)/i', $url, $m)) {
            return 'BDS-PJ'.$m[1];
        }
        return 'BDS-'.Str::upper(Str::slug($name));
    }

    /** Trả [apartments, blocks, area_string|null]. */
    private function parseConfigs(array $configs): array
    {
        $area = null;
        $nums = [];
        foreach ($configs as $c) {
            $c = trim((string) $c);
            if ($c === '') {
                continue;
            }
            if (preg_match('/(ha|m²|m2)/iu', $c)) {
                $area = $c;
                continue;
            }
            // số nguyên (bỏ dấu phân cách nghìn '.')
            $int = (int) preg_replace('/\D/', '', $c);
            if ($int > 0) {
                $nums[] = $int;
            }
        }
        $apartments = $nums[0] ?? 0;
        $blocks     = $nums[1] ?? 0;
        return [$apartments, $blocks, $area];
    }

    private function developer(array $r): ?string
    {
        if (! empty($r['developer'])) {
            return $this->tidy($r['developer']);
        }
        $s = $r['summary'] ?? '';
        foreach ([
            '/do (.+?) làm chủ đầu tư/iu',
            '/do (.+?) (?:phát triển|làm đơn vị phát triển)/iu',
            '/Chủ đầu tư (.+?)(?: tiếp tục| triển khai| tọa lạc|,|\.)/iu',
        ] as $re) {
            if (preg_match($re, $s, $m)) {
                return $this->tidy($m[1]);
            }
        }
        return null;
    }

    private function tidy(string $s): string
    {
        return Str::limit(trim(preg_replace('/\s+/u', ' ', $s)), 250, '');
    }

    private function province(string $location): ?string
    {
        if ($location === '') {
            return null;
        }
        $parts = array_map('trim', explode(',', $location));
        return end($parts) ?: null;
    }

    private function projectType(string $url): ?string
    {
        $map = [
            'can-ho-chung-cu' => 'Căn hộ chung cư',
            'khu-do-thi'      => 'Khu đô thị',
            'nha-o-xa-hoi'    => 'Nhà ở xã hội',
            'shophouse'       => 'Shophouse / Nhà phố',
            'khu-phuc-hop'    => 'Khu phức hợp',
            'biet-thu'        => 'Biệt thự / Liền kề',
        ];
        foreach ($map as $key => $label) {
            if (str_contains($url, $key)) {
                return $label;
            }
        }
        return null;
    }

    private function status(string $raw): string
    {
        $raw = mb_strtolower($raw, 'UTF-8');
        return match (true) {
            str_contains($raw, 'bàn giao')    => 'handover',
            str_contains($raw, 'đang mở bán') => 'selling',
            str_contains($raw, 'sắp mở bán')  => 'planning',
            default                            => 'planning', // "đang cập nhật" / khác
        };
    }
}
