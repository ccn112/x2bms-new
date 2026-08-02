<?php

namespace Database\Seeders;

use App\Models\Apartment;
use App\Models\Building;
use App\Models\BillingPeriod;
use App\Models\FeeType;
use App\Models\Project;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\Statement;
use App\Models\StatementLine;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Demo D6 — CÔNG NỢ THEO DỊCH VỤ cho tài khoản test `test.cudan1@x2bms.vn`.
 *
 * Sinh đúng một cây có thể kiểm bằng mắt:
 *   Phương tiện › Phí gửi ô tô › 51K-838888 › [05/2026, 06/2026, 07/2026] = 4.500.000
 *
 * Mỗi tháng là MỘT dòng phí còn nợ (amount 1.500.000, paid_amount 0), gắn `subject` =
 * chiếc xe, trên bảng kê ĐÃ PHÁT HÀNH (approval_status=published + published_at) để đi
 * qua đúng scope `Statement::scopeVisibleToResident`.
 *
 * Idempotent (firstOrCreate). Tái dùng Project/Building/Apartment/Resident của cudan1 nếu
 * đã có (CommunityTestResidentsSeeder), nếu chưa thì dựng tối thiểu.
 */
class DebtByServiceDemoSeeder extends Seeder
{
    /** Cả hai TK test (Samsung = cudan2) — mỗi TK một biển số để test được ngay. */
    private const ACCOUNTS = [
        ['email' => 'test.cudan1@x2bms.vn', 'name' => 'Cư dân Test 1', 'plate' => '51K-838888'],
        ['email' => 'test.cudan2@x2bms.vn', 'name' => 'Cư dân Test 2', 'plate' => '30F-686868'],
    ];

    /** Ba kỳ dịch vụ đang nợ. */
    private const MONTHS = ['2026-05', '2026-06', '2026-07'];

    private const AMOUNT = 1_500_000;

    public function run(): void
    {
        foreach (self::ACCOUNTS as $acc) {
            $this->seedFor($acc['email'], $acc['name'], $acc['plate']);
        }
    }

