<?php

namespace Database\Seeders;

use App\Services\Address\AddressResolver;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Nạp bộ tra cứu ĐỊA CHỈ MỚI 2025 (NQ 202/2025/QH15) vào 4 bảng admin_*_2025.
 * Idempotent: upsert theo code / xoá-nạp lại bảng ánh xạ. Nguồn JSON ở
 * database/seeders/data/admin_2025/ (xem docs/dev/03_data_arch/ADDRESS-2025.md).
 */
class Admin2025Seeder extends Seeder
{
    protected string $dir;

    public function run(): void
    {
        $this->dir = database_path('seeders/data/admin_2025');

        $this->seedProvinces();
        $this->seedWards();
        $this->seedOldProvinces();
        $this->seedOldToNew();

        $this->command?->info(sprintf(
            'admin_provinces_2025=%d, admin_wards_2025=%d, admin_old_provinces_2025=%d, admin_old_to_new=%d',
            DB::table('admin_provinces_2025')->count(),
            DB::table('admin_wards_2025')->count(),
            DB::table('admin_old_provinces_2025')->count(),
            DB::table('admin_old_to_new')->count(),
        ));
    }

    protected function load(string $file): array
    {
        $path = $this->dir.DIRECTORY_SEPARATOR.$file;
        $data = json_decode(file_get_contents($path), true);

        return is_array($data) ? $data : [];
    }

    protected function seedProvinces(): void
    {
        $now = now();
        $rows = [];
        foreach ($this->load('new_provinces.json') as $p) {
            $rows[] = [
                'code' => $p['id'],
                'full_name' => $p['name'],
                'name_norm' => AddressResolver::normalize($p['name']),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('admin_provinces_2025')->upsert($chunk, ['code'], ['full_name', 'name_norm', 'updated_at']);
        }
    }

    protected function seedWards(): void
    {
        $now = now();
        $rows = [];
        foreach ($this->load('new_wards.json') as $w) {
            $rows[] = [
                'code' => $w['id'],
                'full_name' => $w['name'],
                'name_norm' => AddressResolver::normalize($w['name']),
                'province_code' => $w['province_id'],
                'province_name' => $w['province_name'] ?? '',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        foreach (array_chunk($rows, 1000) as $chunk) {
            DB::table('admin_wards_2025')->upsert(
                $chunk,
                ['code'],
                ['full_name', 'name_norm', 'province_code', 'province_name', 'updated_at']
            );
        }
    }

    protected function seedOldProvinces(): void
    {
        $now = now();
        $data = $this->load('old_province_to_new.json');
        $rows = [];
        foreach (($data['map'] ?? []) as $m) {
            $rows[] = [
                'old_name' => $m['old'],
                'old_name_norm' => AddressResolver::normalize($m['old']),
                'new_province_code' => $m['new_code'],
                'new_province_name' => $m['new_name'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('admin_old_provinces_2025')->truncate();
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('admin_old_provinces_2025')->insert($chunk);
        }
    }

    protected function seedOldToNew(): void
    {
        $now = now();

        // Canonical hoá tỉnh: file1 dùng "Mã tỉnh (BNV)" là số thứ tự 01..34 (KHÔNG phải
        // mã BNV thật), nên đối chiếu theo TÊN đã chuẩn hoá về admin_provinces_2025 để lấy
        // đúng mã BNV + tên chuẩn, đồng bộ mã giữa các bảng.
        $canon = [];
        foreach (DB::table('admin_provinces_2025')->get(['code', 'full_name', 'name_norm']) as $p) {
            $canon[$p->name_norm] = ['code' => $p->code, 'name' => $p->full_name];
        }

        $rows = [];
        foreach ($this->load('old_district_to_new_ward.json') as $r) {
            $district = trim((string) ($r['Tên Quận huyện TMS (cũ)'] ?? ''));
            $ward = trim((string) ($r['Tên Phường/Xã mới'] ?? ''));
            $prov = trim((string) ($r['Tên tỉnh/TP mới'] ?? ''));
            if ($ward === '' || $prov === '') {
                continue;
            }
            $c = $canon[AddressResolver::normalize($prov)] ?? null;
            $rows[] = [
                'old_district_name' => $district,
                'old_district_norm' => AddressResolver::normalize($district),
                'new_province_code' => $c['code'] ?? (string) ($r['Mã tỉnh (BNV)'] ?? ''),
                'new_province_name' => $c['name'] ?? $prov,
                'new_ward_code' => isset($r['Mã phường/xã mới ']) ? (string) $r['Mã phường/xã mới '] : null,
                'new_ward_name' => $ward,
                'new_ward_norm' => AddressResolver::normalize($ward),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('admin_old_to_new')->truncate();
        foreach (array_chunk($rows, 1000) as $chunk) {
            DB::table('admin_old_to_new')->insert($chunk);
        }
    }
}
