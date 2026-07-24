<?php

namespace Database\Seeders;

use App\Models\Resident;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Tạo NHANH vài tài khoản cư dân để test app — AN TOÀN chạy trên DB đã có dữ liệu
 * (idempotent, KHÔNG đụng modules/tenants như DemoDataSeeder).
 *
 * Mỗi tài khoản được gắn vào 1 cư dân (resident) chưa có tài khoản, có sẵn căn hộ
 * primary — nên đăng nhập xong sẽ thấy căn hộ/hoá đơn/thông báo của cư dân đó.
 * Mật khẩu chung: Resident@2026!
 *
 * Chạy:  php artisan db:seed --class=ResidentTestAccountsSeeder
 */
class ResidentTestAccountsSeeder extends Seeder
{
    private const PASSWORD = 'Resident@2026!';

    public function run(): void
    {
        $accounts = [
            ['email' => 'nguyenvananh@gmail.com', 'name' => 'Nguyễn Văn Anh', 'phone' => '0900000555', 'cccd' => '079200001555'],
            ['email' => 'cudan2@fino.vn', 'name' => 'Cư dân Demo 2', 'phone' => '0900000002', 'cccd' => '079200000002'],
            ['email' => 'cudan3@fino.vn', 'name' => 'Cư dân Demo 3', 'phone' => '0900000003', 'cccd' => '079200000003'],
            ['email' => 'cudan4@fino.vn', 'name' => 'Cư dân Demo 4', 'phone' => '0900000004', 'cccd' => '079200000004'],
        ];

        foreach ($accounts as $a) {
            $user = User::withoutGlobalScopes()->updateOrCreate(
                ['email' => $a['email']],
                [
                    'tenant_id' => null,           // tài khoản global
                    'account_type' => 'resident',
                    'name' => $a['name'],
                    'phone' => $a['phone'],
                    'password' => Hash::make(self::PASSWORD),
                    'email_verified_at' => now(),
                    'id_no' => $a['cccd'],
                    'kyc_status' => 'verified',
                    'is_platform_admin' => false,
                ]
            );

            // Đã liên kết cư dân nào chưa? → giữ nguyên (idempotent).
            $linked = Resident::withoutGlobalScopes()->where('user_id', $user->id)->first();
            if ($linked === null) {
                $linked = $this->linkToAvailableResident($user);
            }

            $apt = $linked
                ? DB::table('resident_apartment_relations as rar')
                    ->join('apartments as ap', 'ap.id', '=', 'rar.apartment_id')
                    ->where('rar.resident_id', $linked->id)
                    ->orderByDesc('rar.is_primary')
                    ->value('ap.code')
                : null;

            $this->command?->info(sprintf(
                '  %s / %s → resident #%s (%s) · căn %s',
                $a['email'], self::PASSWORD,
                $linked->id ?? '—', $linked->full_name ?? 'chưa gắn được (hết cư dân trống)', $apt ?? '—'
            ));
        }
    }

    /** Gắn user vào 1 cư dân chưa có tài khoản, có căn hộ primary. */
    private function linkToAvailableResident(User $user): ?Resident
    {
        $residentId = DB::table('residents as r')
            ->join('resident_apartment_relations as rar', 'rar.resident_id', '=', 'r.id')
            ->whereNull('r.user_id')
            ->where('rar.is_primary', true)
            ->orderBy('r.id')
            ->value('r.id');

        if ($residentId === null) {
            return null;
        }

        Resident::withoutGlobalScopes()->where('id', $residentId)->update([
            'user_id' => $user->id,
            'link_status' => 'linked',
            'linked_at' => Carbon::now(),
        ]);

        return Resident::withoutGlobalScopes()->find($residentId);
    }
}