    private function seedFor(string $email, string $name, string $plate): void
    {
        [$tenantId, $building, $apartment, $resident] = $this->resolveContext($email, $name);

        // Loại phí gửi ô tô (family = parking → "Phương tiện"), đơn vị per_vehicle.
        $feeType = FeeType::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenantId, 'code' => 'OTO'],
            [
                'name' => 'Phí gửi ô tô',
                'category' => 'parking',
                'unit' => 'per_vehicle',
                'is_recurring' => true,
                'status' => 'active',
                'is_critical' => true,
                'payment_priority' => 10,
            ],
        );

        // Chiếc xe sinh ra phí.
        $vehicle = Vehicle::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenantId, 'plate_no' => $plate],
            [
                'building_id' => $building->id,
                'apartment_id' => $apartment->id,
                'resident_id' => $resident?->id,
                'type' => 'car',
                'brand' => 'Demo Motors',
                'monthly_fee' => self::AMOUNT,
                'status' => 'active',
            ],
        );

        foreach (self::MONTHS as $ym) {
            $start = Carbon::parse($ym.'-01')->startOfMonth();
            $end = $start->copy()->endOfMonth();

            $period = BillingPeriod::withoutGlobalScopes()->firstOrCreate(
                ['tenant_id' => $tenantId, 'building_id' => $building->id, 'code' => $ym],
                [
                    'label' => 'Tháng '.$start->format('n/Y'),
                    'period_month' => $start->toDateString(),
                    'is_current' => $ym === self::MONTHS[count(self::MONTHS) - 1],
                ],
            );

            $statement = Statement::withoutGlobalScopes()->firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'building_id' => $building->id,
                    'apartment_id' => $apartment->id,
                    'billing_period_id' => $period->id,
                ],
                [
                    'total_amount' => self::AMOUNT,
                    'paid_amount' => 0,
                    'status' => 'issued',
                    'approval_status' => Statement::APPROVAL_PUBLISHED,
                    'published_at' => $start->copy()->addDays(2)->setTime(9, 0),
                    'due_date' => $end->toDateString(),
                ],
            );

            // Nếu bảng kê đã tồn tại từ seed khác nhưng chưa phát hành → kéo về published
            // để cây công nợ demo hiện ra.
            if ($statement->approval_status !== Statement::APPROVAL_PUBLISHED || $statement->published_at === null) {
                $statement->forceFill([
                    'approval_status' => Statement::APPROVAL_PUBLISHED,
                    'published_at' => $start->copy()->addDays(2)->setTime(9, 0),
                ])->saveQuietly();
            }

            StatementLine::withoutGlobalScopes()->firstOrCreate(
                [
                    'statement_id' => $statement->id,
                    'subject_type' => $vehicle->getMorphClass(),
                    'subject_id' => $vehicle->id,
                    'service_period_start' => $start->toDateString(),
                ],
                [
                    'fee_type' => 'Phí gửi ô tô',
                    'fee_type_id' => $feeType->id,
                    'fee_category' => 'parking',
                    'service_period_end' => $end->toDateString(),
                    'due_date' => $end->toDateString(),
                    'quantity' => 1,
                    'unit_price' => self::AMOUNT,
                    'amount' => self::AMOUNT,
                    'paid_amount' => 0,
                ],
            );
        }

        $this->command?->info(sprintf(
            'D6 demo: %s › %s › %s › %d tháng = %s đ (căn #%d)',
            'Phương tiện', 'Phí gửi ô tô', $plate, count(self::MONTHS),
            number_format(self::AMOUNT * count(self::MONTHS)), $apartment->id,
        ));
    }

    /**
     * Trả về [tenantId, Building, Apartment, ?Resident] cho cudan1 — tái dùng nếu có,
     * dựng tối thiểu nếu chưa.
     *
     * @return array{0:int,1:Building,2:Apartment,3:?Resident}
     */
    private function resolveContext(string $email, string $name): array
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => bcrypt('Test@2026!'),
                'account_type' => 'resident',
                'kyc_status' => 'verified',
            ],
        );

        // Đã có căn qua quan hệ cư dân?
        $relation = ResidentApartmentRelation::withoutGlobalScopes()
            ->whereIn('resident_id', Resident::withoutGlobalScopes()->where('user_id', $user->id)->pluck('id'))
            ->whereNotNull('apartment_id')
            ->orderByDesc('is_primary')->orderBy('id')
            ->first();

        if ($relation) {
            $apartment = Apartment::withoutGlobalScopes()->find($relation->apartment_id);
            $building = Building::withoutGlobalScopes()->find($apartment->building_id);
            $resident = Resident::withoutGlobalScopes()->find($relation->resident_id);

            return [$apartment->tenant_id, $building, $apartment, $resident];
        }

        // Chưa có — dựng tối thiểu (ưu tiên dự án demo DAIPHUC-RS nếu có).
        $project = Project::withoutGlobalScopes()->where('code', 'DAIPHUC-RS')->first();
        $tenant = $project
            ? Tenant::withoutGlobalScopes()->find($project->tenant_id)
            : Tenant::firstOrCreate(['code' => 'TEN-D6'], ['name' => 'Demo D6']);
        $project ??= Project::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'D6-PRJ'],
            ['name' => 'Dự án Demo D6'],
        );
        $building = Building::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'project_id' => $project->id, 'code' => 'D6-BLD'],
            ['name' => 'Toà D6'],
        );
        $apartment = Apartment::withoutGlobalScopes()->firstOrCreate(
            ['building_id' => $building->id, 'code' => 'D6-A101'],
            ['tenant_id' => $tenant->id, 'status' => 'occupied', 'area_sqm' => 75, 'type' => '2PN'],
        );
        $resident = Resident::withoutGlobalScopes()->firstOrCreate(
            ['user_id' => $user->id, 'building_id' => $building->id],
            [
                'tenant_id' => $tenant->id,
                'code' => 'CD-D6-'.strtoupper(substr(md5($user->email), 0, 5)),
                'full_name' => $user->name,
                'status' => 'active',
                'link_status' => 'linked',
                'linked_at' => now(),
            ],
        );
        ResidentApartmentRelation::withoutGlobalScopes()->firstOrCreate(
            ['resident_id' => $resident->id, 'apartment_id' => $apartment->id],
            ['tenant_id' => $tenant->id, 'role' => 'owner', 'is_primary' => true],
        );

        return [$tenant->id, $building, $apartment, $resident];
    }
}
