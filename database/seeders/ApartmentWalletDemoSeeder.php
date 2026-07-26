<?php

namespace Database\Seeders;

use App\Models\Apartment;
use App\Models\ApartmentWallet;
use App\Models\ApartmentWalletBucket;
use App\Models\ApartmentWalletTransaction;
use App\Models\FeeType;
use App\Models\Statement;
use App\Models\StatementLine;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Demo ví cư dân theo căn hộ:
 *  - Gắn cờ phí ƯU TIÊN (điện/xe = is_critical) + thứ tự trừ (payment_priority).
 *  - Backfill statement_lines: fee_category + fee_type_id + status (nợ per-service).
 *  - Tạo ví/ngăn/giao dịch mẫu cho vài căn hộ để app hiển thị.
 *
 * Trạng thái TĨNH (không gọi service settlement) để dữ liệu demo dễ kiểm.
 */
class ApartmentWalletDemoSeeder extends Seeder
{
    /** label statement_line → [category, feeType code, is_critical, priority, enforcement]. */
    private const FEE_MAP = [
        'Phí quản lý' => ['management', 'QL', false, 50, null],
        'Phí vệ sinh' => ['service', 'RAC', false, 60, null],
        'Phí gửi ô tô' => ['parking', 'OTO', true, 10, 'lock_parking_card'],
        'Phí gửi xe máy' => ['parking', 'XEMAY', true, 20, 'lock_parking_card'],
        'Phí nước sinh hoạt' => ['utility', 'NUOC', false, 40, null],
        'Tiền điện' => ['utility', 'DIEN', true, 5, 'cut_power'],
        'Tiền nước' => ['utility', 'NUOC', false, 40, null],
    ];

    public function run(): void
    {
        FeeType::query()->withoutGlobalScopes()->get()->each(function (FeeType $ft) {
            [$critical, $priority, $enforce] = $this->flagsForCode($ft->code, $ft->category);
            $ft->forceFill([
                'is_critical' => $critical,
                'payment_priority' => $priority,
                'enforcement_action' => $enforce,
            ])->saveQuietly();
        });

        // Đảm bảo có loại phí "Tiền điện" (critical) cho mỗi tenant có QL.
        FeeType::query()->withoutGlobalScopes()->where('code', 'QL')->get()->each(function (FeeType $ql) {
            FeeType::withoutGlobalScopes()->firstOrCreate(
                ['tenant_id' => $ql->tenant_id, 'code' => 'DIEN'],
                [
                    'name' => 'Tiền điện', 'category' => 'utility', 'unit' => 'per_unit',
                    'is_recurring' => true, 'status' => 'active', 'is_critical' => true,
                    'payment_priority' => 5, 'enforcement_action' => 'cut_power',
                ],
            );
        });

        // Backfill statement_lines theo label.
        StatementLine::query()->withoutGlobalScopes()->with('statement')->chunkById(500, function ($lines) {
            foreach ($lines as $line) {
                $map = self::FEE_MAP[$line->fee_type] ?? null;
                if (! $map) {
                    continue;
                }
                [$category, $code] = $map;
                $tenantId = $line->statement?->tenant_id;
                $ft = $tenantId
                    ? FeeType::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('code', $code)->first()
                    : null;
                $paid = (string) ($line->paid_amount ?? 0);
                $status = bccomp($paid, '0', 2) <= 0 ? 'issued'
                    : (bccomp($paid, (string) $line->amount, 2) >= 0 ? 'paid' : 'partial');
                $line->forceFill([
                    'fee_category' => $category,
                    'fee_type_id' => $ft?->id,
                    'status' => $status,
                ])->saveQuietly();
            }
        });

        // Ví mẫu cho tối đa 6 căn hộ có statement.
        $apartmentIds = Statement::query()->withoutGlobalScopes()
            ->whereNotNull('apartment_id')->distinct()->limit(6)->pluck('apartment_id');

        foreach ($apartmentIds as $i => $apartmentId) {
            $apartment = Apartment::withoutGlobalScopes()->find($apartmentId);
            if (! $apartment) {
                continue;
            }
            $wallet = ApartmentWallet::firstOrCreate(
                ['apartment_id' => $apartment->id],
                [
                    'tenant_id' => $apartment->tenant_id,
                    'building_id' => $apartment->building_id,
                    'currency' => 'VND', 'balance' => 1_500_000, 'status' => 'active',
                ],
            );

            // Ngăn: tiền thừa điện/nước (utility) + ngăn ô tô (parking, fee_type OTO nếu có).
            ApartmentWalletBucket::firstOrCreate(
                ['wallet_id' => $wallet->id, 'fee_category' => 'utility', 'fee_type_id' => null],
                ['tenant_id' => $wallet->tenant_id, 'balance' => 800_000],
            );
            $oto = FeeType::withoutGlobalScopes()->where('tenant_id', $wallet->tenant_id)->where('code', 'OTO')->first();
            if ($oto) {
                ApartmentWalletBucket::firstOrCreate(
                    ['wallet_id' => $wallet->id, 'fee_category' => 'parking', 'fee_type_id' => $oto->id],
                    ['tenant_id' => $wallet->tenant_id, 'balance' => 1_200_000],
                );
            }

            // Giao dịch mẫu: 1 phiếu thu vào + 1 hạch toán ra.
            $total = $wallet->availableTotal();
            ApartmentWalletTransaction::firstOrCreate(
                ['wallet_id' => $wallet->id, 'type' => 'receipt', 'reference_no' => 'PT-DEMO-'.$apartment->id],
                [
                    'tenant_id' => $wallet->tenant_id, 'apartment_id' => $apartment->id,
                    'direction' => 'in', 'amount' => 3_500_000, 'balance_after' => $total,
                    'description' => 'Phiếu thu nộp tiền vào ví', 'status' => 'confirmed',
                    'posted_at' => Carbon::parse('2026-07-10 09:00:00'),
                ],
            );
            ApartmentWalletTransaction::firstOrCreate(
                ['wallet_id' => $wallet->id, 'type' => 'debt_settlement', 'reference_no' => 'HT-DEMO-'.$apartment->id],
                [
                    'tenant_id' => $wallet->tenant_id, 'apartment_id' => $apartment->id,
                    'direction' => 'out', 'fee_category' => 'utility', 'amount' => 1_200_000,
                    'balance_after' => $total, 'description' => 'Hạch toán trả phí điện tháng 6',
                    'status' => 'confirmed', 'posted_at' => Carbon::parse('2026-07-10 09:05:00'),
                ],
            );
        }
    }

    /** @return array{0:bool,1:int,2:?string} */
    private function flagsForCode(?string $code, ?string $category): array
    {
        return match ($code) {
            'DIEN' => [true, 5, 'cut_power'],
            'OTO' => [true, 10, 'lock_parking_card'],
            'XEMAY' => [true, 20, 'lock_parking_card'],
            'NUOC' => [false, 40, null],
            'QL' => [false, 50, null],
            'RAC' => [false, 60, null],
            default => [$category === 'parking', $category === 'parking' ? 15 : 100, $category === 'parking' ? 'lock_parking_card' : null],
        };
    }
}
