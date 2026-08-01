<?php

namespace Database\Seeders;

use App\Models\Apartment;
use App\Models\Building;
use App\Models\BillingPeriod;
use App\Models\FeeType;
use App\Models\Project;
use App\Models\Statement;
use App\Models\StatementLine;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Phát hành một bảng kê ĐÃ PHÁT HÀNH cho căn demo `DP-08.12` (Đại Phúc Riverside,
 * ~apartment 1305) — tài khoản demo có 0 statement nên màn Hoá đơn/Ví rỗng, không
 * soi được chi tiết + per-fee-type (verify live 2026-08-01).
 *
 * Idempotent: bỏ qua nếu căn đã có statement; fee type + kỳ phí dùng
 * firstOrCreate. Chạy: `php artisan db:seed --class=Resident1305StatementSeeder`.
 */
class Resident1305StatementSeeder extends Seeder
{
    public function run(): void
    {
        $project = Project::withoutGlobalScopes()->where('code', 'DAIPHUC-RS')->first();
        if ($project === null) {
            $this->command?->warn('Không thấy project DAIPHUC-RS — bỏ qua.');

            return;
        }

        $building = Building::withoutGlobalScopes()
            ->where('project_id', $project->id)->orderBy('id')->first();
        $apartment = Apartment::withoutGlobalScopes()
            ->where('code', 'DP-08.12')->orderBy('id')->first();
        if ($building === null || $apartment === null) {
            $this->command?->warn('Không thấy building/căn DP-08.12 — bỏ qua.');

            return;
        }

        // Idempotent: đã có bảng kê thì thôi.
        if (Statement::withoutGlobalScopes()->where('apartment_id', $apartment->id)->exists()) {
            $this->command?->info('Căn DP-08.12 đã có bảng kê — bỏ qua.');

            return;
        }

        $tenantId = $project->tenant_id;

        $ql = FeeType::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenantId, 'code' => 'QL'],
            ['name' => 'Phí quản lý'],
        );
        $rac = FeeType::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenantId, 'code' => 'RAC'],
            ['name' => 'Phí vệ sinh'],
        );

        $period = BillingPeriod::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenantId, 'building_id' => $building->id, 'code' => '2026-07'],
            [
                'label' => 'Tháng 7/2026',
                'period_month' => Carbon::parse('2026-07-01'),
                'status' => 'published',
                'is_current' => true,
                'due_date' => Carbon::parse('2026-07-15'),
            ],
        );

        $area = (float) ($apartment->area_sqm ?? 88);
        $qlAmount = round($area * 16_500);
        $racAmount = 50_000;
        $total = $qlAmount + $racAmount;

        $statement = Statement::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId,
            'building_id' => $building->id,
            'billing_period_id' => $period->id,
            'apartment_id' => $apartment->id,
            'code' => 'BK-DP-2607',
            'total_amount' => $total,
            'paid_amount' => 0,
            'status' => 'issued',
            'approval_status' => 'published',
            'issued_at' => Carbon::parse('2026-07-01 08:00'),
            'published_at' => Carbon::parse('2026-07-01 10:00'),
            'due_date' => Carbon::parse('2026-07-15'),
        ]);

        StatementLine::withoutGlobalScopes()->create([
            'statement_id' => $statement->id, 'fee_type_id' => $ql->id,
            'fee_type' => 'Phí quản lý', 'quantity' => $area, 'unit_price' => 16_500,
            'amount' => $qlAmount,
        ]);
        StatementLine::withoutGlobalScopes()->create([
            'statement_id' => $statement->id, 'fee_type_id' => $rac->id,
            'fee_type' => 'Phí vệ sinh', 'quantity' => 1, 'unit_price' => $racAmount,
            'amount' => $racAmount,
        ]);

        $this->command?->info("Đã phát hành bảng kê BK-DP-2607 ({$total}đ) cho căn DP-08.12.");
    }
}
