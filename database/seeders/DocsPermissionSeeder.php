<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Quyền cho MODULE TÀI LIỆU (docs CMS).
 * - docs.view.{audience} (6 giá trị): quyền xem không gian theo đối tượng.
 * - docs.manage: quyền soạn thảo (Filament resources).
 *
 * Idempotent — dùng findOrCreate + syncPermissions không xóa quyền khác.
 * Bám 3-tier RBAC hiện có (xem DemoDataSeeder). super_admin vẫn bypass qua
 * Gate::before nên gán ở đây chỉ để rõ ràng/audit.
 */
class DocsPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $guard = 'web';

        // 1) Tạo permissions.
        $viewPerms = [];
        foreach (['dev', 'ops', 'bql', 'hq', 'sa', 'resident'] as $audience) {
            $viewPerms[$audience] = Permission::findOrCreate("docs.view.{$audience}", $guard);
        }
        $manage = Permission::findOrCreate('docs.manage', $guard);

        // 2) Gán theo role. Map audience mỗi role có thể đọc.
        $grants = [
            // Platform (nhà cung cấp) — thấy tất cả + soạn thảo.
            'super_admin' => ['dev', 'ops', 'bql', 'hq', 'sa', 'resident', 'manage'],
            'platform_support' => ['dev', 'ops', 'bql', 'hq', 'sa', 'resident', 'manage'],
            'billing_admin' => ['sa', 'hq'],

            // Tenant / Công ty vận hành (HQ).
            'company_admin' => ['hq', 'ops', 'bql'],
            'hq_finance' => ['hq'],
            'operations_director' => ['hq', 'ops', 'bql'],

            // Project / BQL.
            'building_manager' => ['bql', 'ops', 'resident'],
            'accountant' => ['bql'],
            'cashier' => ['bql'],
            'customer_service' => ['bql', 'resident'],
            'technician' => ['bql', 'ops'],
            'security' => ['bql'],
            'shift_leader' => ['bql'],
            'communication_officer' => ['bql', 'resident'],
        ];

        foreach ($grants as $roleName => $audiences) {
            $role = Role::findOrCreate($roleName, $guard);
            foreach ($audiences as $a) {
                $perm = $a === 'manage' ? $manage : ($viewPerms[$a] ?? null);
                if ($perm && ! $role->hasPermissionTo($perm)) {
                    $role->givePermissionTo($perm);
                }
            }
        }

        $this->command?->info('DocsPermissionSeeder: đã seed 6 quyền docs.view.* + docs.manage và gán cho '.count($grants).' role.');
    }
}
