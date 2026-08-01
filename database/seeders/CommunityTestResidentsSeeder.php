<?php

namespace Database\Seeders;

use App\Models\Apartment;
use App\Models\Building;
use App\Models\Project;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * 2 tài khoản cư dân TEST cùng dự án Đại Phúc Riverside (DAIPHUC-RS) — để thử
 * Cộng đồng giữa 2 máy (iPhone + Samsung): cùng project ⇒ cùng feed cộng đồng.
 *
 * Idempotent (firstOrCreate). Mật khẩu chung: Test@2026!.
 *  - test.cudan1@x2bms.vn
 *  - test.cudan2@x2bms.vn
 */
class CommunityTestResidentsSeeder extends Seeder
{
    private const PASSWORD = 'Test@2026!';

    public function run(): void
    {
        $project = Project::withoutGlobalScopes()->where('code', 'DAIPHUC-RS')->first();
        if ($project === null) {
            $this->command?->warn('Không thấy project DAIPHUC-RS — chạy DemoDataSeeder trước.');

            return;
        }
        $building = Building::withoutGlobalScopes()
            ->where('project_id', $project->id)->orderBy('id')->first();
        if ($building === null) {
            $this->command?->warn('Dự án chưa có toà nhà — bỏ qua.');

            return;
        }
        $tenantId = $project->tenant_id;

        $accounts = [
            ['email' => 'test.cudan1@x2bms.vn', 'name' => 'Cư dân Test 1', 'apt' => 'DP-TEST-1', 'phone' => '0900000101'],
            ['email' => 'test.cudan2@x2bms.vn', 'name' => 'Cư dân Test 2', 'apt' => 'DP-TEST-2', 'phone' => '0900000102'],
        ];

        foreach ($accounts as $a) {
            $user = User::firstOrCreate(
                ['email' => $a['email']],
                [
                    'name' => $a['name'],
                    'password' => bcrypt(self::PASSWORD),
                    'account_type' => 'resident',
                    'kyc_status' => 'verified',
                ],
            );
            $apartment = Apartment::withoutGlobalScopes()->firstOrCreate(
                ['building_id' => $building->id, 'code' => $a['apt']],
                ['tenant_id' => $tenantId, 'status' => 'occupied', 'area_sqm' => 75, 'type' => '2PN - 2WC'],
            );
            $resident = Resident::withoutGlobalScopes()->firstOrCreate(
                ['user_id' => $user->id, 'building_id' => $building->id],
                [
                    'tenant_id' => $tenantId,
                    'code' => 'CD-TEST-'.strtoupper(substr(md5($a['email']), 0, 5)),
                    'full_name' => $a['name'],
                    'phone' => $a['phone'],
                    'status' => 'active',
                    'kyc_status' => 'verified',
                    'link_status' => 'linked',
                    'linked_at' => now(),
                    'source' => 'bql_manual',
                    'requested_role' => 'owner',
                ],
            );
            ResidentApartmentRelation::firstOrCreate(
                ['resident_id' => $resident->id, 'apartment_id' => $apartment->id],
                ['tenant_id' => $tenantId, 'role' => 'owner', 'is_primary' => true],
            );
        }

        $this->command?->info('2 TK test cộng đồng (cùng Đại Phúc): '
            .'test.cudan1@x2bms.vn / test.cudan2@x2bms.vn — mật khẩu '.self::PASSWORD);
    }
}
