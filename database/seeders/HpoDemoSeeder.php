<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

/**
 * Seed tòa HPO (Happy One) — nạp file thông báo phí thật kỳ 05/2026 vào tenant demo
 * RIÊNG `HPO-DEMO` (không đụng dữ liệu tenant khác). Bọc lệnh
 * `billing:import-fee-notification-demo` với file đã commit trong repo để deploy gọn:
 *
 *   git pull && php artisan db:seed --class=HpoDemoSeeder --force
 *
 * Idempotent: command tự dựng scaffold (firstOrCreate) + import theo natural key nên
 * chạy lại KHÔNG nhân đôi. `--force` để chạy được cả trên production.
 *
 * Đối soát kỳ vọng: 1.314 bảng kê · TỔNG 1.377.819.071 đ
 * (Phí quản lý 1.162.216.371 · Nước 109.862.700 · Xe 105.740.000).
 */
class HpoDemoSeeder extends Seeder
{
    private const FILE = 'seeders/data/import_thong_bao_phi_HPO_202605.xlsx';

    public function run(): void
    {
        $path = database_path(self::FILE);
        if (! is_file($path)) {
            $this->command?->error("Không thấy file HPO: {$path}. Kiểm tra đã git pull chưa.");

            return;
        }

        $this->command?->info('Nạp tòa HPO (kỳ 202605) vào tenant HPO-DEMO…');
        Artisan::call('billing:import-fee-notification-demo', [
            'file' => $path,
            '--commit' => true,
            '--force' => true,
        ], $this->command?->getOutput());
    }
}
