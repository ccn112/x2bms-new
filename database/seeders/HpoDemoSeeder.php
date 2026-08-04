<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\Company;
use App\Models\Project;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

/**
 * Seed tòa HPO (Happy One) — onboarding CHUẨN một khách hàng demo rồi nạp file thông
 * báo phí thật kỳ 05/2026. Tách riêng tenant `HPO-DEMO` (không đụng tenant khác).
 *
 *   git pull && php artisan db:seed --class=HpoDemoSeeder --force
 *
 * Dựng đủ 4 tầng để hiện đúng ở /sa + /hq: Tenant (Công ty) → Company (Công ty vận
 * hành) → Project (gắn company_id) → Building. Lệnh import chỉ tạo scaffold TỐI THIỂU
 * (thiếu Company + company_id=NULL), nên seeder này BỔ SUNG phần đó trước khi nạp phí.
 *
 * Idempotent: firstOrCreate theo mã + update company_id; import theo natural key.
 *
 * Đối soát kỳ vọng: 1.314 bảng kê · TỔNG 1.377.819.071 đ
 * (Phí quản lý 1.162.216.371 · Nước 109.862.700 · Xe 105.740.000).
 */
class HpoDemoSeeder extends Seeder
{
    private const FILE = 'seeders/data/import_thong_bao_phi_HPO_202605.xlsx';

    public function run(): void
    {
        // 1) Onboarding: Tenant (Công ty) → Company (Công ty vận hành) → Project → Building.
        $tenant = Tenant::firstOrCreate(
            ['code' => 'HPO-DEMO'],
            ['name' => 'HPO Demo (di trú)'],
        );
        $tenant->forceFill([
            'short_name' => 'HPO',
            'tax_code' => $tenant->tax_code ?? '0300000000',
            'phone' => $tenant->phone ?? '1900 0000',
            'email' => $tenant->email ?? 'contact@happyone.vn',
            'address' => $tenant->address ?? 'TP. Hồ Chí Minh',
            'city' => $tenant->city ?? 'TP. Hồ Chí Minh',
            'legal_representative' => $tenant->legal_representative ?? 'BQL Happy One',
            'plan' => $tenant->plan ?? 'standard',
            'status' => 'active',
        ])->save();

        $company = Company::firstOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'CO-HPO'],
            [
                'name' => 'Công ty Quản lý Vận hành Happy One (Demo)',
                'short_name' => 'Happy One OM',
                'tax_code' => '0300000000-001',
                'phone' => '1900 0000',
                'email' => 'om@happyone.vn',
                'address' => 'TP. Hồ Chí Minh',
                'legal_representative' => 'Giám đốc Vận hành Happy One',
                'status' => 'active',
            ],
        );

        $project = Project::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'PRJ-HPO-DEMO'],
            ['name' => 'Happy One'],
        );
        $project->forceFill([
            'company_id' => $company->id,                 // "Công ty vận hành" — trước đây NULL
            'name' => 'Happy One',
            'type' => $project->type ?? 'apartment',
            'status' => 'active',
            'city' => $project->city ?? 'TP. Hồ Chí Minh',
            'building_count' => max(1, (int) $project->building_count),
            'apartment_count' => 1314,
            'investor' => $project->investor ?? 'Happy One',
        ])->save();

        Building::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'project_id' => $project->id, 'code' => 'BLD-HPO-DEMO'],
            ['name' => 'Happy One — Tòa A'],
        );

        $this->command?->info("Onboarding HPO: tenant #{$tenant->id} · company #{$company->id} (Công ty vận hành) · project #{$project->id} (đã gắn company_id).");

        // 2) Nạp phí thật từ file đã commit (import tìm thấy scaffold sẵn → chỉ ghi lines).
        $path = database_path(self::FILE);
        if (! is_file($path)) {
            $this->command?->error("Không thấy file HPO: {$path}. Kiểm tra đã git pull chưa.");

            return;
        }

        $this->command?->info('Nạp thông báo phí HPO (kỳ 202605)…');
        Artisan::call('billing:import-fee-notification-demo', [
            'file' => $path,
            '--commit' => true,
            '--force' => true,
        ], $this->command?->getOutput());
    }
}
