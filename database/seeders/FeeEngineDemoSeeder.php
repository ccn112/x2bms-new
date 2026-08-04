<?php

namespace Database\Seeders;

use App\Models\Apartment;
use App\Models\BillingPeriod;
use App\Models\Building;
use App\Models\FeeRate;
use App\Models\FeeType;
use App\Models\Statement;
use App\Models\StatementLine;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

/**
 * Dữ liệu TEST NGHIỆP VỤ engine tính phí (C/P2.1) — TRÊN TÒA HPO.
 * Dựng kịch bản đối soát "số vàng" chạy được ngay:
 *  - fee_rate phí quản lý 15.000đ/m² (effective 2026-01-01).
 *  - 3 căn test có DIỆN TÍCH biết trước (ENG-01/02/03) — tách khỏi 1314 căn import
 *    (căn import không có area nên engine bỏ qua, không đụng số thật).
 *  - Kỳ 2026-08 + số KẾ TOÁN "vàng" (source=import): 2 căn khớp area×giá, 1 căn (ENG-03)
 *    LỆCH cố ý 150.000đ để chứng minh `billing:reconcile-engine` bắt được lệch thật.
 *
 * Sau seed:
 *   php artisan billing:run <hpo_building_id> 2026-08                # dry-run
 *   php artisan billing:reconcile-engine <hpo_building_id> 2026-08   # đối soát vàng
 *
 * PHỤ THUỘC: HpoDemoSeeder (tenant HPO-DEMO + tòa + fee_type quản lý). Idempotent.
 */
class FeeEngineDemoSeeder extends Seeder
{
    private const RATE = 15000;        // đồng/m²
    private const PERIOD = '2026-08';

    /** [mã căn, diện tích, số kế toán "vàng"] — ENG-03 lệch cố ý (đúng phải 750.000). */
    private const APARTMENTS = [
        ['ENG-01', 100.0, 1_500_000],   // khớp: 100 × 15.000
        ['ENG-02', 75.5, 1_132_500],    // khớp: 75,5 × 15.000
        ['ENG-03', 50.0, 900_000],      // LỆCH THẬT: engine tính 750.000, kế toán ghi 900.000
    ];

    public function run(): void
    {
        $tenant = Tenant::where('code', 'HPO-DEMO')->first();
        $building = $tenant ? Building::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('code', 'BLD-HPO-DEMO')->first() : null;
        if ($building === null) {
            $this->command?->warn('Chưa có tòa HPO. Chạy HpoDemoSeeder trước.');

            return;
        }

        $feeType = FeeType::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)->where('category', 'management')->orderBy('id')->first()
            ?? FeeType::withoutGlobalScopes()->create([
                'tenant_id' => $tenant->id, 'code' => 'PQL', 'name' => 'Phí quản lý',
                'category' => 'management', 'unit' => 'per_sqm', 'status' => 'active', 'vat_percent' => 0,
            ]);
        // Đảm bảo active để engine resolve được.
        $feeType->forceFill(['status' => 'active'])->save();

        FeeRate::withoutGlobalScopes()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'fee_type_id' => $feeType->id, 'code' => 'QL-ENG-2026'],
            ['name' => 'Giá quản lý 2026 (engine test)', 'amount' => self::RATE, 'unit' => 'per_sqm', 'effective_from' => '2026-01-01', 'status' => 'active'],
        );

        $period = BillingPeriod::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => self::PERIOD],
            ['label' => 'Tháng 8/2026', 'period_month' => self::PERIOD.'-01'],
        );

        foreach (self::APARTMENTS as [$code, $area, $golden]) {
            $apt = Apartment::withoutGlobalScopes()->updateOrCreate(
                ['tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => $code],
                ['area_sqm' => $area, 'status' => 'occupied'],
            );

            // Số KẾ TOÁN "vàng" (source=import, đã phát hành) để engine đối soát.
            $stmt = Statement::withoutGlobalScopes()->firstOrCreate(
                ['tenant_id' => $tenant->id, 'building_id' => $building->id, 'apartment_id' => $apt->id, 'billing_period_id' => $period->id],
                ['total_amount' => $golden, 'paid_amount' => 0, 'status' => 'issued', 'approval_status' => Statement::APPROVAL_PUBLISHED, 'published_at' => now()],
            );
            StatementLine::withoutGlobalScopes()->updateOrCreate(
                ['statement_id' => $stmt->id, 'fee_type_id' => $feeType->id, 'service_period_start' => self::PERIOD.'-01'],
                [
                    'fee_type' => 'Phí quản lý', 'fee_category' => 'management',
                    'service_period_end' => self::PERIOD.'-31', 'amount' => $golden, 'paid_amount' => 0,
                    'status' => 'issued', 'source' => 'import',
                ],
            );
            $stmt->forceFill(['total_amount' => (int) StatementLine::withoutGlobalScopes()->where('statement_id', $stmt->id)->sum('amount')])->save();
        }

        $this->command?->info('Engine test HPO: 3 căn (ENG-01/02/03, area 100/75.5/50) + giá 15.000/m² + số kế toán vàng kỳ '.self::PERIOD.' (ENG-03 lệch cố ý 150.000).');
        $this->command?->info("Chạy: php artisan billing:reconcile-engine {$building->id} ".self::PERIOD);
    }
}
