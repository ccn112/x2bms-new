<?php

namespace Database\Seeders;

use App\Enums\FeedbackStatus;
use App\Enums\WorkOrderStatus;
use App\Models\AiSuggestion;
use App\Models\Apartment;
use App\Models\AuditLog;
use App\Models\BillingPeriod;
use App\Models\Building;
use App\Models\Debt;
use App\Models\Department;
use App\Models\FeedbackCategory;
use App\Models\FeedbackRequest;
use App\Models\IocAlert;
use App\Models\Project;
use App\Models\SlaEvent;
use App\Models\Statement;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds the demo tenant exactly as approved in UI handoff WEB-01-01
 * (Bảng điều khiển vận hành). Every headline number on the screen is
 * produced by these rows — the view computes, it never hardcodes.
 *
 * Headline targets reproduced:
 *   - Tỷ lệ thu phí        = 96.2%   (collected 2.45 tỷ / billed 2.546 tỷ, current period)
 *   - Đã thu trong tháng   = 2.45 tỷ
 *   - Phản ánh chờ xử lý   = 56      (feedback_requests in pending states)
 *   - Cảnh báo SLA         = 18      (open sla_events)
 *   - Phản ánh phân loại   = 132     (total feedback_requests, donut)
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::create([
            'code' => 'T-X2-DEMO',
            'name' => 'X2-BMS Demo Tenant',
            'short_name' => 'X2-BMS',
            'tax_code' => '0312345678',
            'phone' => '1900 1234',
            'email' => 'contact@x2bms.vn',
            'website' => 'https://x2bms.vn',
            'address' => 'Tầng 12, Tòa nhà Sunshine, Q.1',
            'city' => 'TP. Hồ Chí Minh',
            'legal_representative' => 'Nguyễn Minh Anh',
            'contact_person' => 'Phòng Vận hành',
            'contact_phone' => '028 3822 1234',
            'plan' => 'enterprise',
            'status' => 'active',
            'primary_color' => '#0b1b3f',
            'secondary_color' => '#c8a24c',
            'app_config' => ['locale' => 'vi', 'currency' => 'VND', 'modules' => ['finance', 'feedback', 'operations']],
        ]);

        $company = \App\Models\Company::create([
            'tenant_id' => $tenant->id,
            'code' => 'CO-SSG',
            'name' => 'Công ty CP Quản lý Vận hành Sunshine',
            'short_name' => 'Sunshine OM',
            'tax_code' => '0312345678-001',
            'phone' => '028 3822 1234',
            'email' => 'om@sunshine.vn',
            'address' => 'Q.1, TP. HCM',
            'legal_representative' => 'Trần Văn Bình',
            'status' => 'active',
        ]);

        $project = Project::create([
            'tenant_id' => $tenant->id,
            'company_id' => $company->id,
            'code' => 'SUNSHINE-GARDEN',
            'name' => 'Sunshine Garden',
            'type' => 'urban_area',
            'status' => 'active',
            'address' => 'Đường Phú Thuận, P. An Phú',
            'ward' => 'An Phú',
            'district' => 'TP. Thủ Đức',
            'city' => 'TP. Hồ Chí Minh',
            'latitude' => 10.7870000,
            'longitude' => 106.7510000,
            'land_area_sqm' => 35000,
            'building_count' => 2,
            'apartment_count' => 160,
            'investor' => 'Sunshine Group',
            'legal_no' => 'GP-2021/SG-001',
            'handover_date' => Carbon::parse('2022-06-20'),
            'contact_person' => 'Nguyễn Minh Anh',
            'contact_phone' => '0901 234 567',
            'description' => 'Khu căn hộ cao cấp ven sông, gồm 2 tòa A/B.',
        ]);

        $block = \App\Models\Block::create([
            'tenant_id' => $tenant->id,
            'project_id' => $project->id,
            'code' => 'S1',
            'name' => 'Phân khu S1',
        ]);

        $building = Building::create([
            'tenant_id' => $tenant->id,
            'project_id' => $project->id,
            'block_id' => $block->id,
            'code' => 'SG-A',
            'name' => 'Sunshine Garden - Tòa A',
            'type' => 'residential',
            'status' => 'active',
            'address' => 'Lô A, Sunshine Garden',
            'apartment_count' => 120,
            'floor_count' => 20,
            'basement_count' => 2,
            'elevator_count' => 4,
            'handover_date' => Carbon::parse('2022-06-20'),
        ]);

        $scope = ['tenant_id' => $tenant->id, 'building_id' => $building->id];

        // --- Admin user (login for Filament + dashboard) ---
        $admin = User::create([
            'tenant_id' => $tenant->id,
            'project_id' => $project->id,
            'building_id' => $building->id,
            'name' => 'Nguyễn Minh Anh',
            'title' => 'Trưởng BQL',
            'is_platform_admin' => true,
            'email' => 'x2bms@x2bms.vn',
            'password' => Hash::make('Bms@2026!'),
            'email_verified_at' => now(),
        ]);

        // RBAC roles — 3-tier model (Platform → Tenant/Công ty vận hành → Project/BQL).
        // super_admin gets all abilities via Gate::before; the rest are created so
        // Shield can attach permissions and so scope grants can reference them.
        $rolesByTier = [
            'platform' => ['super_admin', 'platform_support', 'billing_admin'],
            'tenant' => ['company_admin', 'hq_finance', 'operations_director'],
            'project' => ['building_manager', 'accountant', 'cashier', 'customer_service', 'technician', 'security', 'shift_leader', 'communication_officer'],
        ];
        $roles = [];
        foreach ($rolesByTier as $tierRoles) {
            foreach ($tierRoles as $role) {
                $roles[$role] = \Spatie\Permission\Models\Role::findOrCreate($role, 'web');
            }
        }
        $admin->assignRole($roles['super_admin']);

        // X2AI access permissions (WEB-UX-09 governance — mode is permission-driven,
        // not a user toggle). super_admin bypasses via Gate::before; others are granted
        // explicitly here. ai.use = use copilot; ai.data_lookup = Mode 2 DB lookup.
        $aiUse = \Spatie\Permission\Models\Permission::findOrCreate('ai.use', 'web');
        $aiDataLookup = \Spatie\Permission\Models\Permission::findOrCreate('ai.data_lookup', 'web');
        foreach ($roles as $role) {
            $role->givePermissionTo($aiUse);
        }
        foreach (['company_admin', 'hq_finance', 'operations_director', 'building_manager', 'accountant', 'customer_service'] as $r) {
            $roles[$r]->givePermissionTo($aiDataLookup);
        }

        // Demo login is a platform operator (sees every project). The scope row makes
        // the 3-tier model explicit/auditable rather than relying only on the flag.
        \App\Models\UserRoleScope::create([
            'user_id' => $admin->id,
            'role_id' => $roles['super_admin']->id,
            'scope_type' => \App\Models\UserRoleScope::SCOPE_PLATFORM,
        ]);

        // --- Departments ---
        $departments = collect([
            ['code' => 'KT', 'name' => 'Kỹ thuật'],
            ['code' => 'AN', 'name' => 'An ninh'],
            ['code' => 'VS', 'name' => 'Vệ sinh'],
            ['code' => 'CS', 'name' => 'CSKH'],
            ['code' => 'TC', 'name' => 'Tài chính'],
        ])->map(fn ($d) => Department::create($scope + $d));

        // --- Floors (20) + common areas ---
        $floors = [];
        for ($level = 1; $level <= 20; $level++) {
            $floors[$level] = \App\Models\Floor::create($scope + [
                'code' => sprintf('F%02d', $level),
                'name' => "Tầng {$level}",
                'level' => $level,
            ]);
        }
        foreach ([
            ['BAI-XE', 'Bãi xe tầng hầm', 'parking'],
            ['SANH', 'Sảnh chính', 'common'],
            ['GYM', 'Phòng Gym', 'amenity'],
            ['KY-THUAT', 'Phòng kỹ thuật', 'technical'],
        ] as [$code, $name, $type]) {
            \App\Models\Area::create($scope + ['code' => $code, 'name' => $name, 'type' => $type]);
        }

        // --- Apartments (120) ---
        $apartments = [];
        for ($level = 1; $level <= 20; $level++) {
            for ($unit = 1; $unit <= 6; $unit++) {
                $apartments[] = Apartment::create($scope + [
                    'floor_id' => $floors[$level]->id,
                    'code' => sprintf('A-%02d%02d', $level, $unit),
                    'status' => 'occupied',
                    'area_sqm' => 65 + ($unit * 5),
                    'type' => ['1PN - 1WC', '2PN - 2WC', '3PN - 2WC'][$unit % 3],
                    'ownership_type' => 'Sở hữu lâu dài',
                    'handover_date' => Carbon::parse('2022-06-20'),
                    'management_fee' => 16500,
                ]);
            }
        }

        // --- Residents + resident↔apartment relations ---
        $firstNames = ['An', 'Bình', 'Cường', 'Dung', 'Giang', 'Hà', 'Hùng', 'Lan', 'Minh', 'Nam', 'Phúc', 'Quân', 'Thảo', 'Vân'];
        $residents = [];
        foreach ($apartments as $i => $apt) {
            $name = 'Nguyễn Văn '.$firstNames[$i % count($firstNames)];
            $resident = \App\Models\Resident::create($scope + [
                'code' => sprintf('CD-%04d', $i + 1),
                'full_name' => $name,
                'phone' => '09'.str_pad((string) (10000000 + $i), 8, '0', STR_PAD_LEFT),
                'email' => 'cudan'.($i + 1).'@x2bms.vn',
                'status' => 'active',
                'dob' => Carbon::parse('1985-01-01')->addDays($i * 37),
                'gender' => $i % 2 ? 'Nam' : 'Nữ',
                'id_no' => sprintf('0790%08d', 9000000 + $i),
                'id_issued_date' => Carbon::parse('2018-01-01')->addDays($i),
                'id_issued_place' => 'Cục CSQL HC về TTXH',
                'nationality' => 'Việt Nam',
                'marital_status' => $i % 3 === 0 ? 'Độc thân' : 'Đã kết hôn',
                'contact_address' => $apt->code.' - Tòa A, Sunshine Garden, P. An Phú, TP. Thủ Đức, TP. HCM',
                'mailing_address' => $apt->code.' - Tòa A, Sunshine Garden, P. An Phú, TP. Thủ Đức, TP. HCM',
                'join_date' => Carbon::parse('2022-06-15')->addDays($i % 60),
            ]);
            $residents[] = $resident;
            // Vary household role: ~70% owner, ~22% tenant, ~8% member.
            $role = $i % 5 === 0 ? 'tenant' : ($i % 12 === 0 ? 'member' : 'owner');
            \App\Models\ResidentApartmentRelation::create([
                'tenant_id' => $tenant->id,
                'resident_id' => $resident->id,
                'apartment_id' => $apt->id,
                'role' => $role,
                'is_primary' => true,
                'start_date' => Carbon::parse('2025-01-01'),
            ]);
        }

        // --- Emergency contacts (RES-DETAIL) for the first batch of residents ---
        $relMeta = [['Vợ', 'Trần Thị Lan', '0909876543'], ['Chồng', 'Phạm Văn Hòa', '0912765432'], ['Con', 'Nguyễn Thu Trang', '0987112233']];
        foreach (array_slice($residents, 0, 60) as $i => $res) {
            [$rel, $cName, $cPhone] = $relMeta[$i % 3];
            \App\Models\ResidentEmergencyContact::create([
                'tenant_id' => $tenant->id,
                'resident_id' => $res->id,
                'full_name' => $cName,
                'relationship' => $rel,
                'phone' => $cPhone,
                'email' => 'lienhe'.($i + 1).'@gmail.com',
            ]);
        }

        // --- Vehicles (WEB-02-02/03): ~70% apartments have a vehicle ---
        $vehicleTypes = ['car', 'motorbike', 'motorbike', 'bicycle'];
        foreach ($apartments as $i => $apt) {
            if ($i % 10 === 7) {
                continue; // some apartments have no vehicle
            }
            $type = $vehicleTypes[$i % count($vehicleTypes)];
            $isCar = $type === 'car';
            \App\Models\Vehicle::create($scope + [
                'apartment_id' => $apt->id,
                'resident_id' => $residents[$i]->id,
                'plate_no' => $isCar ? sprintf('30A-%03d.%02d', $i % 1000, $i % 100) : sprintf('29-%02dX%d.%04d', $i % 100, $i % 9, $i % 10000),
                'type' => $type,
                'brand' => $isCar ? 'Toyota' : ($type === 'motorbike' ? 'Honda' : 'Giant'),
                'parking_card_no' => $type === 'bicycle' ? null : sprintf('PK-%05d', $i + 1),
                'monthly_fee' => $isCar ? 1_200_000 : ($type === 'motorbike' ? 120_000 : 0),
                'status' => 'active',
                'valid_to' => Carbon::parse('2026-12-31'),
            ]);
        }

        // --- Access cards (WEB-02-03): one per resident, some biometric ---
        foreach ($residents as $i => $resident) {
            $bio = $i % 6 === 0;
            \App\Models\AccessCard::create($scope + [
                'resident_id' => $resident->id,
                'apartment_id' => $apartments[$i]->id,
                'card_no' => sprintf('RFID-%06d', 100000 + $i),
                'type' => $bio ? 'biometric' : 'rfid',
                'is_biometric' => $bio,
                'valid_from' => Carbon::parse('2025-01-01'),
                'valid_to' => Carbon::parse('2026-12-31'),
                'status' => $i % 25 === 0 ? 'revoked' : 'active',
            ]);
        }

        // --- Resident approval queue (WEB-02-04): pending applicants ---
        $applicants = [
            ['Trần Thị Hồng', 'owner', 92, 4],
            ['Lê Văn Tài', 'tenant', 78, 3],
            ['Phạm Thu Hà', 'owner', 65, 2],
            ['Vũ Minh Khôi', 'member', 88, 3],
            ['Đỗ Thị Mai', 'tenant', 54, 1],
            ['Hoàng Văn Sơn', 'owner', 95, 5],
            ['Bùi Thị Lan', 'member', 71, 2],
            ['Ngô Quang Huy', 'tenant', 83, 4],
        ];
        foreach ($applicants as $i => [$fullName, $reqRole, $score, $docs]) {
            \App\Models\ResidentApprovalRequest::create($scope + [
                'apartment_id' => $apartments[($i * 7) % count($apartments)]->id,
                'full_name' => $fullName,
                'phone' => '09'.str_pad((string) (20000000 + $i), 8, '0', STR_PAD_LEFT),
                'email' => 'applicant'.($i + 1).'@x2bms.vn',
                'requested_role' => $reqRole,
                'match_score' => $score,
                'document_count' => $docs,
                'status' => 'pending',
                'submitted_at' => now()->subDays($i + 1),
                'note' => null,
            ]);
        }

        // --- Billing periods (Jan–Jul 2026), bar chart "Tình hình thu phí" ---
        // collected (tỷ VND) trend; current = Jul with rate 96.2%.
        $trend = [
            ['2026-01', 'T1/2026', 2_150_000_000, 2_100_000_000, false],
            ['2026-02', 'T2/2026', 2_220_000_000, 2_180_000_000, false],
            ['2026-03', 'T3/2026', 2_300_000_000, 2_250_000_000, false],
            ['2026-04', 'T4/2026', 2_360_000_000, 2_310_000_000, false],
            ['2026-05', 'T5/2026', 2_430_000_000, 2_380_000_000, false],
            ['2026-06', 'T6/2026', 2_500_000_000, 2_420_000_000, false],
            ['2026-07', 'T7/2026', 2_546_000_000, 2_450_000_000, true],  // 2.45/2.546 = 96.23%
        ];
        $currentPeriod = null;
        foreach ($trend as [$code, $label, $billed, $collected, $isCurrent]) {
            $period = BillingPeriod::create($scope + [
                'code' => $code,
                'label' => $label,
                'period_month' => Carbon::parse($code.'-01'),
                'billed_amount' => $billed,
                'collected_amount' => $collected,
                'is_current' => $isCurrent,
            ]);
            if ($isCurrent) {
                $currentPeriod = $period;
            }
        }

        // --- Statements for current period (realism; a dozen apartments) ---
        foreach (array_slice($apartments, 0, 12) as $i => $apt) {
            $total = 21_000_000 + ($i * 100_000);
            $paid = $i < 9 ? $total : (int) ($total * 0.4); // last 3 partial
            Statement::create($scope + [
                'billing_period_id' => $currentPeriod->id,
                'apartment_id' => $apt->id,
                'total_amount' => $total,
                'paid_amount' => $paid,
                'status' => $paid >= $total ? 'paid' : 'partial',
            ]);
        }

        // --- Overdue debts → KPI "Công nợ đến hạn" (sum = 96,000,000) ---
        foreach (array_slice($apartments, 12, 12) as $i => $apt) {
            Debt::create($scope + [
                'apartment_id' => $apt->id,
                'amount' => 8_000_000,
                'due_date' => Carbon::parse('2026-07-10'),
                'is_overdue' => true,
            ]);
        }

        // --- Feedback categories + 132 requests (donut), 56 pending (KPI) ---
        $categories = collect([
            ['code' => 'KT', 'name' => 'Kỹ thuật', 'color' => '#2563eb', 'count' => 48],
            ['code' => 'VS', 'name' => 'Vệ sinh', 'color' => '#0d9488', 'count' => 32],
            ['code' => 'AN', 'name' => 'An ninh', 'color' => '#f59e0b', 'count' => 22],
            ['code' => 'TI', 'name' => 'Tiện ích', 'color' => '#8b5cf6', 'count' => 18],
            ['code' => 'KH', 'name' => 'Khác', 'color' => '#94a3b8', 'count' => 12],
        ]);

        $pendingBudget = 56; // total pending across all categories
        foreach ($categories as $cat) {
            $category = FeedbackCategory::create([
                'tenant_id' => $tenant->id,
                'code' => $cat['code'],
                'name' => $cat['name'],
                'color' => $cat['color'],
            ]);
            for ($n = 1; $n <= $cat['count']; $n++) {
                if ($pendingBudget > 0) {
                    $status = [FeedbackStatus::New, FeedbackStatus::Assigned, FeedbackStatus::InProgress][$pendingBudget % 3];
                    $pendingBudget--;
                } else {
                    $status = $n % 2 === 0 ? FeedbackStatus::Resolved : FeedbackStatus::Closed;
                }
                FeedbackRequest::create($scope + [
                    'feedback_category_id' => $category->id,
                    'title' => "Phản ánh {$cat['name']} #{$n}",
                    'status' => $status,
                    'priority' => 'normal',
                ]);
            }
        }

        // --- Work orders (department performance + "Việc cần xử lý hôm nay" table) ---
        $featured = [
            ['Sự cố thang máy tòa A', 'KT', WorkOrderStatus::InProgress, 'high'],
            ['Thay bóng đèn hành lang tầng 3', 'KT', WorkOrderStatus::Pending, 'normal'],
            ['Rò rỉ nước tầng hầm B1', 'KT', WorkOrderStatus::InProgress, 'high'],
            ['Kiểm tra hệ thống PCCC định kỳ', 'AN', WorkOrderStatus::Pending, 'high'],
            ['Vệ sinh sảnh chính', 'VS', WorkOrderStatus::Pending, 'normal'],
            ['Bảo trì camera tầng 5', 'AN', WorkOrderStatus::InProgress, 'normal'],
        ];
        $deptByCode = $departments->keyBy('code');
        foreach ($featured as $i => [$title, $deptCode, $status, $priority]) {
            WorkOrder::create($scope + [
                'department_id' => $deptByCode[$deptCode]->id,
                'code' => sprintf('WO-%04d', $i + 1),
                'title' => $title,
                'status' => $status,
                'priority' => $priority,
                'due_at' => now()->addDays($i + 1),
            ]);
        }
        // Bulk to give each department a realistic resolution % (progress bars).
        $deptPlan = [
            'KT' => ['done' => 38, 'open' => 6],   // ~86%
            'AN' => ['done' => 24, 'open' => 4],   // ~86%
            'VS' => ['done' => 30, 'open' => 2],   // ~94%
            'CS' => ['done' => 18, 'open' => 3],   // ~86%
            'TC' => ['done' => 12, 'open' => 1],   // ~92%
        ];
        $seq = 100;
        foreach ($deptPlan as $code => $plan) {
            for ($d = 0; $d < $plan['done']; $d++) {
                WorkOrder::create($scope + [
                    'department_id' => $deptByCode[$code]->id,
                    'code' => 'WO-'.(++$seq),
                    'title' => "Công việc {$code} #{$d}",
                    'status' => WorkOrderStatus::Done,
                    'priority' => 'normal',
                ]);
            }
            for ($o = 0; $o < $plan['open']; $o++) {
                WorkOrder::create($scope + [
                    'department_id' => $deptByCode[$code]->id,
                    'code' => 'WO-'.(++$seq),
                    'title' => "Công việc mở {$code} #{$o}",
                    'status' => WorkOrderStatus::Pending,
                    'priority' => 'normal',
                ]);
            }
        }

        // --- SLA events: 18 open → KPI "Cảnh báo SLA" ---
        for ($i = 1; $i <= 18; $i++) {
            SlaEvent::create($scope + [
                'type' => $i <= 6 ? 'breach' : 'due_soon',
                'status' => 'open',
                'description' => "Phản ánh #{$i} sắp/đã quá hạn SLA",
            ]);
        }

        // --- IOC alerts: "Cảnh báo & cần xử lý" list ---
        $alerts = [
            ['critical', 'Nhiệt độ phòng kỹ thuật vượt ngưỡng', 'device'],
            ['warning', 'Camera tầng 7 mất kết nối', 'camera'],
            ['warning', 'Đồng hồ nước Block B chênh lệch bất thường', 'meter'],
            ['info', 'Máy bơm nước số 2 cần bảo trì', 'device'],
        ];
        foreach ($alerts as [$severity, $title, $source]) {
            IocAlert::create($scope + ['severity' => $severity, 'title' => $title, 'source' => $source, 'status' => 'open']);
        }

        // --- AI suggestions for X2AI panel ---
        $suggestions = [
            ['Ưu tiên xử lý 6 phản ánh kỹ thuật quá hạn SLA', 'Tập trung nhân sự Kỹ thuật trong hôm nay'],
            ['Gửi nhắc thanh toán cho 12 căn công nợ đến hạn', 'Dự kiến thu thêm ~96 triệu'],
            ['Lên lịch bảo trì máy bơm nước số 2', 'Tránh rủi ro mất nước cuối tuần'],
        ];
        foreach ($suggestions as [$title, $detail]) {
            AiSuggestion::create($scope + ['context' => 'operational_dashboard', 'title' => $title, 'detail' => $detail]);
        }

        // --- Audit log (footer shows latest) ---
        AuditLog::create($scope + [
            'user_id' => $admin->id,
            'actor_name' => $admin->name,
            'action' => 'statement.publish',
            'subject_type' => BillingPeriod::class,
            'subject_id' => $currentPeriod->id,
            'description' => 'Phát hành bảng kê phí kỳ T7/2026',
        ]);

        // --- Staff accounts + HR profiles + PROJECT scope (WEB-FORM-03-01/03-04) ---
        $deptByCode = $departments->keyBy('code');

        // Admin's own HR profile.
        \App\Models\StaffProfile::create([
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'employee_code' => 'NV-0001',
            'position' => 'Trưởng BQL',
            'phone' => '0901 234 567',
            'gender' => 'Nữ',
            'hire_date' => Carbon::parse('2022-06-01'),
            'status' => 'active',
        ]);

        // Project-scoped BQL staff (NOT platform admins) — demonstrates tier-3 scope.
        $staffPlan = [
            ['Trần Thị Kế', 'Kế toán trưởng', 'accountant', 'TC'],
            ['Lê Văn Kỹ', 'Kỹ thuật viên', 'technician', 'KT'],
            ['Phạm Văn An', 'Nhân viên an ninh', 'security', 'AN'],
            ['Vũ Thị Hỗ', 'CSKH', 'customer_service', 'CS'],
        ];
        foreach ($staffPlan as $i => [$name, $position, $role, $deptCode]) {
            $staff = User::create([
                'tenant_id' => $tenant->id,
                'project_id' => $project->id,
                'building_id' => $building->id,
                'name' => $name,
                'title' => $position,
                'is_platform_admin' => false,
                'email' => 'nv'.($i + 1).'@x2bms.vn',
                'password' => Hash::make('Bms@2026!'),
                'email_verified_at' => now(),
            ]);
            $staff->assignRole($roles[$role]);
            \App\Models\UserRoleScope::create([
                'user_id' => $staff->id,
                'role_id' => $roles[$role]->id,
                'scope_type' => \App\Models\UserRoleScope::SCOPE_PROJECT,
                'tenant_id' => $tenant->id,
                'project_id' => $project->id,
            ]);
            \App\Models\StaffProfile::create([
                'tenant_id' => $tenant->id,
                'user_id' => $staff->id,
                'department_id' => $deptByCode[$deptCode]->id,
                'employee_code' => sprintf('NV-%04d', $i + 2),
                'position' => $position,
                'phone' => '09'.str_pad((string) (60000000 + $i), 8, '0', STR_PAD_LEFT),
                'gender' => $i % 2 ? 'Nam' : 'Nữ',
                'hire_date' => Carbon::parse('2022-08-01')->addMonths($i),
                'status' => 'active',
            ]);
        }

        // --- Teams within the project (WEB-FORM-03-04) ---
        foreach ([['T-KT', 'Tổ Kỹ thuật', 'KT'], ['T-AN', 'Tổ An ninh', 'AN'], ['T-VS', 'Tổ Vệ sinh', 'VS']] as [$code, $name, $deptCode]) {
            \App\Models\Team::create([
                'tenant_id' => $tenant->id,
                'project_id' => $project->id,
                'department_id' => $deptByCode[$deptCode]->id,
                'code' => $code,
                'name' => $name,
            ]);
        }

        // --- Apartment status histories (WEB-FORM-01-04 "trạng thái căn") ---
        foreach (array_slice($apartments, 0, 8) as $apt) {
            \App\Models\ApartmentStatusHistory::create([
                'tenant_id' => $tenant->id,
                'apartment_id' => $apt->id,
                'from_status' => 'handover',
                'to_status' => 'occupied',
                'changed_by_id' => $admin->id,
                'reason' => 'Bàn giao & cư dân dọn vào',
                'changed_at' => Carbon::parse('2022-07-01'),
            ]);
        }

        $this->seedFeeCatalog($tenant, $project, $building);
        $this->seedBillingAndPayments($tenant, $building, $currentPeriod, $admin);
        $this->seedBql03Receivables($tenant, $building, $project, $currentPeriod, $admin);
        $this->seedBql0302Cycles($tenant, $building);
        $this->seedSecondaryBuilding($tenant, $project, $firstNames);
        $this->seedSecondProject($tenant, $firstNames);
        $this->seedCrossCompanyResident($tenant, $apartments[10]);
        $this->seedApprovalQueueRuns($tenant, $project, $admin);
        $this->seedAiEngine($tenant, $project, $building, $admin);
        $this->seedTier2($tenant, $project, $building, $admin);
        $this->seedTier2Patch($tenant, $project, $building, $admin);
        $this->seedTier3Ops($tenant, $project, $building, $admin);
        $this->seedTier3Finance($tenant, $project, $admin);
        $this->seedTier3Security($tenant, $project, $building, $admin);
        $this->seedTier4Saas($tenant);
        $this->seedTier4AdminOps($tenant, $admin);
        $this->seedTier4AssetsContractors($tenant, $project, $building, $admin);
        $this->seedTier4FormBuilder($tenant, $project, $admin);
        $this->seedTier5Community($tenant, $project, $building);
        $this->seedTier5Ecosystem($tenant, $project, $building);
        $this->seedEntityGapClose($tenant, $admin);
        $this->seedPlatformContent($tenant, $project, $admin);
        $this->seedGlobalAccounts($tenant, $project, $building, $admin);
        $this->seedSharedPartners($tenant, $project);
        $this->seedDocumentTemplates($tenant, $admin);
        $this->seedKbAiGovernance($tenant, $project, $admin);
        $this->seedBatch08Integration($tenant, $admin);
        $this->seedBatch10Support($tenant, $admin);
        $this->seedHq01($roles, $admin);
        $this->seedHq02($admin);
        $this->seedHq05($admin);
        $this->seedHq03($admin);
        $this->seedHq04($roles, $admin);
    }

    /**
     * HQ-03 — Biểu mẫu, tài liệu dùng chung & tri thức AI cho Sunshine Group.
     * Thư viện tài liệu + SOP/checklist + biểu mẫu (dynamic_forms) + gán/kế thừa + KB + nguồn AI.
     */
    private function seedHq03(User $admin): void
    {
        $now = Carbon::parse('2026-07-02');
        $tenant = Tenant::where('code', 'T-SSG-HQ')->first();
        if (! $tenant) {
            return;
        }
        $hqUser = User::where('email', 'hq@sunshinegroup.vn')->first() ?? $admin;
        $projects = Project::where('tenant_id', $tenant->id)->orderBy('id')->take(8)->get();
        $snap = fn (string $key, $value, ?array $dim = null, ?string $period = '2026-07') => \App\Models\MetricSnapshot::create([
            'tenant_id' => $tenant->id, 'metric_key' => $key, 'period' => $period, 'value' => $value, 'dimension' => $dim, 'captured_at' => $now,
        ]);

        // Folder tree (HQ-03-05).
        $folders = [
            ['00', '00. Hệ thống quản trị', 96], ['01', '01. SOP quy trình', 356], ['02', '02. HDSD', 214],
            ['03', '03. Chính sách', 148], ['04', '04. Hợp đồng mẫu', 216], ['05', '05. Phụ lục', 98], ['06', '06. Biểu mẫu đính kèm', 714],
        ];
        $folderModels = [];
        foreach ($folders as $i => [$code, $name, $count]) {
            $folderModels[$code] = \App\Models\DocumentLibrary::create(['tenant_id' => $tenant->id, 'code' => $code, 'name' => $name, 'doc_count' => $count, 'sort' => $i]);
        }
        foreach ([['Quản trị dự án', 124], ['Tài chính - Kế toán', 86], ['Nhân sự - Hành chính', 64], ['Mua sắm - Hợp đồng', 52], ['IT - Hệ thống', 30]] as $j => [$sub, $c]) {
            \App\Models\DocumentLibrary::create(['tenant_id' => $tenant->id, 'parent_id' => $folderModels['01']->id, 'name' => $sub, 'doc_count' => $c, 'sort' => $j]);
        }

        // Documents (representative rows for the table).
        $docs = [
            ['SOP-QTDA-01', 'Quy trình khởi tạo dự án', 'sop', 'v2.3', 'synced', 'Toàn công ty', 512],
            ['SOP-QTDA-02', 'Quy trình lập kế hoạch dự án', 'sop', 'v1.8', 'synced', 'Toàn công ty', 428],
            ['SOP-MUAS-01', 'Quy trình mua sắm & đấu thầu', 'sop', 'v2.1', 'synced', 'Phòng Mua sắm', 380],
            ['CHS-NHANSU-03', 'Chính sách lương & thưởng', 'policy', 'v1.5', 'synced', 'Toàn công ty', 210],
            ['HDSD-ERP-01', 'Hướng dẫn sử dụng ERP', 'guide', 'v3.2', 'synced', 'IT, Kế toán', 640],
            ['HDM-MAU-01', 'Hợp đồng tư vấn thiết kế', 'contract', 'v1.3', 'synced', 'Phòng Pháp chế', 156],
            ['PL-HD-02', 'Phụ lục điều khoản thanh toán', 'appendix', 'v1.1', 'synced', 'Toàn công ty', 88],
            ['BM-QLDA-05', 'Biểu mẫu báo cáo tiến độ', 'form_attachment', 'v2.0', 'synced', 'Quản trị dự án', 42],
            ['SOP-IT-04', 'Quy trình quản lý tài khoản', 'sop', 'v1.6', 'error', 'IT', 120],
            ['CHS-ATTT-01', 'Chính sách an toàn thông tin', 'policy', 'v1.2', 'synced', 'Toàn công ty', 98],
            ['SOP-VH-09', 'Quy trình vận hành tòa nhà', 'sop', 'v2.0', 'pending', 'Toàn công ty', 512],
            ['CHS-PCCC-02', 'Chính sách PCCC', 'policy', 'v1.4', 'synced', 'An toàn', 176],
        ];
        foreach ($docs as $i => [$code, $name, $type, $ver, $sync, $scope, $size]) {
            \App\Models\Document::create([
                'tenant_id' => $tenant->id, 'library_id' => $folderModels[$i % 2 ? '01' : '03']->id ?? null,
                'code' => $code, 'name' => $name, 'type' => $type, 'version' => $ver,
                'effective_from' => $now->copy()->subMonths($i + 1), 'owner_id' => $hqUser->id, 'scope' => $scope,
                'ai_sync_status' => $sync, 'size_kb' => $size, 'summary' => 'Tài liệu '.$name.' áp dụng toàn công ty.',
                'status' => $type === 'policy' && $i % 5 === 0 ? 'pending_approval' : 'active',
            ]);
        }
        $snap('doc_kpi', 1842, ['metric' => 'total_docs']);
        $snap('doc_kpi', 356, ['metric' => 'active_sop']);
        $snap('doc_kpi', 18, ['metric' => 'policy_pending']);
        $snap('doc_kpi', 27, ['metric' => 'expiring']);
        $snap('doc_kpi', 18.4, ['metric' => 'storage_gb']);

        // SOP + checklist.
        foreach ([['SOP-QC-01', 'Quy trình nghiệm thu', 'QA/QC'], ['SOP-BT-02', 'Quy trình bảo trì thiết bị', 'Kỹ thuật'], ['SOP-AN-03', 'Quy trình tuần tra an ninh', 'An ninh'], ['SOP-CS-04', 'Quy trình xử lý phản ánh', 'CSKH']] as $i => [$code, $name, $cat]) {
            \App\Models\SopTemplate::create(['tenant_id' => $tenant->id, 'code' => $code, 'name' => $name, 'category' => $cat, 'version' => 'v'.($i + 1).'.0', 'steps' => ['Bước 1', 'Bước 2', 'Bước 3'], 'status' => 'active', 'owner_id' => $hqUser->id]);
        }
        foreach ([['CL-VS-01', 'Checklist vệ sinh hằng ngày', 'Vệ sinh', 12], ['CL-AN-02', 'Checklist an ninh ca trực', 'An ninh', 9], ['CL-KT-03', 'Checklist kiểm tra kỹ thuật', 'Kỹ thuật', 15]] as [$code, $name, $cat, $n]) {
            $cl = \App\Models\ChecklistTemplate::create(['tenant_id' => $tenant->id, 'code' => $code, 'name' => $name, 'category' => $cat, 'item_count' => $n, 'version' => 'v1.0', 'status' => 'active']);
            for ($k = 1; $k <= $n; $k++) {
                \App\Models\ChecklistItem::create(['checklist_template_id' => $cl->id, 'label' => $name.' - mục '.$k, 'sort' => $k, 'is_required' => $k % 3 !== 0]);
            }
        }

        // Forms (reuse dynamic_forms).
        $forms = [
            ['BM-QA-01', 'Biên bản nghiệm thu công việc', 'QA/QC', 'published', 'v2.3'],
            ['BM-ATLD-02', 'Biên bản kiểm tra ATLĐ', 'An toàn', 'published', 'v2.1'],
            ['BM-KT-15', 'Phiếu yêu cầu vật tư', 'Kỹ thuật', 'published', 'v1.8'],
            ['BM-NV-08', 'Đề nghị thanh toán', 'Tài chính - Kế toán', 'published', 'v1.6'],
            ['BM-DA-01', 'Đề xuất / Kiến nghị', 'Văn phòng', 'draft', 'v1.5'],
            ['BM-NS-03', 'Đánh giá nhân sự thử việc', 'Nhân sự', 'draft', 'v1.2'],
            ['BM-TC-07', 'Phiếu thu nội bộ', 'Tài chính - Kế toán', 'archived', 'v3.0'],
            ['BM-PM-11', 'Kế hoạch tuần', 'Quản lý dự án', 'published', 'v2.0'],
            ['BM-QLDA-04', 'Báo cáo tiến độ dự án', 'Quản lý dự án', 'published', 'v3.1'],
            ['BM-KT-22', 'Biên bản kiểm tra chất lượng', 'QA/QC', 'published', 'v2.0'],
        ];
        $formModels = [];
        foreach ($forms as $i => [$code, $name, $cat, $status, $ver]) {
            $formModels[] = \App\Models\DynamicForm::create([
                'tenant_id' => $tenant->id, 'code' => $code, 'name' => $name, 'description' => $name,
                'category' => $cat, 'status' => $status, 'current_version' => (int) ltrim(explode('.', $ver)[0], 'v'),
                'created_by_id' => $hqUser->id,
            ]);
        }
        $snap('form_kpi', 218, ['metric' => 'total']);
        $snap('form_kpi', 156, ['metric' => 'applied']);
        $snap('form_kpi', 38, ['metric' => 'draft']);
        $snap('form_kpi', 24, ['metric' => 'expired']);

        // Hub dashboard (HQ-03-01).
        $snap('hub_kpi', 218, ['metric' => 'forms_applied']);
        $snap('hub_kpi', 346, ['metric' => 'docs_effective']);
        $snap('hub_kpi', 1256, ['metric' => 'ai_indexed']);
        $snap('hub_kpi', 27, ['metric' => 'pending_updates']);
        foreach ([['Sunshine City S1', 92], ['Sunshine Garden S2', 78], ['Sunshine Riverside S3', 64], ['Sunshine Center S4', 88], ['Sunshine Tower S5', 71], ['Sunshine Sky S6', 55]] as $i => [$p, $rate]) {
            $snap('apply_rate', $rate, ['project' => $p, 'sort' => $i]);
        }
        foreach ([['Đã index', 778, 62], ['Chờ re-index', 226, 18], ['Đang rà soát', 151, 12], ['Lỗi cấu trúc', 101, 8]] as $i => [$label, $count, $pct]) {
            $snap('ai_kb_status', $count, ['label' => $label, 'pct' => $pct, 'sort' => $i]);
        }

        // Template assignments + inheritance.
        foreach ($formModels as $i => $fm) {
            $proj = $projects[$i % $projects->count()];
            \App\Models\TemplateAssignment::create([
                'tenant_id' => $tenant->id, 'assignable_type' => 'form', 'assignable_id' => $fm->id, 'resource_name' => $fm->name,
                'project_id' => $proj->id, 'mode' => $i % 4 === 0 ? 'override' : 'apply', 'status' => 'active',
                'assigned_by' => $hqUser->id, 'assigned_at' => $now->copy()->subDays($i),
            ]);
        }
        foreach ([['form', 'tenant', 'project', 'inherit', 1], ['sop', 'tenant', 'project', 'force', 2], ['document', 'platform', 'tenant', 'inherit', 3], ['kb', 'tenant', 'project', 'override', 4]] as $i => [$rt, $from, $to, $mode, $pri]) {
            \App\Models\ConfigInheritanceRule::create(['tenant_id' => $tenant->id, 'resource_type' => $rt, 'scope_from' => $from, 'scope_to' => $to, 'mode' => $mode, 'priority' => $pri, 'status' => 'active', 'note' => 'Quy tắc kế thừa '.$rt]);
        }

        // Knowledge base (reuse knowledge_categories/articles) for T-SSG.
        foreach ([['Vận hành', 'blue'], ['Tài chính', 'amber'], ['Kỹ thuật', 'teal'], ['An ninh', 'red']] as $ci => [$cname, $color]) {
            $cat = \App\Models\KnowledgeCategory::create(['tenant_id' => $tenant->id, 'name' => $cname, 'slug' => \Illuminate\Support\Str::slug($cname).'-ssg-'.$ci, 'color' => $color, 'description' => 'Kiến thức '.$cname, 'articles_count' => 3]);
            for ($a = 1; $a <= 3; $a++) {
                \App\Models\KnowledgeArticle::create([
                    'tenant_id' => $tenant->id, 'knowledge_category_id' => $cat->id, 'title' => $cname.' — Bài hướng dẫn '.$a,
                    'slug' => \Illuminate\Support\Str::slug($cname.'-huong-dan-'.$a.'-ssg'), 'excerpt' => 'Tóm tắt bài viết '.$cname.' '.$a,
                    'body' => 'Nội dung chi tiết hướng dẫn '.$cname.' số '.$a.'.', 'status' => 'published', 'views' => 100 + $a * 20,
                    'helpful_count' => 10 + $a, 'author_id' => $hqUser->id, 'published_at' => $now->copy()->subDays($a * 3),
                ]);
            }
        }

        // AI knowledge sources (HQ-03-09).
        $sources = [
            ['Tài liệu nội bộ', 'SharePoint / OneDrive', 'file', 128.4, 342156],
            ['Google Drive / Share Drive', 'Google Drive', 'drive', 87.9, 156432],
            ['Uploaded files', 'Upload thủ công', 'file', 64.1, 82730],
            ['Biểu mẫu', 'HQ forms & templates', 'form', 18.7, 21842],
            ['SOP library', 'Quy trình & hướng dẫn', 'sop', 162.3, 311764],
            ['FAQ & Hỏi đáp', 'Kho Q&A doanh nghiệp', 'faq', 51.2, 48901],
        ];
        foreach ($sources as $i => [$name, $provider, $type, $gb, $items]) {
            $src = \App\Models\AiKnowledgeSource::create([
                'tenant_id' => $tenant->id, 'name' => $name, 'provider' => $provider, 'type' => $type,
                'status' => $i === 5 ? 'syncing' : 'synced', 'size_gb' => $gb, 'indexed_items' => $items,
                'auto_sync' => true, 'last_synced_at' => $now->copy()->subMinutes(5 + $i),
            ]);
            \App\Models\AiKnowledgeSyncLog::create(['tenant_id' => $tenant->id, 'source_id' => $src->id, 'event' => 'Đồng bộ tự động', 'items_new' => 100 + $i * 10, 'items_updated' => 50 + $i * 5, 'errors' => 0, 'status' => 'success', 'ran_at' => $now->copy()->subMinutes(5 + $i)]);
        }

        // AI test (HQ-03-10).
        $questions = [
            ['Quy trình khởi tạo dự án gồm những bước nào?', 'Vận hành', 'SOP-QTDA-01'],
            ['Chính sách bảo mật thông tin quy định gì?', 'An ninh', 'CHS-ATTT-01'],
            ['Đề nghị thanh toán cần biểu mẫu nào?', 'Tài chính', 'BM-NV-08'],
            ['Quy trình mua sắm & đấu thầu ra sao?', 'Mua sắm', 'SOP-MUAS-01'],
            ['Checklist an ninh ca trực gồm mục gì?', 'An ninh', 'CL-AN-02'],
        ];
        foreach ($questions as $i => [$q, $cat, $src]) {
            $qm = \App\Models\AiTestQuestion::create(['tenant_id' => $tenant->id, 'question' => $q, 'category' => $cat, 'expected_source' => $src, 'status' => 'active']);
            \App\Models\AiTestRun::create([
                'tenant_id' => $tenant->id, 'question_id' => $qm->id, 'answer' => 'Theo tài liệu '.$src.', quy trình gồm các bước chính...',
                'cited_sources' => [$src], 'has_citation' => $i !== 4, 'score' => $i === 4 ? 62 : 88 + $i, 'ran_at' => $now->copy()->subHours($i + 1),
            ]);
        }
    }

    /**
     * HQ-04 — Phân quyền & hỗ trợ tập trung cho Sunshine Group.
     * IAM (reuse spatie + user_role_scopes) + nhóm quyền + hỗ trợ (reuse support_*).
     *
     * @param  array<string, \Spatie\Permission\Models\Role>  $roles
     */
    private function seedHq04(array $roles, User $admin): void
    {
        $now = Carbon::parse('2026-07-02');
        $tenant = Tenant::where('code', 'T-SSG-HQ')->first();
        if (! $tenant) {
            return;
        }
        $hqUser = User::where('email', 'hq@sunshinegroup.vn')->first() ?? $admin;
        $snap = fn (string $key, $value, ?array $dim = null) => \App\Models\MetricSnapshot::create([
            'tenant_id' => $tenant->id, 'metric_key' => $key, 'period' => '2026-07', 'value' => $value, 'dimension' => $dim, 'captured_at' => $now,
        ]);

        // Permission groups (HQ-04-04).
        $groups = [
            ['PG-DASH', 'Dashboard & Báo cáo', 'Xem tổng quan hệ thống', 'Dashboard', 8, 6],
            ['PG-USER', 'Quản lý người dùng', 'Quản lý người dùng hệ thống', 'Người dùng', 12, 3],
            ['PG-ROLE', 'Quản lý vai trò', 'Quản lý vai trò & quyền', 'Vai trò', 10, 2],
            ['PG-TICKET', 'Ticket hỗ trợ', 'Quản lý ticket & phản ánh', 'Hỗ trợ', 14, 5],
            ['PG-KB', 'Cơ sở tri thức', 'Quản lý bài viết & FAQ', 'Tri thức', 9, 4],
            ['PG-FIN', 'Tài chính & Công nợ', 'Xem/duyệt tài chính', 'Tài chính', 16, 4],
            ['PG-DOC', 'Tài liệu nội bộ', 'Quản lý tài liệu', 'Tài liệu', 11, 6],
            ['PG-SYS', 'Cấu hình hệ thống', 'Thiết lập hệ thống', 'Hệ thống', 7, 2],
        ];
        foreach ($groups as $g) {
            [$code, $name, $desc, $module, $permCount, $roleCount] = $g;
            $pg = \App\Models\PermissionGroup::create(['tenant_id' => $tenant->id, 'code' => $code, 'name' => $name, 'description' => $desc, 'module' => $module, 'permission_count' => $permCount, 'role_count' => $roleCount, 'status' => 'active']);
            foreach (['xem', 'them', 'sua', 'xoa', 'duyet'] as $act) {
                \App\Models\PermissionGroupItem::create(['permission_group_id' => $pg->id, 'permission_key' => strtolower($module).'.'.$act, 'module' => $module, 'action' => $act]);
            }
        }

        // 2FA + login sessions cho HQ operator.
        \App\Models\TwoFactorSetting::create(['user_id' => $hqUser->id, 'enabled' => true, 'method' => 'app', 'verified_at' => $now->copy()->subDays(10)]);
        foreach ([['Chrome / Windows', '113.161.20.5', 'TP. Hồ Chí Minh', true], ['Safari / iPhone', '113.161.20.9', 'TP. Hồ Chí Minh', false], ['Edge / Windows', '42.115.3.14', 'Hà Nội', false]] as $i => [$device, $ip, $loc, $current]) {
            \App\Models\LoginSession::create(['tenant_id' => $tenant->id, 'user_id' => $hqUser->id, 'ip_address' => $ip, 'device' => $device, 'location' => $loc, 'last_active_at' => $now->copy()->subHours($i * 6), 'is_current' => $current]);
        }

        // Support tickets cho T-SSG (reuse Batch 10 support_tickets).
        $slaPolicy = \App\Models\SupportSlaPolicy::first();
        $ticketDefs = [
            ['Không đăng nhập được cổng cư dân', 'Người dùng', 'high', 'new', 'Nguyễn Văn A', 'within_sla'],
            ['Sai số liệu công nợ tháng 06', 'Tài chính', 'critical', 'in_progress', 'Ban Tài chính', 'near_breach'],
            ['Đề nghị thêm quyền duyệt chi', 'Phân quyền', 'medium', 'waiting_customer', 'Trần B', 'within_sla'],
            ['Lỗi hiển thị biểu đồ dashboard', 'Hệ thống', 'low', 'resolved', 'Lê C', 'resolved'],
            ['Yêu cầu khôi phục dữ liệu cư dân', 'Dữ liệu', 'high', 'escalated', 'BQL Sunshine Garden', 'breached'],
            ['Tài khoản bị khóa nhầm', 'Người dùng', 'medium', 'closed', 'Phạm D', 'resolved'],
            ['Chậm đồng bộ tri thức AI', 'AI/KB', 'medium', 'open', 'Vũ E', 'within_sla'],
            ['Xin hướng dẫn cấu hình gói dịch vụ', 'Billing', 'low', 'new', 'Hoàng F', 'within_sla'],
        ];
        foreach ($ticketDefs as $i => [$subject, $module, $priority, $status, $requester, $slaState]) {
            $t = \App\Models\SupportTicket::create([
                'ticket_no' => 'TCK-SSG-'.sprintf('%04d', $i + 1), 'tenant_id' => $tenant->id, 'subject' => $subject,
                'description' => 'Nội dung yêu cầu: '.$subject, 'module' => $module, 'category' => $module, 'priority' => $priority,
                'status' => $status, 'environment' => 'production', 'channel' => 'web', 'sla_policy_id' => $slaPolicy?->id,
                'sla_state' => $slaState, 'sla_due_at' => $now->copy()->addHours(8 - $i), 'owner_id' => $hqUser->id,
                'requester_name' => $requester, 'requester_contact' => 'lienhe@sunshinegroup.vn',
                'csat_score' => in_array($status, ['resolved', 'closed'], true) ? (4 + ($i % 2)) : null,
                'created_at' => $now->copy()->subDays($i),
            ]);
            \App\Models\SupportTicketMessage::create(['support_ticket_id' => $t->id, 'author_id' => $hqUser->id, 'author_name' => $hqUser->name, 'type' => 'customer', 'body' => 'Mô tả chi tiết vấn đề: '.$subject]);
            \App\Models\SupportTicketMessage::create(['support_ticket_id' => $t->id, 'author_id' => $hqUser->id, 'author_name' => 'Hỗ trợ HQ', 'type' => 'internal', 'body' => 'Đã tiếp nhận và đang xử lý.']);
        }

        // Support KB (reuse knowledge_articles đã seed ở HQ-03 cho T-SSG) — dùng chung.

        // Dashboard KPIs (HQ-04-01).
        foreach ([['total_users', 1248], ['roles', 18], ['tickets', 386], ['sla_overdue', 23], ['csat', 4.62]] as [$m, $v]) {
            $snap('hq04_kpi', $v, ['metric' => $m]);
        }
        foreach ([['Đang hoạt động', 1062, 85.3, 'green'], ['Chờ kích hoạt', 96, 7.7, 'amber'], ['Tạm khóa', 60, 4.8, 'red'], ['Ngưng hoạt động', 30, 2.2, 'gray']] as $i => [$label, $count, $pct, $color]) {
            $snap('user_status', $count, ['label' => $label, 'pct' => $pct, 'color' => $color, 'sort' => $i]);
        }
        foreach ([['Mới', 132, 34.2, 'blue'], ['Đang xử lý', 146, 37.8, 'orange'], ['Chờ phản hồi KH', 68, 17.6, 'violet'], ['Chờ xử lý', 24, 6.2, 'amber'], ['Đã giải quyết', 16, 4.2, 'green']] as $i => [$label, $count, $pct, $color]) {
            $snap('ticket_status', $count, ['label' => $label, 'pct' => $pct, 'color' => $color, 'sort' => $i]);
        }
        foreach ([['Cổng cư dân (App/Web)', 210, 54.4], ['Email', 78, 20.2], ['Hotline', 52, 13.5], ['Nhân viên ghi nhận', 46, 11.9]] as $i => [$label, $count, $pct]) {
            $snap('ticket_source', $count, ['label' => $label, 'pct' => $pct, 'sort' => $i]);
        }
        foreach ([['Sunshine Garden', 98], ['Sunshine Riverside', 87], ['Sunshine City', 74], ['Sunshine Palace', 68], ['Sunshine Marina', 59]] as $i => [$label, $count]) {
            $snap('ticket_by_building', $count, ['label' => $label, 'sort' => $i]);
        }
        foreach ([['Quản trị HQ', 2, 11.1], ['Quản lý tòa nhà', 6, 33.3], ['Nhân viên vận hành', 5, 27.8], ['Kỹ thuật viên', 3, 16.7], ['Cư dân', 2, 11.1]] as $i => [$label, $count, $pct]) {
            $snap('role_usage', $count, ['label' => $label, 'pct' => $pct, 'sort' => $i]);
        }
        foreach (['02/06' => 4.38, '03/06' => 4.42, '04/06' => 4.51, '05/06' => 4.57, '06/06' => 4.61, '07/06' => 4.62] as $d => $v) {
            $snap('csat_trend', $v, ['day' => $d]);
        }
        // SLA report (HQ-04-09).
        foreach ([['response_time', 78], ['resolution_time', 522], ['sla_compliance', 88.4], ['breach_rate', 11.6]] as [$m, $v]) {
            $snap('sla_kpi', $v, ['metric' => $m]);
        }
    }

    /**
     * HQ-05 — Báo cáo công nợ, tài chính, thu chi đa dự án cho Sunshine Group.
     * Aggregate qua metric_snapshots (khớp ảnh: aging 1023.38 tỷ, cashflow 28.62 tỷ,
     * top-debtor 1236 hồ sơ, AI risk 68/100) + chiến dịch nhắc nợ + quỹ/thu chi + báo cáo + ai_insights.
     */
    private function seedHq05(User $admin): void
    {
        $now = Carbon::parse('2026-07-02');
        $tenant = Tenant::where('code', 'T-SSG-HQ')->first();
        if (! $tenant) {
            return;
        }
        $hqUser = User::where('email', 'hq@sunshinegroup.vn')->first() ?? $admin;
        $B = 1_000_000_000; // tỷ đồng

        $snap = fn (string $key, $value, ?array $dim = null, ?string $period = '2026-06') => \App\Models\MetricSnapshot::create([
            'tenant_id' => $tenant->id, 'metric_key' => $key, 'period' => $period,
            'value' => $value, 'dimension' => $dim, 'captured_at' => $now,
        ]);

        /* ---- Aging buckets (HQ-05-03) ---- */
        $buckets = [
            ['current', 'Current (≤ 0 ngày)', 292.45, 28.6], ['d30', '1–30 ngày', 236.78, 23.2],
            ['d60', '31–60 ngày', 168.32, 16.5], ['d90', '61–90 ngày', 112.54, 11.0], ['over90', 'Trên 90 ngày', 213.91, 20.7],
        ];
        foreach ($buckets as $i => [$k, $label, $ty, $pct]) {
            $snap('aging_bucket', $ty * $B, ['bucket' => $k, 'label' => $label, 'pct' => $pct, 'sort' => $i]);
        }

        /* ---- Per-project debt/aging (HQ-05-02/03) ---- */
        $projAging = [
            ['Sunshine Garden', 92.10, 74.30, 48.60, 32.10, 67.40, 1268, 30.71, 6.3, 21.4],
            ['Sunshine Riverside', 78.60, 62.70, 38.40, 24.80, 53.90, 1045, 25.26, 2.1, 20.9],
            ['Sunshine City', 61.30, 50.20, 33.70, 22.30, 41.80, 842, 20.48, -1.4, 20.0],
            ['Sunshine Palace', 34.20, 28.10, 18.60, 12.90, 27.40, 512, 11.84, -0.6, 22.6],
            ['Sunshine Center', 26.30, 21.50, 14.10, 8.90, 23.40, 398, 9.20, 0.9, 24.8],
        ];
        foreach ($projAging as $r) {
            [$name, $c, $d30, $d60, $d90, $o90, $units, $share, $trend, $badPct] = $r;
            $total = $c + $d30 + $d60 + $d90 + $o90;
            $snap('project_aging', $total * $B, [
                'project' => $name, 'current' => $c, 'd30' => $d30, 'd60' => $d60, 'd90' => $d90,
                'over90' => $o90, 'total' => $total, 'units' => $units, 'share' => $share, 'trend' => $trend, 'bad_pct' => $badPct,
            ]);
        }

        /* ---- Debt by fee type (HQ-05-06) ---- */
        foreach ([['Phí quản lý', 512.30], ['Phí gửi xe', 210.50], ['Phí dịch vụ', 180.20], ['Phí nước', 68.40], ['Phí khác', 51.98]] as $i => [$fee, $ty]) {
            $snap('debt_by_fee', $ty * $B, ['fee_type' => $fee, 'sort' => $i]);
        }

        /* ---- Collection rate theo kỳ (HQ-05-05) ---- */
        foreach (['2026-02' => 88.2, '2026-03' => 89.5, '2026-04' => 90.1, '2026-05' => 91.3, '2026-06' => 92.0, '2026-07' => 79.3] as $p => $rate) {
            $snap('collection_rate', $rate, ['period' => $p], $p);
        }
        // Tỷ lệ thu theo dự án (kỳ hiện tại).
        foreach ([['Sunshine Garden', 78.6], ['Sunshine Riverside', 74.7], ['Sunshine City', 79.5], ['Sunshine Palace', 77.4], ['Sunshine Center', 75.2]] as $i => [$name, $rate]) {
            $snap('collection_by_project', $rate, ['project' => $name, 'sort' => $i]);
        }

        /* ---- Finance KPI tổng quan (HQ-05-01) ---- */
        $snap('finance_kpi', 1023.38 * $B, ['metric' => 'total_debt']);
        $snap('finance_kpi', 213.91 * $B, ['metric' => 'overdue_90']);
        $snap('finance_kpi', 79.3, ['metric' => 'collection_rate']);
        $snap('finance_kpi', 28.62 * $B, ['metric' => 'revenue_month']);
        $snap('finance_kpi', 22.68 * $B, ['metric' => 'collected_month']);
        $snap('finance_kpi', 8.25 * $B, ['metric' => 'reserve_fund']);

        /* ---- Cashflow theo dự án (HQ-05-07) ---- */
        $cashflow = [
            ['Sunshine Garden', 8.20, 7.95, 4.60, 0.60, 3.00, 2.75, 16.00, 7.60, 8.40, 47.5],
            ['Sunshine Riverside', 7.35, 7.05, 3.60, 0.55, 3.20, 2.90, 14.00, 6.30, 7.70, 45.0],
            ['Sunshine City', 5.60, 5.25, 2.70, 0.40, 2.50, 2.15, 11.00, 4.60, 6.40, 41.8],
            ['Sunshine Center', 3.90, 3.75, 2.20, 0.40, 1.30, 1.15, 8.50, 3.40, 5.10, 40.0],
            ['Sunshine Tower', 2.85, 2.60, 1.70, 0.30, 0.85, 0.60, 6.00, 2.80, 3.20, 46.7],
            ['Sunshine Airport', 0.95, 0.88, 0.55, 0.15, 0.25, 0.18, 2.20, 1.30, 0.90, 59.1],
            ['Sunshine Industrial', 0.77, 0.66, 0.40, 0.12, 0.25, 0.14, 1.80, 1.02, 0.78, 56.7],
        ];
        foreach ($cashflow as $i => $r) {
            [$name, $rev, $act, $opex, $maint, $gross, $net, $budget, $spent, $var, $sdpct] = $r;
            $snap('project_cashflow', $rev * $B, [
                'project' => $name, 'revenue' => $rev, 'actual' => $act, 'opex' => $opex, 'maintenance' => $maint,
                'gross' => $gross, 'netflow' => $net, 'budget' => $budget, 'spent' => $spent, 'variance' => $var, 'sd_pct' => $sdpct, 'sort' => $i,
            ], '2026-07');
        }
        foreach ([['revenue', 28.62], ['expense', 18.74], ['netflow', 9.88], ['budget_used_pct', 46.3], ['receivable', 32.14], ['reserve', 8.25]] as [$m, $v]) {
            $snap('cashflow_kpi', $m === 'budget_used_pct' ? $v : $v * $B, ['metric' => $m], '2026-07');
        }

        // Quỹ + thu chi thực + đề nghị chi.
        $fund = \App\Models\CashFund::create(['tenant_id' => $tenant->id, 'code' => 'QUY-VH', 'name' => 'Quỹ vận hành công ty', 'type' => 'operating', 'balance' => 8_250_000_000]);
        foreach ([['income', 'Thu phí dịch vụ', 2_800_000_000], ['expense', 'Chi lương nhân sự', 1_200_000_000], ['expense', 'Chi bảo trì', 620_000_000], ['income', 'Thu phí gửi xe', 480_000_000]] as $i => [$type, $cat, $amt]) {
            \App\Models\CashTransaction::create(['tenant_id' => $tenant->id, 'cash_fund_id' => $fund->id, 'type' => $type, 'category' => $cat, 'amount' => $amt, 'description' => $cat, 'reference_no' => 'CT-'.($i + 1), 'occurred_at' => $now->copy()->subDays($i * 3), 'created_by' => $hqUser->id]);
        }
        foreach ([['Đề nghị chi phí bảo trì thang máy T6/2026', 'Bảo trì', 320_000_000, 'pending'], ['Mua vật tư kỹ thuật định kỳ Q2/2026', 'Vật tư', 180_000_000, 'pending'], ['Thanh toán dịch vụ vệ sinh T6/2026', 'Dịch vụ', 95_000_000, 'pending']] as $i => [$desc, $cat, $amt, $st]) {
            \App\Models\Expense::create(['tenant_id' => $tenant->id, 'code' => 'EXP-2026-'.($i + 1), 'category' => $cat, 'amount' => $amt, 'status' => $st, 'description' => $desc, 'incurred_at' => $now->copy()->subDays($i * 2), 'vendor' => 'Nhà cung cấp '.($i + 1)]);
        }

        /* ---- Top debtors (HQ-05-04) ---- */
        $snap('debt_kpi', 1236, ['metric' => 'high_debt_records']);
        $snap('debt_kpi', 8_246_500_000, ['metric' => 'top50_debt']);
        $snap('debt_kpi', 268, ['metric' => 'over_90_cases']);
        $snap('debt_kpi', 156, ['metric' => 'in_progress']);
        $topDebtors = [
            ['AR-2025-08621', 'Nguyễn Văn Hùng', 'S3-1205', 'Sunshine Garden', 'Phí quản lý', 14, 48_750_000, 'Lê Hoàng Nam', 'over_90'],
            ['AR-2025-08509', 'Trần Thị Mai', 'G2-0910', 'Green Diamond', 'Phí quản lý', 12, 36_240_000, 'Phạm Quốc Bảo', 'd60_90'],
            ['AR-2025-08843', 'Công ty TNHH An Phát', 'S1-TMDV-03', 'Sunshine Garden', 'Phí dịch vụ', 18, 124_800_000, 'Trần Mạnh Quân', 'over_90'],
            ['AR-2025-08377', 'Lê Thị Thanh Hằng', 'S2-0803', 'Sunshine Garden', 'Phí quản lý', 11, 28_160_000, 'Lê Hoàng Nam', 'd60_90'],
            ['AR-2025-08912', 'Phạm Quốc Tuấn', 'G1-1512A', 'Green Diamond', 'Phí quản lý', 10, 22_100_000, 'Phạm Quốc Bảo', 'd30_60'],
            ['AR-2025-08701', 'Đỗ Minh Anh', 'S3-0711', 'Sunshine Garden', 'Phí gửi xe', 9, 18_450_000, 'Trần Mạnh Quân', 'd30_60'],
            ['AR-2025-08455', 'Nguyễn Thị Lan', 'G2-0606', 'Green Diamond', 'Phí quản lý', 8, 16_800_000, 'Lê Hoàng Nam', 'd30_60'],
            ['AR-2025-08228', 'Công ty CP VinaTech', 'S1-TMDV-07', 'Sunshine Garden', 'Phí dịch vụ', 13, 75_600_000, 'Trần Mạnh Quân', 'over_90'],
            ['AR-2025-08315', 'Hoàng Văn Dũng', 'G1-0302', 'Green Diamond', 'Phí quản lý', 7, 13_300_000, 'Phạm Quốc Bảo', 'd30_60'],
            ['AR-2025-08144', 'Vũ Thị Hương', 'S2-1001', 'Sunshine Garden', 'Phí quản lý', 6, 11_520_000, 'Lê Hoàng Nam', 'd0_30'],
        ];
        foreach ($topDebtors as $i => [$code, $name, $apt, $proj, $fee, $months, $amt, $handler, $bucket]) {
            $snap('top_debtor', $amt, [
                'code' => $code, 'name' => $name, 'apartment' => $apt, 'project' => $proj, 'fee' => $fee,
                'months' => $months, 'handler' => $handler, 'bucket' => $bucket, 'sort' => $i,
            ], null);
        }

        /* ---- Chiến dịch nhắc nợ (HQ-05-08) ---- */
        $snap('reminder_kpi', 12, ['metric' => 'running'], null);
        $snap('reminder_kpi', 128_456, ['metric' => 'sent'], null);
        $snap('reminder_kpi', 8_736, ['metric' => 'responses'], null);
        $snap('reminder_kpi', 12_680_000_000, ['metric' => 'committed'], null);
        $snap('reminder_kpi', 356, ['metric' => 'escalated'], null);
        $campaigns = [
            ['Nhắc nợ định kỳ – 7/6', 'Toàn công ty', 'sms', 'running', 12458, 11982, 6.72, 9.82, 3.45],
            ['Nhắc nợ cuối hạn – 5/6', 'Toàn công ty', 'zalo', 'running', 11236, 10824, 5.91, 7.45, 2.21],
            ['Thu hồi quá hạn 30+ ngày', 'Cư dân', 'sms', 'paused', 4876, 4876, 4.18, 5.36, 1.28],
            ['Thu hồi quá hạn 60+ ngày', 'Cư dân', 'email', 'completed', 2843, 2701, 3.32, 3.24, 0.82],
            ['Nhắc nợ qua App X2', 'Tòa nhà', 'app', 'running', 3126, 3974, 7.81, 2.08, 1.12],
            ['Nhắc nợ chủ động doanh nghiệp', 'Cư dân', 'call', 'running', 1820, 1650, 9.20, 4.10, 1.86],
        ];
        foreach ($campaigns as $i => [$name, $scope, $ch, $st, $target, $sent, $resp, $commitTy, $collectTy]) {
            $c = \App\Models\DebtReminderCampaign::create([
                'tenant_id' => $tenant->id, 'code' => 'CAMP-2026-'.sprintf('%03d', $i + 1), 'name' => $name,
                'scope' => $scope, 'channel' => $ch, 'status' => $st, 'target_count' => $target, 'sent_count' => $sent,
                'response_rate' => $resp, 'committed_amount' => $commitTy * $B, 'collected_amount' => $collectTy * $B,
                'owner_id' => $hqUser->id, 'started_at' => $now->copy()->subDays(25 - $i), 'ended_at' => $st === 'completed' ? $now->copy()->subDays(2) : null,
            ]);
            foreach (['sent' => 'Gửi nhắc nợ', 'responded' => 'Phản hồi từ khách hàng', 'committed' => 'Cam kết thanh toán'] as $ls => $note) {
                \App\Models\DebtReminderLog::create(['tenant_id' => $tenant->id, 'campaign_id' => $c->id, 'debtor_ref' => 'KH-'.($i + 1), 'channel' => $ch, 'status' => $ls, 'amount' => $collectTy * $B / 10, 'note' => $note, 'acted_at' => $now->copy()->subDays(20 - $i)]);
            }
        }

        /* ---- Lịch & xuất báo cáo (HQ-05-09) ---- */
        foreach ([
            ['Báo cáo công nợ tổng hợp', 'debt_summary', 'monthly', 'pdf'],
            ['Báo cáo dòng tiền đa dự án', 'cashflow', 'weekly', 'excel'],
            ['Báo cáo tuổi nợ (Aging)', 'aging', 'monthly', 'both'],
            ['Báo cáo tỷ lệ thu', 'collection', 'monthly', 'pdf'],
        ] as $i => [$name, $type, $freq, $fmt]) {
            \App\Models\ReportSchedule::create([
                'tenant_id' => $tenant->id, 'name' => $name, 'report_type' => $type, 'frequency' => $freq, 'format' => $fmt,
                'recipients' => ['ceo@sunshinegroup.vn', 'cfo@sunshinegroup.vn'], 'status' => 'active',
                'next_run_at' => $now->copy()->addDays($i + 1), 'last_run_at' => $now->copy()->subDays(30 - $i), 'created_by' => $hqUser->id,
            ]);
        }
        foreach ([['debt_summary', 'pdf', 'completed'], ['cashflow', 'excel', 'completed'], ['aging', 'pdf', 'processing'], ['collection', 'excel', 'queued']] as $i => [$type, $fmt, $st]) {
            \App\Models\ReportExportJob::create([
                'tenant_id' => $tenant->id, 'report_type' => $type, 'format' => $fmt, 'status' => $st,
                'file_path' => $st === 'completed' ? 'exports/'.$type.'_2026_07.'.$fmt : null,
                'requested_by' => $hqUser->id, 'completed_at' => $st === 'completed' ? $now->copy()->subHours($i + 1) : null,
            ]);
        }

        /* ---- AI phân tích rủi ro (HQ-05-10) ---- */
        $snap('ai_risk_kpi', 68, ['metric' => 'portfolio_risk'], null);
        $snap('ai_risk_kpi', 28.45 * $B, ['metric' => 'forecast_collection'], null);
        $snap('ai_risk_kpi', 63.2, ['metric' => 'avg_recovery_prob'], null);
        $snap('ai_risk_kpi', 14, ['metric' => 'alerts'], null);
        $snap('ai_risk_kpi', 7, ['metric' => 'high_risk_projects'], null);
        foreach (['2026-06' => [25.34, 31.25], '2026-07' => [28.45, 31.25], '2026-08' => [30.12, 31.25]] as $p => [$actual, $target]) {
            $snap('ai_forecast', $actual * $B, ['period' => $p, 'actual' => $actual, 'target' => $target], $p);
        }
        $risks = [
            ['Sunshine Riverside – Block A', 'Doanh nghiệp', 12.58, 92, 28.4, 75, 'Ưu tiên gặp & làm việc trực tiếp', 'critical'],
            ['Công ty TNHH Phú Thịnh', 'Doanh nghiệp', 8.76, 85, 32.1, 63, 'Đặt lịch thanh toán mới', 'critical'],
            ['Sunshine City Sài Gòn – Tower B', 'Doanh nghiệp', 7.42, 78, 41.7, 42, 'Theo dõi sát – cảnh báo sớm', 'high'],
            ['Công ty CP Xây dựng An Phát', 'Doanh nghiệp', 6.05, 72, 46.3, 35, 'Gửi nhắc nợ & đối chiếu', 'high'],
            ['Sunshine Garden – Shophouse 12', 'Cá nhân', 5.32, 61, 59.2, 26, 'Nhắc tự động', 'medium'],
            ['Công ty TNHH Minh Khang', 'Doanh nghiệp', 4.88, 58, 61.4, 22, 'Theo dõi định kỳ', 'medium'],
            ['Sunshine Golden River – Unit 03', 'Cá nhân', 3.96, 52, 67.9, 18, 'Gửi nhắc nợ', 'medium'],
            ['Công ty CP Thương mại ABC', 'Doanh nghiệp', 3.45, 47, 72.3, 15, 'Theo dõi định kỳ', 'low'],
            ['Sunshine Diamond River – Block C', 'Cá nhân', 3.12, 41, 76.8, 12, 'Duy trì theo dõi', 'low'],
            ['Khách hàng khác', '—', 10.23, 39, 79.3, 10, 'Duy trì theo dõi', 'low'],
        ];
        foreach ($risks as $i => [$name, $group, $debtTy, $aiScore, $prob, $delay, $action, $sev]) {
            \App\Models\AiInsight::create([
                'tenant_id' => $tenant->id, 'category' => 'debt_risk', 'severity' => $sev,
                'title' => $name, 'body' => 'Khách hàng có rủi ro công nợ với điểm AI '.$aiScore.'/100.',
                'score' => $aiScore, 'recommendation' => $action, 'status' => 'new', 'generated_at' => $now,
                'metadata' => ['rank' => $i + 1, 'group' => $group, 'debt_ty' => $debtTy, 'recovery_prob' => $prob, 'delay_days' => $delay, 'handler' => $i % 2 ? 'Trần Mạnh Quân' : 'Lê Hoàng Nam'],
            ]);
        }
    }

    /**
     * HQ-02 — Billing, ví công ty & tương tác Platform cho tenant Sunshine Group.
     * Tái sử dụng Batch 07 (billing_invoices/usage/pass-through) + delta HQ-02 (ví công ty,
     * rate card, plan change, metric snapshot). Số khớp ảnh HQ-02-01/03/10.
     */
    private function seedHq02(User $admin): void
    {
        $now = Carbon::parse('2026-07-02');
        $tenant = Tenant::where('code', 'T-SSG-HQ')->first();
        if (! $tenant) {
            return;
        }
        $hqUser = User::where('email', 'hq@sunshinegroup.vn')->first() ?? $admin;
        $projects = Project::where('tenant_id', $tenant->id)->orderBy('id')->get();
        $plans = \App\Models\Plan::whereIn('code', ['popular', 'full', 'intelligent'])->get()->keyBy('code');

        /* ---- Ví công ty (HQ-02-03) ---- */
        $wallet = \App\Models\Wallet::create([
            'tenant_id' => $tenant->id,
            'balance' => 352_680_000,
            'credit_limit' => 1_000_000_000,
            'currency' => 'VND',
            'auto_topup_enabled' => true,
            'auto_topup_threshold' => 200_000_000,
            'auto_topup_amount' => 300_000_000,
            'payment_method' => 'Vietcombank',
            'payment_account' => '**** **** 8888',
            'status' => 'active',
        ]);

        // Sổ cái ví: 6 lần nạp trong tháng = 745.000.000; + phân bổ dự án + trừ phí.
        $topups = [150_000_000, 120_000_000, 125_000_000, 100_000_000, 150_000_000, 100_000_000]; // 6 lần = 745M
        $balanceSeq = [150_000_000, 275_000_000, 225_000_000, 335_000_000, 490_000_000, 360_000_000, 445_000_000, 470_000_000, 385_000_000, 420_000_000, 300_000_000, 352_680_000];
        foreach ($balanceSeq as $d => $bal) {
            $isTopup = $d % 2 === 0;
            \App\Models\WalletTransaction::create([
                'tenant_id' => $tenant->id,
                'wallet_id' => $wallet->id,
                'type' => $isTopup ? 'top_up' : 'deduct',
                'amount' => $isTopup ? ($topups[intdiv($d, 2)] ?? 100_000_000) : 90_000_000,
                'balance_after' => $bal,
                'reference_no' => 'WTX-'.$now->copy()->subDays(12 - $d)->format('Ymd').'-'.($d + 1),
                'description' => $isTopup ? 'Nạp ví qua Vietcombank' : 'Trừ phí nền tảng & pass-through',
                'status' => 'confirmed',
                'posted_at' => $now->copy()->subDays(12 - $d),
                'created_by' => $hqUser->id,
            ]);
        }
        // Phân bổ ngân sách dự phòng theo dự án (HQ-02-03 phải panel): 210/160/120/80 = 570M.
        $alloc = [[$projects[0] ?? null, 210_000_000], [$projects[1] ?? null, 160_000_000], [$projects[2] ?? null, 120_000_000], [null, 80_000_000]];
        foreach ($alloc as [$proj, $amount]) {
            \App\Models\WalletTransaction::create([
                'tenant_id' => $tenant->id,
                'wallet_id' => $wallet->id,
                'project_id' => $proj?->id,
                'type' => 'allocation',
                'amount' => $amount,
                'reference_no' => 'ALLOC-'.($proj?->code ?? 'RESERVE'),
                'description' => $proj ? ('Ngân sách dự phòng: '.$proj->name) : 'Ngân sách dự phòng HQ',
                'status' => 'confirmed',
                'posted_at' => $now->copy()->subDays(3),
                'created_by' => $hqUser->id,
            ]);
        }
        // 1 yêu cầu nạp ví đang chờ + 1 yêu cầu tăng hạn mức.
        \App\Models\WalletTopupRequest::create([
            'tenant_id' => $tenant->id, 'wallet_id' => $wallet->id, 'request_no' => 'TOPUP-2026-014',
            'kind' => 'top_up', 'amount' => 200_000_000, 'method' => 'Vietcombank', 'status' => 'pending',
            'note' => 'Nạp bổ sung cho kỳ 07/2026', 'requested_by' => $hqUser->id,
        ]);
        \App\Models\WalletTopupRequest::create([
            'tenant_id' => $tenant->id, 'wallet_id' => $wallet->id, 'request_no' => 'CLR-2026-003',
            'kind' => 'credit_limit', 'amount' => 1_500_000_000, 'method' => null, 'status' => 'pending',
            'note' => 'Đề nghị nâng hạn mức tín dụng lên 1,5 tỷ', 'requested_by' => $hqUser->id,
        ]);

        /* ---- Usage / pass-through (HQ-02-01/05/06) ---- */
        $period = \App\Models\UsagePeriod::firstOrCreate(
            ['code' => '2026-07'],
            ['period_start' => $now->copy()->startOfMonth(), 'period_end' => $now->copy()->endOfMonth(), 'status' => 'open'],
        );
        // meter_type => [used, limit, overage_amount]
        $usage = [
            'sms' => [174_000, 300_000, 0],
            'zalo' => [92_000, 120_000, 0],
            'email' => [78_000, 150_000, 0],
            'payment_gateway' => [4_200, 10_000, 0],
            'platform' => [8, 24, 0], // số dự án dùng / hạn mức gói
        ];
        foreach ($usage as $meter => [$used, $limit, $ov]) {
            \App\Models\UsageRecord::create([
                'tenant_id' => $tenant->id,
                'usage_period_id' => $period->id,
                'meter_type' => $meter,
                'usage_value' => $used,
                'included_limit' => $limit,
                'overage_value' => max(0, $used - $limit),
                'overage_amount' => $ov,
                'source' => 'collected',
                'status' => 'calculated',
            ]);
        }
        // Quota alert cho Zalo (76.7% — gần ngưỡng) + SMS (58%).
        \App\Models\QuotaAlert::create([
            'tenant_id' => $tenant->id, 'usage_period_id' => $period->id, 'meter_type' => 'zalo',
            'usage_value' => 92_000, 'included_limit' => 120_000, 'over_percent' => 76.7,
            'estimated_fee' => 0, 'recommendation' => 'Cân nhắc mua thêm gói Zalo ZNS', 'status' => 'open',
        ]);

        /* ---- Rate cards (HQ-02-05) ---- */
        foreach ([
            ['sms', 'SMS Brandname', 850, 10], ['zalo', 'Zalo ZNS', 500, 8],
            ['email', 'Email Marketing', 120, 5], ['payment_gateway', 'Cổng thanh toán', 0, 1.1],
            ['platform', 'Phí nền tảng / dự án', 6_000_000, 0],
        ] as [$ch, $name, $price, $markup]) {
            \App\Models\BillingRateCard::create([
                'tenant_id' => null, 'channel' => $ch, 'meter_code' => $ch, 'name' => $name,
                'unit_price' => $price, 'markup_percent' => $markup, 'currency' => 'VND',
                'effective_from' => Carbon::parse('2026-01-01'), 'is_active' => true,
            ]);
        }

        /* ---- Metric snapshots: cơ cấu chi phí + xu hướng + top dự án + dự báo ---- */
        $components = ['platform_fee' => 80_750_000, 'sms' => 17_400_000, 'zalo' => 14_000_000, 'email' => 7_800_000, 'payment_gateway' => 6_000_000, 'other' => 2_500_000];
        foreach ($components as $key => $val) {
            \App\Models\MetricSnapshot::create([
                'tenant_id' => $tenant->id, 'metric_key' => 'cost_component', 'period' => '2026-07',
                'value' => $val, 'dimension' => ['channel' => $key], 'captured_at' => $now,
            ]);
        }
        $trend = ['2026-02' => 96_800_000, '2026-03' => 102_300_000, '2026-04' => 108_700_000, '2026-05' => 117_500_000, '2026-06' => 118_300_000, '2026-07' => 128_450_000];
        foreach ($trend as $p => $val) {
            \App\Models\MetricSnapshot::create([
                'tenant_id' => $tenant->id, 'metric_key' => 'monthly_cost', 'period' => $p,
                'value' => $val, 'captured_at' => $now,
            ]);
        }
        $topProjects = [[$projects[0] ?? null, 45_230_000], [$projects[1] ?? null, 28_640_000], [$projects[2] ?? null, 19_870_000], [$projects[3] ?? null, 14_710_000]];
        foreach ($topProjects as $rank => [$proj, $val]) {
            \App\Models\MetricSnapshot::create([
                'tenant_id' => $tenant->id, 'project_id' => $proj?->id, 'metric_key' => 'project_cost', 'period' => '2026-07',
                'value' => $val, 'dimension' => ['rank' => $rank + 1, 'project' => $proj?->name], 'captured_at' => $now,
            ]);
        }
        // Dự báo tháng tới (HQ-02-09): +6.3%.
        \App\Models\MetricSnapshot::create([
            'tenant_id' => $tenant->id, 'metric_key' => 'forecast', 'period' => '2026-08',
            'value' => 136_540_000, 'dimension' => ['growth_percent' => 6.3, 'confidence' => 'medium'], 'captured_at' => $now,
        ]);

        /* ---- Hóa đơn platform (HQ-02-07/02) ---- */
        $invMonths = ['2026-02' => 96_800_000, '2026-03' => 102_300_000, '2026-04' => 108_700_000, '2026-05' => 117_500_000, '2026-06' => 118_300_000, '2026-07' => 128_450_000];
        $mi = 0;
        foreach ($invMonths as $p => $total) {
            $isCurrent = $p === '2026-07';
            $issue = Carbon::parse($p.'-01');
            $inv = \App\Models\BillingInvoice::create([
                'invoice_no' => 'PINV-'.str_replace('-', '', $p).'-SSG',
                'tenant_id' => $tenant->id,
                'period' => $p,
                'status' => $isCurrent ? 'partially_paid' : 'paid',
                'issue_date' => $issue,
                'due_date' => $issue->copy()->addDays(15),
                'subtotal' => $total,
                'discount_total' => 0,
                'tax_total' => 0,
                'total_amount' => $total,
                'paid_amount' => $isCurrent ? 80_000_000 : $total,
                'remaining_amount' => $isCurrent ? $total - 80_000_000 : 0,
                'currency' => 'VND',
            ]);
            // Lines: phí nền tảng + pass-through gộp.
            foreach ([['subscription', 'Phí nền tảng (gói dịch vụ)', round($total * 0.63)], ['pass_through', 'Pass-through (SMS/Zalo/Email/Gateway)', round($total * 0.35)], ['usage_overage', 'Vượt hạn mức usage', $total - round($total * 0.63) - round($total * 0.35)]] as [$lt, $desc, $amt]) {
                \App\Models\BillingInvoiceLine::create([
                    'invoice_id' => $inv->id, 'line_type' => $lt, 'description' => $desc,
                    'quantity' => 1, 'unit_price' => $amt, 'amount' => $amt, 'tax_rate' => 0,
                ]);
            }
            if (! $isCurrent) {
                \App\Models\BillingPayment::create([
                    'invoice_id' => $inv->id, 'tenant_id' => $tenant->id, 'payment_method' => 'bank',
                    'amount' => $total, 'paid_at' => $issue->copy()->addDays(10), 'transaction_ref' => 'PAY-'.$inv->invoice_no, 'status' => 'confirmed',
                ]);
            }
            $mi++;
        }
        // Đối soát (HQ-02-08): 1 khớp + 1 chênh lệch.
        $latestInv = \App\Models\BillingInvoice::where('tenant_id', $tenant->id)->latest('id')->first();
        \App\Models\BillingReconciliation::create([
            'tenant_id' => $tenant->id, 'invoice_id' => $latestInv?->id, 'bank_transaction_ref' => 'VCB-20260710-001',
            'status' => 'matched', 'difference_amount' => 0, 'confirmed_by' => $hqUser->id, 'confirmed_at' => $now,
        ]);
        \App\Models\BillingReconciliation::create([
            'tenant_id' => $tenant->id, 'invoice_id' => $latestInv?->id, 'bank_transaction_ref' => 'VCB-20260710-002',
            'status' => 'mismatch', 'difference_amount' => 1_250_000,
        ]);
        \App\Models\BillingAdjustment::create([
            'case_id' => 'ADJ-SSG-2026-001', 'tenant_id' => $tenant->id, 'invoice_id' => $latestInv?->id,
            'adjustment_type' => 'overcharge_sms', 'amount' => 1_250_000, 'reason' => 'Tính trùng phí SMS kỳ 06/2026', 'status' => 'pending_approval',
            'requested_by' => $hqUser->id,
        ]);

        /* ---- Plan change requests (HQ-02-10): 128 = 18 processing / 27 pending / 78 completed / 5 rejected ---- */
        $statusPlan = array_merge(
            array_fill(0, 18, 'processing'),
            array_fill(0, 27, 'pending_approval'),
            array_fill(0, 78, 'completed'),
            array_fill(0, 5, 'rejected'),
        );
        $types = ['upgrade', 'downgrade', 'renew'];
        $planCodes = ['popular', 'full', 'intelligent'];
        foreach ($statusPlan as $k => $status) {
            $type = $types[$k % 3];
            $proj = $projects[$k % $projects->count()];
            $from = $plans[$planCodes[$k % 3]];
            $to = $type === 'renew' ? $from : $plans[$planCodes[($k + 1) % 3]];
            $content = match ($type) {
                'upgrade' => 'Nâng cấp từ '.$from->name.' lên '.$to->name,
                'downgrade' => 'Hạ từ '.$from->name.' xuống '.$to->name,
                default => 'Gia hạn thêm '.(6 + ($k % 2) * 6).' tháng',
            };
            \App\Models\PlanChangeRequest::create([
                'tenant_id' => $tenant->id,
                'request_no' => sprintf('REQ-2026-%05d', 128 - $k),
                'project_id' => $proj->id,
                'change_type' => $type,
                'from_plan_id' => $from->id,
                'to_plan_id' => $to->id,
                'content' => $content,
                'effective_date' => $now->copy()->addDays(($k % 20) + 5),
                'estimated_delta' => ($type === 'downgrade' ? -1 : 1) * (2_000_000 + ($k % 5) * 1_000_000),
                'status' => $status,
                'requested_by' => $hqUser->id,
                'approved_by' => in_array($status, ['completed', 'rejected'], true) ? $hqUser->id : null,
                'approved_at' => in_array($status, ['completed', 'rejected'], true) ? $now->copy()->subDays($k % 25) : null,
                'created_at' => $now->copy()->subDays($k % 30),
            ]);
        }
    }

    /**
     * HQ-01 — Cổng Công ty: một công ty vận hành đa dự án (Sunshine Group) với 24 dự án,
     * BQL/nhân sự, gói dịch vụ theo dự án, module override, import. Khớp ảnh HQ-01-01
     * (Tổng 24 · Đang hoạt động 18 · Trial 3 · Tạm ngừng 3 · gia hạn sắp tới 6 · BQL thiếu 4;
     * Tòa nhà 32 · Căn hộ 12.540 · Diện tích 238.500 m²).
     *
     * @param  array<string, \Spatie\Permission\Models\Role>  $roles
     */
    private function seedHq01(array $roles, User $admin): void
    {
        $now = Carbon::parse('2026-07-02');

        $tenant = Tenant::create([
            'code' => 'T-SSG-HQ',
            'name' => 'Công ty CP Quản lý Bất động sản Sunshine Group',
            'short_name' => 'Sunshine Group',
            'tax_code' => '0316666888',
            'phone' => '1900 6888',
            'email' => 'hq@sunshinegroup.vn',
            'website' => 'https://sunshinegroup.vn',
            'address' => 'Tòa nhà Sunshine Center, Q.1',
            'city' => 'TP. Hồ Chí Minh',
            'legal_representative' => 'Nguyễn Minh Anh',
            'contact_person' => 'Khối Vận hành',
            'contact_phone' => '028 3999 6888',
            'plan' => 'enterprise',
            'status' => 'active',
            'primary_color' => '#0b1b3f',
            'secondary_color' => '#c8a24c',
            'app_config' => ['locale' => 'vi', 'currency' => 'VND'],
        ]);

        $company = \App\Models\Company::create([
            'tenant_id' => $tenant->id,
            'code' => 'CO-SSG-HQ',
            'name' => 'Công ty CP Quản lý Bất động sản Sunshine Group',
            'short_name' => 'Sunshine Group',
            'tax_code' => '0316666888',
            'phone' => '028 3999 6888',
            'email' => 'hq@sunshinegroup.vn',
            'address' => 'Q.1, TP. HCM',
            'legal_representative' => 'Nguyễn Minh Anh',
            'status' => 'active',
        ]);

        // HQ operator (tenant-level) — dùng để test cổng /hq không cần platform admin.
        $hqUser = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Nguyễn Minh Anh',
            'title' => 'Quản trị HQ',
            'account_type' => 'staff',
            'is_platform_admin' => false,
            'email' => 'hq@sunshinegroup.vn',
            'password' => Hash::make('Bms@2026!'),
            'email_verified_at' => now(),
        ]);
        $hqUser->assignRole($roles['company_admin']);
        \App\Models\UserRoleScope::create([
            'user_id' => $hqUser->id,
            'role_id' => $roles['company_admin']->id,
            'scope_type' => \App\Models\UserRoleScope::SCOPE_TENANT,
            'tenant_id' => $tenant->id,
        ]);

        // --- Departments (công ty) — khớp donut HQ-01-05 ---
        $deptDefs = [
            ['BGD', 'Ban giám đốc'], ['KT', 'Kỹ thuật'], ['KE', 'Kế toán'],
            ['CS', 'CSKH'], ['BV', 'Bảo vệ'],
        ];
        $deptByCode = [];
        foreach ($deptDefs as [$code, $name]) {
            $deptByCode[$code] = Department::create(['tenant_id' => $tenant->id, 'code' => $code, 'name' => $name]);
        }

        $plans = \App\Models\Plan::whereIn('code', ['popular', 'full', 'intelligent'])->get()->keyBy('code');

        // --- 24 dự án (8 dòng đầu khớp đúng ảnh HQ-01-01) ---
        // [code, name, typeLabel, managerName, planCode, status, renewSoon, deptCode]
        $projectDefs = [
            ['SG-001', 'Sunshine Garden', 'Chung cư cao cấp', 'Trần Quốc Hùng', 'full', 'active', false, 'BGD'],
            ['RP-002', 'River Park Residence', 'Chung cư', 'Lê Thu Hương', 'popular', 'active', false, 'BGD'],
            ['CP-003', 'Central Plaza', 'Tổ hợp TM & VP', 'Phạm Minh Tuấn', 'full', 'active', false, 'BGD'],
            ['GH-004', 'Green Heights', 'Chung cư', 'Đỗ Thị Mai', 'intelligent', 'active', false, 'BGD'],
            ['LB-005', 'Lakeside Building', 'Văn phòng', 'Nguyễn Văn Hòa', 'popular', 'active', true, 'BGD'],
            ['MT-006', 'Moonlight Tower', 'Chung cư cao cấp', 'Vũ Hoàng Nam', 'intelligent', 'trial', false, 'BGD'],
            ['OS-007', 'Ocean Suites', 'Chung cư', 'Lê Thị Kim Anh', 'popular', 'suspended', false, 'BGD'],
            ['SK-008', 'Skyline Tower', 'Tổ hợp TM & VP', 'Hoàng Anh Dũng', 'full', 'active', false, 'BGD'],
            ['GV-009', 'Garden Villa', 'Biệt thự', 'Trần Văn Lộc', 'full', 'active', true, 'BGD'],
            ['SP-010', 'Sunrise Park', 'Chung cư', 'Ngô Thị Bích', 'popular', 'active', false, 'BGD'],
            ['DP-011', 'Diamond Plaza', 'Tổ hợp TM & VP', 'Bùi Quang Huy', 'full', 'active', true, 'BGD'],
            ['GT-012', 'Golden Tower', 'Chung cư cao cấp', 'Đặng Thị Lan', 'intelligent', 'active', false, 'BGD'],
            ['RV-013', 'River View', 'Chung cư', 'Phan Văn Đức', 'popular', 'active', false, 'BGD'],
            ['CH-014', 'City Heights', 'Chung cư', 'Trịnh Thị Hoa', 'popular', 'active', true, 'BGD'],
            ['MP-015', 'Metro Plaza', 'Tổ hợp TM & VP', 'Hồ Minh Khôi', 'full', 'active', false, 'BGD'],
            ['PL-016', 'Pearl Land', 'Chung cư cao cấp', 'Lý Thị Ngọc', 'intelligent', 'active', false, 'BGD'],
            ['ST-017', 'Star Tower', 'Văn phòng', 'Cao Văn Sơn', 'full', 'active', true, 'BGD'],
            ['EC-018', 'Eco City', 'Khu đô thị', 'Dương Thị Thu', 'popular', 'active', false, 'BGD'],
            ['SC-019', 'Sunshine City', 'Chung cư cao cấp', 'Vương Minh Đức', 'popular', 'active', true, 'BGD'],
            ['GC-020', 'Grand Center', 'Tổ hợp TM & VP', 'Tạ Thị Yến', 'full', 'active', false, 'BGD'],
            ['LM-021', 'Luxury Marina', 'Chung cư cao cấp', 'Đinh Văn Toàn', 'intelligent', 'trial', false, 'BGD'],
            ['HP-022', 'Harbor Point', 'Chung cư', 'Mai Thị Hạnh', 'intelligent', 'trial', false, 'BGD'],
            ['WT-023', 'West Tower', 'Văn phòng', 'Chu Văn Bình', 'popular', 'suspended', false, 'BGD'],
            ['NP-024', 'North Park', 'Chung cư', 'Lâm Thị Diệu', 'popular', 'suspended', false, 'BGD'],
        ];

        $count = count($projectDefs);
        // Aggregate targets: buildings 32, apartments 12.540, land 238.500.
        $baseApt = intdiv(12540, $count);      // 522
        $baseLand = intdiv(238500, $count);    // 9937
        $managers = [];

        foreach ($projectDefs as $i => [$code, $name, $typeLabel, $managerName, $planCode, $status, $renewSoon, $deptCode]) {
            // Manager staff (user + staff_profile). Trưởng BQL: 18 thuộc Ban giám đốc, 6 senior thuộc Kỹ thuật.
            $mgrDept = $i < 18 ? 'BGD' : 'KT';
            $mUser = User::create([
                'tenant_id' => $tenant->id,
                'name' => $managerName,
                'title' => 'Trưởng BQL',
                'account_type' => 'staff',
                'is_platform_admin' => false,
                'email' => 'bql'.sprintf('%02d', $i + 1).'@sunshinegroup.vn',
                'password' => Hash::make('Bms@2026!'),
                'email_verified_at' => now(),
            ]);
            $manager = \App\Models\StaffProfile::create([
                'tenant_id' => $tenant->id,
                'user_id' => $mUser->id,
                'department_id' => $deptByCode[$mgrDept]->id,
                'employee_code' => sprintf('NS-%03d', $i + 1),
                'position' => 'Trưởng BQL',
                'phone' => '09'.str_pad((string) (11000000 + $i), 8, '0', STR_PAD_LEFT),
                'gender' => $i % 2 ? 'Nam' : 'Nữ',
                'hire_date' => Carbon::parse('2023-01-01')->addMonths($i),
                'status' => 'active',
            ]);
            $managers[] = $manager;

            $buildingCount = $i < 8 ? 2 : 1;   // 8×2 + 16×1 = 32
            $apt = $baseApt + ($i === 0 ? 12540 - $baseApt * $count : 0);
            $land = $baseLand + ($i === 0 ? 238500 - $baseLand * $count : 0);

            $project = Project::create([
                'tenant_id' => $tenant->id,
                'company_id' => $company->id,
                'code' => $code,
                'name' => $name,
                'type' => $typeLabel,
                'status' => $status,
                'address' => 'TP. Hồ Chí Minh',
                'city' => 'TP. Hồ Chí Minh',
                'land_area_sqm' => $land,
                'building_count' => $buildingCount,
                'apartment_count' => $apt,
                'investor' => 'Sunshine Group',
                'handover_date' => Carbon::parse('2022-01-01')->addMonths($i * 2),
                'contact_person' => $managerName,
                'contact_phone' => '1900 6888',
                'description' => $name.' — dự án do Sunshine Group vận hành.',
            ]);

            // BQL team — 4 dự án đầu tiên (index 0..3) đánh dấu thiếu nhân sự.
            $understaffed = $i < 4;
            \App\Models\BqlTeam::create([
                'tenant_id' => $tenant->id,
                'project_id' => $project->id,
                'code' => 'BQL-'.$code,
                'name' => 'BQL '.$name,
                'manager_employee_id' => $manager->id,
                'hotline' => '1900 6888',
                'email' => strtolower(str_replace(['-', ' '], '', $code)).'@sunshinegroup.vn',
                'address' => 'Văn phòng BQL '.$name,
                'status' => $status === 'suspended' ? 'inactive' : 'active',
                'metadata' => ['understaffed' => $understaffed, 'required_headcount' => 6, 'current_headcount' => $understaffed ? 3 : 6],
            ]);

            // Subscription period theo dự án. Base date để chu kỳ +1 năm KHÔNG rơi vào
            // cửa sổ 30 ngày (2026-07) — chỉ dự án renewSoon mới "sắp gia hạn".
            $started = Carbon::parse('2025-09-01')->addDays($i * 5);
            $periodEnd = $renewSoon ? $now->copy()->addDays(10 + ($i % 15))  // gia hạn trong 30 ngày
                : $started->copy()->addYear();
            \App\Models\ProjectSubscriptionPeriod::create([
                'tenant_id' => $tenant->id,
                'project_id' => $project->id,
                'plan_id' => $plans[$planCode]->id,
                'status' => match ($status) {
                    'trial' => 'trial',
                    'suspended' => 'suspended',
                    default => 'active',
                },
                'started_at' => $started,
                'trial_ends_at' => $status === 'trial' ? $started->copy()->addDays(30) : null,
                'current_period_start' => $started,
                'current_period_end' => $periodEnd,
                'billing_anchor_day' => 1,
                'auto_renew' => $status !== 'suspended',
                'price_snapshot_json' => ['plan' => $planCode, 'monthly' => (int) ($plans[$planCode]->monthly_base_price ?? 0)],
                'approved_by_platform_at' => $status === 'trial' ? null : $started,
            ]);

            // Assign manager to project (primary).
            \App\Models\EmployeeProjectAssignment::create([
                'tenant_id' => $tenant->id,
                'project_id' => $project->id,
                'employee_id' => $manager->id,
                'department_id' => $deptByCode[$mgrDept]->id,
                'role_id' => $roles['building_manager']->id,
                'assignment_type' => 'primary',
                'workload_percent' => 100,
                'priority' => 'high',
                'effective_from' => $started,
                'status' => $status === 'suspended' ? 'expired' : 'active',
                'assigned_by' => $hqUser->id,
                'approved_by' => $hqUser->id,
                'approved_at' => $started,
            ]);
        }

        // --- Nhân sự công ty (HQ-01-05): tổng 128 (24 Trưởng BQL ở trên + 104 dưới đây).
        // Phân bổ phòng ban khớp donut: Kỹ thuật 58, Kế toán 12, CSKH 22, Bảo vệ 18, Ban giám đốc 18.
        // 16 người cuối = "Chờ phân công" (pending, chưa gán dự án) ⇒ Đang làm việc 112 / Chờ 16.
        $fillPlan = array_merge(
            array_fill(0, 52, 'KT'),  // KT: 6 (trưởng BQL) + 52 = 58
            array_fill(0, 12, 'KE'),
            array_fill(0, 22, 'CS'),
            array_fill(0, 18, 'BV'),
        );
        $ho = ['Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Huỳnh', 'Phan', 'Vũ', 'Võ', 'Đặng', 'Bùi', 'Đỗ', 'Hồ', 'Ngô', 'Dương', 'Lý'];
        $dem = ['Văn', 'Thị', 'Minh', 'Quang', 'Ngọc', 'Thanh', 'Hữu', 'Đức', 'Thu', 'Hải', 'Anh', 'Gia'];
        $ten = ['An', 'Bình', 'Cường', 'Dũng', 'Hà', 'Hạnh', 'Khoa', 'Lan', 'Mai', 'Nam', 'Oanh', 'Phúc', 'Quân', 'Sơn', 'Tâm', 'Uyên', 'Vân', 'Yến', 'Bảo', 'Chi'];
        $posByDept = [
            'KT' => ['Kỹ sư dự án', 'Kỹ thuật viên', 'Kỹ sư điện', 'Kỹ sư M&E'],
            'KE' => ['Kế toán viên', 'Kế toán tổng hợp', 'Kế toán trưởng'],
            'CS' => ['Nhân viên CSKH', 'Chuyên viên CSKH'],
            'BV' => ['Nhân viên bảo vệ', 'Giám sát an ninh'],
        ];
        $fill = [];
        foreach ($fillPlan as $j => $deptCode) {
            $pending = $j >= 88; // 16 người cuối chờ phân công
            $u = User::create([
                'tenant_id' => $tenant->id,
                'name' => $ho[$j % 16].' '.$dem[$j % 12].' '.$ten[$j % 20],
                'title' => $posByDept[$deptCode][$j % count($posByDept[$deptCode])],
                'account_type' => 'staff',
                'is_platform_admin' => false,
                'email' => 'ns'.sprintf('%03d', 25 + $j).'@sunshinegroup.vn',
                'password' => Hash::make('Bms@2026!'),
                'email_verified_at' => now(),
            ]);
            $fill[] = \App\Models\StaffProfile::create([
                'tenant_id' => $tenant->id,
                'user_id' => $u->id,
                'department_id' => $deptByCode[$deptCode]->id,
                'employee_code' => sprintf('NS-%03d', 25 + $j),
                'position' => $posByDept[$deptCode][$j % count($posByDept[$deptCode])],
                'phone' => '09'.str_pad((string) (30000000 + $j), 8, '0', STR_PAD_LEFT),
                'gender' => $j % 2 ? 'Nam' : 'Nữ',
                'hire_date' => Carbon::parse('2022-01-01')->addDays($j * 8),
                'status' => $pending ? 'pending' : 'active',
            ]);
        }

        // Phân công: 88 người đầu (active) gán dự án; 36 trong số đó thêm dự án thứ 2 (Đa dự án 36).
        $activeProjects = Project::where('tenant_id', $tenant->id)->where('status', 'active')->orderBy('id')->get();
        foreach (array_slice($fill, 0, 88) as $k => $sp) {
            $primary = $activeProjects[$k % $activeProjects->count()];
            \App\Models\EmployeeProjectAssignment::create([
                'tenant_id' => $tenant->id,
                'project_id' => $primary->id,
                'employee_id' => $sp->id,
                'department_id' => $sp->department_id,
                'role_id' => $roles['technician']->id,
                'assignment_type' => 'primary',
                'workload_percent' => 100,
                'priority' => 'normal',
                'effective_from' => Carbon::parse('2025-03-01'),
                'status' => 'active',
                'assigned_by' => $hqUser->id,
                'approved_by' => $hqUser->id,
                'approved_at' => Carbon::parse('2025-03-01'),
            ]);
            // 36 người đầu tiên nhận thêm 1 dự án phụ (secondary) ⇒ đa dự án.
            if ($k < 36) {
                $secondary = $activeProjects[($k + 5) % $activeProjects->count()];
                \App\Models\EmployeeProjectAssignment::create([
                    'tenant_id' => $tenant->id,
                    'project_id' => $secondary->id,
                    'employee_id' => $sp->id,
                    'department_id' => $sp->department_id,
                    'role_id' => $roles['technician']->id,
                    'assignment_type' => 'secondary',
                    'workload_percent' => 50,
                    'priority' => 'normal',
                    'effective_from' => Carbon::parse('2025-06-01'),
                    'status' => 'active',
                    'assigned_by' => $hqUser->id,
                    'approved_by' => $hqUser->id,
                    'approved_at' => Carbon::parse('2025-06-01'),
                ]);
            }
        }

        // --- Lịch sử luân chuyển (HQ-01-07) ---
        for ($h = 0; $h < 6; $h++) {
            $emp = $fill[$h];
            \App\Models\EmployeeAssignmentHistory::create([
                'tenant_id' => $tenant->id,
                'employee_id' => $emp->id,
                'from_project_id' => $activeProjects[$h]->id,
                'to_project_id' => $activeProjects[($h + 1) % $activeProjects->count()]->id,
                'new_department_id' => $emp->department_id,
                'transfer_code' => sprintf('LC-2026-%03d', $h + 1),
                'reason' => 'Điều chuyển theo nhu cầu vận hành dự án',
                'effective_at' => $now->copy()->subDays(($h + 1) * 7),
                'status' => $h < 4 ? 'effective' : ($h === 4 ? 'pending_approval' : 'approved'),
                'requested_by' => $hqUser->id,
                'approved_by' => $h === 4 ? null : $hqUser->id,
                'approved_at' => $h === 4 ? null : $now->copy()->subDays(($h + 1) * 7 + 1),
            ]);
        }

        // --- Module overrides (HQ-01-09) ---
        $moduleKeys = ['x2ai', 'contractor_library', 'report_library', 'rag', 'supplier_library', 'kb_inheritance', 'public_project', 'prompt_guardrail'];
        foreach (array_slice($activeProjects->all(), 0, 8) as $mi => $p) {
            \App\Models\ProjectModuleOverride::create([
                'tenant_id' => $tenant->id,
                'project_id' => $p->id,
                'module_key' => $moduleKeys[$mi],
                'source' => $mi % 2 ? 'addon' : 'manual_override',
                'status' => $mi % 4 === 0 ? 'pending' : ($mi % 4 === 3 ? 'locked' : 'enabled'),
                'requested_by' => $hqUser->id,
                'approved_by' => $mi % 4 === 0 ? null : $hqUser->id,
                'approved_at' => $mi % 4 === 0 ? null : $now->copy()->subDays($mi * 3),
                'effective_from' => $now->copy()->subDays($mi * 3),
                'metadata' => ['note' => 'Điều chỉnh module theo yêu cầu dự án'],
            ]);
        }

        // --- Import batches (HQ-01-10) ---
        $batch = \App\Models\ImportBatch::create([
            'tenant_id' => $tenant->id,
            'import_type' => 'projects_employees',
            'file_name' => 'import_du_an_nhan_su_2026.xlsx',
            'storage_path' => 'imports/ssg/import_du_an_nhan_su_2026.xlsx',
            'status' => 'validated',
            'total_rows' => 30,
            'valid_rows' => 27,
            'error_rows' => 3,
            'created_by' => $hqUser->id,
            'metadata' => ['sheet' => 'DuAn'],
        ]);
        for ($r = 1; $r <= 8; $r++) {
            \App\Models\ImportBatchRow::create([
                'tenant_id' => $tenant->id,
                'import_batch_id' => $batch->id,
                'row_number' => $r,
                'row_type' => $r <= 5 ? 'project' : 'employee',
                'external_code' => sprintf('IMP-%03d', $r),
                'raw_payload' => ['ten' => 'Dòng '.$r, 'ma' => sprintf('IMP-%03d', $r)],
                'normalized_payload' => ['name' => 'Dòng '.$r],
                'validation_status' => $r <= 6 ? 'valid' : ($r === 7 ? 'warning' : 'error'),
                'validation_errors' => $r === 8 ? ['ma_du_an' => 'Trùng mã dự án'] : null,
            ]);
        }
    }

    /** Addendum / P6 — KB governance + AI guardrail + retrieval log + mở rộng prompt. */
    private function seedKbAiGovernance(Tenant $tenant, Project $project, User $admin): void
    {
        $docs = [
            ['KBD-001', 'Nội quy chung cư nền tảng', 'resident_rule', 'platform', 'public'],
            ['KBD-002', 'SOP vận hành tòa nhà', 'sop', 'platform', 'internal'],
            ['KBD-003', 'Chính sách bảo mật dữ liệu', 'policy', 'platform', 'confidential'],
            ['KBD-004', 'Hướng dẫn PCCC', 'guide', 'tenant', 'internal'],
        ];
        foreach ($docs as $i => [$code, $title, $type, $scope, $sens]) {
            $doc = \App\Models\KnowledgeDocument::create([
                'code' => $code, 'title' => $title, 'description' => $title, 'document_type' => $type,
                'owner_scope' => $scope, 'owner_id' => $scope === 'tenant' ? $tenant->id : null,
                'content_markdown' => "# {$title}\n\nNội dung tài liệu.", 'status' => 'active',
                'ai_index_status' => $i < 3 ? 'indexed' : 'queued', 'ai_indexed_at' => $i < 3 ? Carbon::parse('2026-06-20') : null,
                'sensitivity' => $sens, 'effective_from' => Carbon::parse('2026-01-01'),
            ]);
            \App\Models\KnowledgeScope::create([
                'knowledge_document_id' => $doc->id, 'scope_type' => $scope,
                'scope_id' => $scope === 'tenant' ? $tenant->id : null,
                'permission' => $sens === 'public' ? 'ai_read' : ($sens === 'confidential' ? 'read' : 'ai_read'), 'status' => 'active',
            ]);
        }

        $guards = [
            ['GR-PII', 'Ẩn dữ liệu cá nhân', 'privacy', 'high', 'block'],
            ['GR-FIN', 'Chặn tiết lộ số liệu tài chính nhạy cảm', 'finance', 'high', 'require_human_approval'],
            ['GR-HALLU', 'Chống bịa đặt', 'hallucination', 'medium', 'warn'],
            ['GR-ESC', 'Leo thang khi rủi ro cao', 'escalation', 'critical', 'require_human_approval'],
        ];
        foreach ($guards as [$code, $name, $type, $sev, $action]) {
            \App\Models\AiGuardrailPolicy::create([
                'code' => $code, 'name' => $name, 'description' => $name, 'policy_type' => $type,
                'rule_json' => ['match' => $type], 'severity' => $sev, 'action' => $action, 'is_active' => true,
            ]);
        }

        // Retrieval logs demo.
        $docIds = \App\Models\KnowledgeDocument::pluck('id')->all();
        for ($i = 0; $i < 4; $i++) {
            \App\Models\AiRetrievalLog::create([
                'user_id' => $admin->id, 'tenant_id' => $tenant->id, 'project_id' => $project->id,
                'question' => ['Nội quy giữ xe?', 'Quy trình PCCC?', 'Chính sách phí?', 'Giờ mở hồ bơi?'][$i],
                'answer_summary' => 'Trả lời dựa trên KB nền tảng.',
                'retrieved_document_ids_json' => array_slice($docIds, 0, 2),
                'blocked_document_ids_json' => $i === 2 ? array_slice($docIds, 2, 1) : [],
                'permission_snapshot_json' => ['role' => 'super_admin'], 'model' => 'claude-haiku-4-5',
                'token_input' => 800 + $i * 100, 'token_output' => 300 + $i * 50, 'latency_ms' => 1200 + $i * 200,
            ]);
        }

        // Mở rộng vài prompt template theo addendum (use_case/system_prompt).
        foreach (\App\Models\AiPromptTemplate::where('tenant_id', $tenant->id)->take(3)->get() as $i => $pt) {
            $pt->update([
                'code' => 'PT-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'use_case' => ['resident_qa', 'bql_copilot', 'support_agent'][$i] ?? 'resident_qa',
                'system_prompt' => 'Bạn là trợ lý X2AI. Trả lời ngắn gọn, chính xác, tiếng Việt.',
                'user_prompt_template' => 'Người dùng hỏi: {{question}}',
                'variables_json' => ['question'], 'owner_scope' => 'platform',
            ]);
        }
    }

    /** Addendum / P5 — thư viện mẫu tài liệu + share + clone. */
    private function seedDocumentTemplates(Tenant $tenant, User $admin): void
    {
        $cats = [];
        foreach ([['SOP', 'Quy trình SOP'], ['POLICY', 'Chính sách'], ['CONTRACT', 'Hợp đồng mẫu'], ['FORM', 'Biểu mẫu']] as [$code, $name]) {
            $cats[$code] = \App\Models\DocumentTemplateCategory::create(['code' => $code, 'name' => $name]);
        }
        $tplDefs = [
            ['TPL-SOP-01', 'SOP tiếp nhận phản ánh', 'sop', 'SOP'],
            ['TPL-POL-01', 'Chính sách phí quản lý mẫu', 'policy', 'POLICY'],
            ['TPL-CTR-01', 'Hợp đồng dịch vụ vệ sinh mẫu', 'contract', 'CONTRACT'],
            ['TPL-FRM-01', 'Biểu mẫu đăng ký chuyển đồ', 'form', 'FORM'],
        ];
        $templates = [];
        foreach ($tplDefs as $i => [$code, $title, $type, $catCode]) {
            $templates[] = \App\Models\DocumentTemplate::create([
                'code' => $code, 'category_id' => $cats[$catCode]->id, 'title' => $title, 'description' => $title,
                'template_type' => $type, 'owner_scope' => 'platform', 'version' => 1, 'status' => 'active',
                'body_markdown' => "# {$title}\n\nNội dung mẫu áp dụng toàn nền tảng.", 'ai_readable' => true,
                'effective_from' => Carbon::parse('2026-01-01'), 'created_by' => $admin->id, 'approved_by' => $admin->id,
            ]);
        }
        // Chia sẻ mẫu platform → tenant (clone_allowed) + 1 clone.
        \App\Models\DocumentTemplateShare::create([
            'template_id' => $templates[0]->id, 'from_scope' => 'platform', 'to_scope' => 'tenant', 'to_owner_id' => $tenant->id,
            'share_mode' => 'clone_allowed', 'can_ai_read' => true, 'effective_from' => Carbon::parse('2026-06-01'), 'status' => 'active',
        ]);
        $clone = \App\Models\DocumentTemplate::create([
            'code' => 'TPL-SOP-01-T'.$tenant->id, 'category_id' => $cats['SOP']->id, 'title' => 'SOP tiếp nhận phản ánh (bản công ty)',
            'template_type' => 'sop', 'owner_scope' => 'tenant', 'owner_id' => $tenant->id, 'version' => 1, 'status' => 'active',
            'body_markdown' => "# SOP tiếp nhận phản ánh\n\nĐiều chỉnh theo công ty.", 'ai_readable' => true, 'created_by' => $admin->id,
        ]);
        \App\Models\DocumentTemplateClone::create([
            'source_template_id' => $templates[0]->id, 'cloned_template_id' => $clone->id, 'cloned_by' => $admin->id,
            'cloned_at' => Carbon::parse('2026-06-15'), 'clone_reason' => 'Tùy biến cho công ty',
        ]);
    }

    /** Addendum / P4 — thư viện đối tác dùng chung + gán cho tenant. */
    private function seedSharedPartners(Tenant $tenant, Project $project): void
    {
        $cats = [];
        foreach ([
            ['CT-ELV', 'Thang máy', 'contractor'], ['CT-PCCC', 'PCCC', 'contractor'],
            ['CT-MEP', 'Cơ điện MEP', 'contractor'], ['CT-SEC', 'An ninh', 'contractor'],
            ['SP-MEP', 'Vật tư M&E', 'supplier'], ['SP-EQP', 'Thiết bị', 'supplier'],
            ['SV-CLEAN', 'Vệ sinh', 'service_provider'],
        ] as [$code, $name, $type]) {
            $cats[$code] = \App\Models\SharedPartnerCategory::create(['code' => $code, 'name' => $name, 'partner_type' => $type]);
        }
        $partnerDefs = [
            // [code, name, partner_type, category_code, verification_status, rating]
            ['NT-TN', 'Thang máy Thiên Nam', 'contractor', 'CT-ELV', 'preferred', 4.7],
            ['NT-OTIS', 'Thang máy OTIS VN', 'contractor', 'CT-ELV', 'verified', 4.5],
            ['NT-PCCC1', 'PCCC Trường Sơn', 'contractor', 'CT-PCCC', 'verified', 4.2],
            ['NT-PCCC2', 'PCCC An Toàn Việt', 'contractor', 'CT-PCCC', 'unverified', 0.0],
            ['NT-MEP1', 'Cơ điện REE M&E', 'contractor', 'CT-MEP', 'preferred', 4.8],
            ['NT-SEC1', 'An ninh Long Hải', 'contractor', 'CT-SEC', 'verified', 4.1],
            ['NT-SEC2', 'Bảo vệ Thắng Lợi', 'contractor', 'CT-SEC', 'blacklisted', 2.3],
            ['NCC-SG', 'Vật tư Sài Gòn M&E', 'supplier', 'SP-MEP', 'verified', 4.3],
            ['NCC-HP', 'Vật tư Hải Phát', 'supplier', 'SP-MEP', 'preferred', 4.6],
            ['NCC-EQ1', 'Thiết bị Schneider VN', 'supplier', 'SP-EQP', 'verified', 4.4],
            ['NCC-EQ2', 'Thiết bị ABB Việt Nam', 'supplier', 'SP-EQP', 'unverified', 0.0],
            ['DV-SX', 'Vệ sinh Sạch Xanh', 'service_provider', 'SV-CLEAN', 'verified', 4.5],
        ];
        $partners = [];
        foreach ($partnerDefs as $i => [$code, $name, $type, $catKey, $vstatus, $rating]) {
            $p = \App\Models\SharedPartner::create([
                'code' => $code, 'name' => $name, 'partner_type' => $type, 'category_id' => $cats[$catKey]->id,
                'tax_code' => '03123'.$i, 'contact_name' => 'Phòng KD', 'phone' => '028 3822 00'.$i,
                'service_area' => 'TP.HCM', 'verification_status' => $vstatus, 'rating_avg' => $rating, 'kpi_score' => $rating * 20,
                'description' => $name, 'is_active' => true,
            ]);
            \App\Models\SharedPartnerCertification::create([
                'partner_id' => $p->id, 'name' => 'Chứng nhận năng lực', 'certificate_no' => 'CC-'.$code,
                'issued_by' => 'Sở Xây dựng', 'issued_at' => Carbon::parse('2024-01-01'), 'expired_at' => Carbon::parse('2027-01-01'),
            ]);
            if ($type === 'supplier') {
                foreach ([['MEP-001', 'Cáp điện CV', 'm', 25000], ['MEP-002', 'Ống PPR', 'm', 45000]] as [$sku, $pn, $unit, $price]) {
                    \App\Models\SharedPartnerProduct::create(['partner_id' => $p->id, 'sku' => $sku, 'name' => $pn, 'unit' => $unit, 'reference_price' => $price, 'warranty_months' => 12]);
                }
            }
            $partners[] = $p;
        }

        $asgTypes = ['contracted_vendor', 'approved_vendor', 'favorite'];
        foreach ($partners as $i => $p) {
            // Không gán đối tác bị cấm (AC-14).
            if ($p->verification_status === 'blacklisted') {
                continue;
            }
            \App\Models\TenantPartnerAssignment::create([
                'tenant_id' => $tenant->id, 'project_id' => $project->id, 'partner_id' => $p->id,
                'assignment_type' => $asgTypes[$i % count($asgTypes)],
                'contract_no' => $i === 0 ? 'HD-2026-001' : null, 'start_date' => Carbon::parse('2026-01-01'),
                'end_date' => Carbon::parse('2026-12-31'),
            ]);
        }
    }

    /** Addendum / P3 — tài khoản gốc + yêu cầu/liên kết gắn cư dân. */
    private function seedGlobalAccounts(Tenant $tenant, Project $project, Building $building, User $admin): void
    {
        // Vài căn hộ trong dự án để gắn (nhiều tòa nếu có).
        $apts = Apartment::whereIn('building_id', Building::where('project_id', $project->id)->pluck('id'))
            ->orderBy('id')->limit(10)->get()->values();
        $aptAt = fn (int $i) => $apts->get($i % max($apts->count(), 1));

        // Registry đa dạng: verified/chờ xác thực, nhiều account_type, 1 bị khoá,
        // 1 cặp nghi trùng (cùng duplicate_group_id + SĐT gần giống), risk score cao.
        $accounts = [
            // [name, phone, email, identity_status, account_type, account_status, risk, dupGroup]
            ['Nguyễn Văn An', '0901234567', 'nguyenvanan@gmail.com', 'verified', 'resident', 'active', 0, null],
            ['Trần Thị Bình', '0912345678', 'binhtt@gmail.com', 'phone_verified', 'public_user', 'active', 20, null],
            ['Lê Quản Trị', '0987000111', 'admin.le@x2bms.vn', 'verified', 'platform_admin', 'active', 0, null],
            ['Phạm Thị Cúc', '0903000222', 'cucpham@gmail.com', 'verified', 'resident', 'active', 0, null],
            ['Hoàng Văn Dũng', '0903000223', 'dunghoang@gmail.com', 'email_verified', 'public_user', 'active', 65, 'DUP-01'],
            ['Hoàng V. Dũng', '0903000223', 'dung.hoang88@gmail.com', 'unverified', 'public_user', 'active', 70, 'DUP-01'],
            ['Đỗ Thị Em', '0916111333', 'do.em@gmail.com', 'phone_verified', 'public_user', 'active', 15, null],
            ['Vũ Minh Phúc', '0918222444', 'phucvm@gmail.com', 'verified', 'contractor', 'active', 0, null],
            ['Bùi Thị Giang', '0919333555', 'giangbui@gmail.com', 'unverified', 'public_user', 'suspended', 85, null],
            ['Ngô Văn Hải', '0921444666', 'haingo@gmail.com', 'verified', 'resident', 'active', 0, null],
            ['Đặng Thị Hoa', '0922555777', 'hoadang@gmail.com', 'phone_verified', 'public_user', 'active', 10, null],
            ['Lý Văn Khoa', '0923666888', 'khoaly@gmail.com', 'email_verified', 'vendor', 'active', 0, null],
        ];
        $created = [];
        foreach ($accounts as $i => [$name, $phone, $email, $idStatus, $type, $accStatus, $risk, $dup]) {
            $created[] = \App\Models\GlobalUserAccount::create([
                'uuid' => \Illuminate\Support\Str::uuid(), 'phone' => $phone, 'email' => $email, 'full_name' => $name,
                'identity_status' => $idStatus, 'account_status' => $accStatus, 'account_type' => $type,
                'first_registered_at' => Carbon::parse('2026-01-05')->addDays($i * 4),
                'last_login_at' => Carbon::parse('2026-06-30')->subDays($i),
                'risk_score' => $risk, 'duplicate_group_id' => $dup,
            ]);
        }

        // Hàng đợi duyệt gắn căn: phủ đủ 5 trạng thái + 1 account gắn NHIỀU căn (AC-07).
        $ev = ['evidence' => ['so_hong.pdf', 'cccd_front.jpg']];
        $reqApproved = \App\Models\ResidentBindingRequest::create([
            'code' => 'BIND-0001', 'user_account_id' => $created[0]->id, 'tenant_id' => $tenant->id,
            'project_id' => $project->id, 'building_id' => $aptAt(0)?->building_id, 'apartment_id' => $aptAt(0)?->id,
            'requested_role' => 'owner', 'status' => 'approved', 'requested_at' => Carbon::parse('2026-06-20'),
            'reviewed_by' => $admin->id, 'reviewed_at' => Carbon::parse('2026-06-21'), 'review_note' => 'Hợp lệ',
            'evidence_files_json' => $ev,
        ]);
        \App\Models\ResidentUnitBinding::create([
            'user_account_id' => $created[0]->id, 'tenant_id' => $tenant->id, 'project_id' => $project->id,
            'building_id' => $aptAt(0)?->building_id, 'apartment_id' => $aptAt(0)?->id, 'role' => 'owner', 'status' => 'active',
            'starts_at' => Carbon::parse('2026-06-21'), 'approved_request_id' => $reqApproved->id,
        ]);
        // Cùng account (An) sở hữu thêm 1 căn nữa — minh hoạ 1 tài khoản nhiều căn.
        $reqApproved2 = \App\Models\ResidentBindingRequest::create([
            'code' => 'BIND-0002', 'user_account_id' => $created[0]->id, 'tenant_id' => $tenant->id,
            'project_id' => $project->id, 'building_id' => $aptAt(5)?->building_id, 'apartment_id' => $aptAt(5)?->id,
            'requested_role' => 'owner', 'status' => 'approved', 'requested_at' => Carbon::parse('2026-06-25'),
            'reviewed_by' => $admin->id, 'reviewed_at' => Carbon::parse('2026-06-26'), 'evidence_files_json' => $ev,
        ]);
        \App\Models\ResidentUnitBinding::create([
            'user_account_id' => $created[0]->id, 'tenant_id' => $tenant->id, 'project_id' => $project->id,
            'building_id' => $aptAt(5)?->building_id, 'apartment_id' => $aptAt(5)?->id, 'role' => 'owner', 'status' => 'active',
            'starts_at' => Carbon::parse('2026-06-26'), 'approved_request_id' => $reqApproved2->id,
        ]);

        // Các yêu cầu còn treo: pending × nhiều, need_more_info, rejected, cancelled.
        $pending = [
            [$created[1], 1, 'tenant', '2026-07-01'],
            [$created[3], 2, 'owner', '2026-06-29'],
            [$created[6], 3, 'family_member', '2026-06-30'],
            [$created[9], 4, 'owner', '2026-07-01'],
            [$created[10], 6, 'tenant', '2026-06-28'],
        ];
        $n = 3;
        foreach ($pending as [$acc, $ai, $role, $date]) {
            \App\Models\ResidentBindingRequest::create([
                'code' => 'BIND-'.str_pad((string) $n++, 4, '0', STR_PAD_LEFT), 'user_account_id' => $acc->id,
                'tenant_id' => $tenant->id, 'project_id' => $project->id, 'building_id' => $aptAt($ai)?->building_id,
                'apartment_id' => $aptAt($ai)?->id, 'requested_role' => $role, 'status' => 'pending',
                'requested_at' => Carbon::parse($date), 'evidence_files_json' => $ev,
            ]);
        }
        \App\Models\ResidentBindingRequest::create([
            'code' => 'BIND-'.str_pad((string) $n++, 4, '0', STR_PAD_LEFT), 'user_account_id' => $created[4]->id,
            'tenant_id' => $tenant->id, 'project_id' => $project->id, 'building_id' => $aptAt(7)?->building_id,
            'apartment_id' => $aptAt(7)?->id, 'requested_role' => 'owner', 'status' => 'need_more_info',
            'requested_at' => Carbon::parse('2026-06-27'), 'reviewed_by' => $admin->id, 'reviewed_at' => Carbon::parse('2026-06-28'),
            'review_note' => 'Cần bổ sung sổ hồng bản công chứng', 'evidence_files_json' => ['evidence' => ['cccd_front.jpg']],
        ]);
        \App\Models\ResidentBindingRequest::create([
            'code' => 'BIND-'.str_pad((string) $n++, 4, '0', STR_PAD_LEFT), 'user_account_id' => $created[8]->id,
            'tenant_id' => $tenant->id, 'project_id' => $project->id, 'building_id' => $aptAt(8)?->building_id,
            'apartment_id' => $aptAt(8)?->id, 'requested_role' => 'tenant', 'status' => 'rejected',
            'requested_at' => Carbon::parse('2026-06-22'), 'reviewed_by' => $admin->id, 'reviewed_at' => Carbon::parse('2026-06-23'),
            'review_note' => 'Giấy tờ không khớp chủ hộ', 'evidence_files_json' => $ev,
        ]);
        \App\Models\ResidentBindingRequest::create([
            'code' => 'BIND-'.str_pad((string) $n++, 4, '0', STR_PAD_LEFT), 'user_account_id' => $created[7]->id,
            'tenant_id' => $tenant->id, 'project_id' => $project->id, 'building_id' => $aptAt(9)?->building_id,
            'apartment_id' => $aptAt(9)?->id, 'requested_role' => 'guest', 'status' => 'cancelled',
            'requested_at' => Carbon::parse('2026-06-24'),
        ]);
    }

    /** Addendum / P2 — platform content + thư viện dự án public. */
    private function seedPlatformContent(Tenant $tenant, Project $project, User $admin): void
    {
        $cats = [];
        foreach ([['NEWS', 'Tin tức', 'news'], ['BANNER', 'Banner', 'banner'], ['GUIDE', 'Hướng dẫn', 'guide'], ['POLICY', 'Chính sách', 'policy']] as [$code, $name, $type]) {
            $cats[$type] = \App\Models\PlatformContentCategory::create(['code' => $code, 'name' => $name, 'type' => $type]);
        }
        $contents = [
            ['Ra mắt X2-BMS 2.0', 'news', 'published', 'platform'],
            ['Ưu đãi nâng cấp gói Thông minh', 'banner', 'published', 'public'],
            ['Hướng dẫn kích hoạt tài khoản cư dân', 'guide', 'published', 'public'],
            ['Chính sách bảo mật dữ liệu nền tảng', 'policy', 'published', 'platform'],
            ['Bản tin vận hành tháng 7 (nháp)', 'news', 'draft', 'platform'],
        ];
        foreach ($contents as $i => [$title, $type, $status, $scope]) {
            \App\Models\PlatformContent::create([
                'category_id' => $cats[$type]->id ?? null, 'title' => $title, 'slug' => \Illuminate\Support\Str::slug($title),
                'summary' => $title, 'body' => '<p>'.$title.'</p>', 'content_type' => $type === 'banner' ? 'banner' : 'news',
                'publish_scope' => $scope, 'status' => $status, 'created_by' => $admin->id,
                'approved_by' => $status === 'published' ? $admin->id : null,
                'published_at' => $status === 'published' ? Carbon::parse('2026-06-25')->addDays($i) : null,
            ]);
        }

        $pp = \App\Models\PublicProject::create([
            'code' => 'PP-SSG', 'name' => 'Sunshine Garden', 'developer_name' => 'Sunshine Group',
            'address' => 'P. An Phú, TP. Thủ Đức', 'province' => 'TP. Hồ Chí Minh', 'project_type' => 'urban_area',
            'status' => 'operating', 'blocks' => 2, 'apartments' => 160,
            'amenities_json' => ['gym', 'pool', 'bbq', 'kids'], 'description' => 'Khu căn hộ cao cấp ven sông.', 'is_public' => true,
        ]);
        foreach ([['image', 'Ảnh tổng thể'], ['floor_plan', 'Mặt bằng điển hình'], ['brochure', 'Brochure dự án']] as $i => [$mt, $t]) {
            \App\Models\ProjectMedia::create(['public_project_id' => $pp->id, 'media_type' => $mt, 'title' => $t, 'file_url' => 'public-projects/ssg-'.$mt.'.jpg', 'sort_order' => $i]);
        }
        \App\Models\TenantProjectLink::create([
            'tenant_id' => $tenant->id, 'project_id' => $project->id, 'public_project_id' => $pp->id,
            'linked_by' => $admin->id, 'linked_at' => Carbon::parse('2026-06-01'),
        ]);

        // Thêm dự án public khác để thư viện phong phú.
        $more = [
            ['PP-RVR', 'Riverside Residence', 'Nam Long Group', 'Q.7', 'apartment', 'operating', 3, 240, ['pool', 'gym', 'mall']],
            ['PP-GRN', 'Green Valley', 'Phú Mỹ Hưng', 'Q.7', 'apartment', 'handover', 4, 320, ['park', 'school', 'pool']],
            ['PP-SKY', 'Sky Central', 'Masterise Homes', 'TP. Thủ Đức', 'mixed', 'selling', 2, 180, ['sky_bar', 'gym']],
            ['PP-LOTUS', 'Lotus Lake', 'Ecopark', 'Hưng Yên', 'villa', 'planning', 6, 90, ['lake', 'golf']],
        ];
        foreach ($more as $j => [$code, $name, $dev, $prov, $ptype, $status, $blocks, $apts, $amen]) {
            $x = \App\Models\PublicProject::create([
                'code' => $code, 'name' => $name, 'developer_name' => $dev, 'province' => $prov,
                'project_type' => $ptype, 'status' => $status, 'blocks' => $blocks, 'apartments' => $apts,
                'amenities_json' => $amen, 'description' => $name.' — dự án trong thư viện nền tảng.',
                'is_public' => $status !== 'planning',
            ]);
            \App\Models\ProjectMedia::create(['public_project_id' => $x->id, 'media_type' => 'image', 'title' => 'Phối cảnh', 'file_url' => 'public-projects/'.strtolower($code).'.jpg', 'sort_order' => 0]);
        }
    }

    /** B7 — đóng nốt gap: activity_logs + Tier 6 (ai_requests, ai_approvals, automation_steps, knowledge_chunks). */
    private function seedEntityGapClose(Tenant $tenant, User $admin): void
    {
        for ($i = 0; $i < 5; $i++) {
            \App\Models\ActivityLog::create([
                'tenant_id' => $tenant->id, 'user_id' => $admin->id, 'log_name' => 'default',
                'description' => ['Đăng nhập hệ thống', 'Duyệt bảng kê', 'Cập nhật cư dân', 'Tạo workflow', 'Xuất báo cáo'][$i],
                'created_at' => Carbon::parse('2026-07-01 08:00')->addHours($i), 'updated_at' => Carbon::parse('2026-07-01 08:00')->addHours($i),
            ]);
        }

        $logs = \App\Models\AiUsageLog::where('tenant_id', $tenant->id)->orderBy('id')->take(6)->get();
        foreach ($logs as $i => $log) {
            \App\Models\AiRequest::create([
                'tenant_id' => $tenant->id, 'user_id' => $log->user_id, 'mode' => $log->mode, 'model' => $log->model,
                'prompt' => $log->prompt_excerpt, 'status' => $log->status, 'tokens_in' => $log->tokens_in,
                'tokens_out' => $log->tokens_out, 'latency_ms' => $log->latency_ms,
            ]);
        }
        // Hàng chờ duyệt AI từ các log pending_approval.
        $pending = \App\Models\AiUsageLog::where('tenant_id', $tenant->id)->where('status', 'pending_approval')->take(3)->get();
        foreach ($pending as $p) {
            \App\Models\AiApproval::create([
                'tenant_id' => $tenant->id, 'ai_usage_log_id' => $p->id, 'action' => $p->action,
                'risk_level' => 'high', 'status' => 'pending', 'requested_by_id' => $p->user_id,
            ]);
        }

        // Bảng hoá steps cho workflow.
        foreach (\App\Models\AiWorkflow::where('tenant_id', $tenant->id)->take(3)->get() as $wf) {
            foreach ($wf->steps ?? [] as $s => $step) {
                \App\Models\AutomationStep::create([
                    'ai_workflow_id' => $wf->id, 'step_no' => $s + 1, 'type' => $step['type'] ?? 'action',
                    'label' => $step['label'] ?? 'Bước', 'config' => $step,
                ]);
            }
        }

        // Chunk KB cho vài bài published.
        foreach (\App\Models\KnowledgeArticle::where('status', 'published')->whereNotNull('content_text')->take(4)->get() as $art) {
            $text = (string) $art->content_text;
            \App\Models\KnowledgeChunk::create([
                'knowledge_article_id' => $art->id, 'chunk_index' => 0,
                'content' => mb_substr($text, 0, 500), 'tokens' => (int) ceil(mb_strlen($text) / 4),
            ]);
        }
    }

    /** Tier 5 / B6 — marketplace + dịch vụ + loyalty + BĐS + nhà thông minh. */
    private function seedTier5Ecosystem(Tenant $tenant, Project $project, Building $building): void
    {
        $residents = \App\Models\Resident::where('tenant_id', $tenant->id)->orderBy('id')->take(6)->get();
        $apts = Apartment::where('building_id', $building->id)->orderBy('id')->take(4)->get();
        $r = fn ($i) => $residents[$i % max(1, $residents->count())] ?? null;

        // Marketplace.
        $products = [];
        foreach ([['Tủ lạnh Samsung 2 cửa', 4_500_000, 'used'], ['Xe đạp trẻ em', 800_000, 'used'], ['Bộ bàn ăn gỗ', 3_200_000, 'used']] as $i => [$name, $price, $cond]) {
            $products[] = \App\Models\MarketplaceProduct::create([
                'tenant_id' => $tenant->id, 'project_id' => $project->id, 'seller_resident_id' => $r($i)?->id,
                'name' => $name, 'description' => $name, 'price' => $price, 'category' => 'household', 'condition' => $cond, 'status' => 'active',
            ]);
        }
        $order = \App\Models\MarketplaceOrder::create([
            'tenant_id' => $tenant->id, 'buyer_resident_id' => $r(3)?->id, 'seller_resident_id' => $products[0]->seller_resident_id,
            'code' => 'MO-0001', 'total' => 4_500_000, 'status' => 'completed', 'ordered_at' => Carbon::parse('2026-06-25'),
        ]);
        \App\Models\OrderItem::create(['marketplace_order_id' => $order->id, 'marketplace_product_id' => $products[0]->id, 'quantity' => 1, 'price' => 4_500_000, 'amount' => 4_500_000]);

        // Dịch vụ.
        foreach ([['Giặt là 5 sao', 'laundry', 4.7], ['Bếp nhà An', 'food', 4.5], ['Sửa điện nước 24h', 'repair', 4.3]] as $i => [$name, $cat, $rating]) {
            $sp = \App\Models\ServiceProvider::create(['tenant_id' => $tenant->id, 'name' => $name, 'category' => $cat, 'phone' => '090000000'.$i, 'rating' => $rating, 'status' => 'active']);
            \App\Models\ServiceOrder::create([
                'tenant_id' => $tenant->id, 'service_provider_id' => $sp->id, 'resident_id' => $r($i)?->id,
                'code' => 'SO-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT), 'description' => 'Đặt '.$name,
                'amount' => 100_000 * ($i + 1), 'status' => ['completed', 'confirmed', 'pending'][$i], 'scheduled_at' => Carbon::parse('2026-07-03')->addDays($i),
            ]);
        }

        // Loyalty + voucher.
        foreach ($residents as $i => $res) {
            $acc = \App\Models\LoyaltyAccount::create([
                'tenant_id' => $tenant->id, 'resident_id' => $res->id, 'points_balance' => 500 + $i * 120,
                'tier' => ['silver', 'gold', 'platinum'][$i % 3], 'status' => 'active',
            ]);
            \App\Models\LoyaltyTransaction::create(['loyalty_account_id' => $acc->id, 'type' => 'earn', 'points' => 100, 'description' => 'Thanh toán phí đúng hạn', 'transacted_at' => Carbon::parse('2026-06-10')->addDays($i)]);
        }
        foreach ([['GIAM10', 'Giảm 10% dịch vụ', 'discount', 10, 200], ['QUA-CAFE', 'Voucher cafe', 'gift', 50_000, 500]] as [$code, $name, $type, $val, $cost]) {
            \App\Models\Voucher::create(['tenant_id' => $tenant->id, 'code' => $code, 'name' => $name, 'type' => $type, 'value' => $val, 'points_cost' => $cost, 'quantity' => 100, 'valid_from' => Carbon::parse('2026-07-01'), 'valid_to' => Carbon::parse('2026-12-31'), 'status' => 'active']);
        }

        // Bất động sản.
        foreach ([['sale', 'Bán căn 2PN view sông', 3_800_000_000, 68, 2, 'active'], ['rent', 'Cho thuê 1PN full nội thất', 12_000_000, 45, 1, 'active']] as $i => [$type, $title, $price, $area, $bed, $status]) {
            $listing = \App\Models\RealEstateListing::create([
                'tenant_id' => $tenant->id, 'project_id' => $project->id, 'apartment_id' => $apts[$i]->id ?? null,
                'owner_resident_id' => $r($i)?->id, 'code' => 'RE-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'type' => $type, 'title' => $title, 'price' => $price, 'area' => $area, 'bedrooms' => $bed, 'status' => $status,
                'published_at' => Carbon::parse('2026-06-20')->addDays($i),
            ]);
            \App\Models\ListingInquiry::create(['real_estate_listing_id' => $listing->id, 'resident_id' => $r($i + 1)?->id, 'name' => 'Khách quan tâm', 'phone' => '0911222333', 'message' => 'Xin xem nhà cuối tuần', 'status' => 'new']);
        }

        // Nhà thông minh.
        foreach ($residents->take(3) as $i => $res) {
            $acc = \App\Models\SmartHomeAccount::create([
                'tenant_id' => $tenant->id, 'resident_id' => $res->id, 'apartment_id' => $apts[$i % max(1, $apts->count())]->id ?? null,
                'provider' => ['lumi', 'tuya', 'fpt'][$i % 3], 'status' => 'active', 'linked_at' => Carbon::parse('2026-05-01')->addDays($i),
            ]);
            foreach ([['Đèn phòng khách', 'light', 'Phòng khách', 'on'], ['Khóa cửa chính', 'lock', 'Cửa chính', 'online'], ['Điều hòa phòng ngủ', 'ac', 'Phòng ngủ', 'off']] as $d => [$name, $type, $room, $st]) {
                $dev = \App\Models\SmartDevice::create(['smart_home_account_id' => $acc->id, 'name' => $name, 'type' => $type, 'room' => $room, 'status' => $st]);
                if ($d === 0) {
                    \App\Models\SensorEvent::create(['tenant_id' => $tenant->id, 'smart_device_id' => $dev->id, 'type' => 'motion', 'value' => 'detected', 'event_at' => Carbon::parse('2026-07-01 19:00')->addMinutes($i * 5)]);
                }
            }
            \App\Models\SmartScene::create(['smart_home_account_id' => $acc->id, 'name' => 'Về nhà', 'description' => 'Bật đèn + điều hòa', 'is_active' => true]);
            \App\Models\EnergyReading::create(['tenant_id' => $tenant->id, 'apartment_id' => $acc->apartment_id, 'smart_home_account_id' => $acc->id, 'period' => '2026-06', 'kwh' => 220 + $i * 30, 'cost' => (220 + $i * 30) * 2500, 'reading_date' => Carbon::parse('2026-06-30')]);
        }
    }

    /** Tier 5 / B5 — bàn giao/bảo hành + cộng đồng/sự kiện/bình chọn. */
    private function seedTier5Community(Tenant $tenant, Project $project, Building $building): void
    {
        $apts = Apartment::where('building_id', $building->id)->orderBy('id')->take(6)->get();
        $residents = \App\Models\Resident::where('tenant_id', $tenant->id)->orderBy('id')->take(6)->get();

        // Bàn giao.
        $batch = \App\Models\HandoverBatch::create([
            'tenant_id' => $tenant->id, 'project_id' => $project->id, 'building_id' => $building->id,
            'code' => 'HB-2026-01', 'name' => 'Đợt bàn giao Tòa A - Block 1', 'scheduled_date' => Carbon::parse('2022-06-20'),
            'total_units' => $apts->count(), 'status' => 'completed',
        ]);
        foreach ($apts as $i => $apt) {
            $unit = \App\Models\HandoverUnit::create([
                'handover_batch_id' => $batch->id, 'apartment_id' => $apt->id,
                'resident_id' => $residents[$i % max(1, $residents->count())]->id ?? null,
                'status' => $i % 4 === 0 ? 'pending_defects' : 'handed_over', 'handed_over_at' => Carbon::parse('2022-07-01'),
            ]);
            $cl = \App\Models\HandoverChecklist::create(['handover_unit_id' => $unit->id, 'name' => 'Nghiệm thu căn hộ', 'status' => $i % 4 === 0 ? 'failed' : 'passed']);
            foreach (['Tường/trần', 'Điện nước', 'Cửa & khóa'] as $s => $label) {
                \App\Models\HandoverPunchItem::create([
                    'handover_checklist_id' => $cl->id, 'label' => $label, 'is_ok' => ! ($i % 4 === 0 && $s === 0),
                    'severity' => $s === 0 ? 'major' : 'minor', 'note' => $i % 4 === 0 && $s === 0 ? 'Nứt nhẹ trần bếp' : null,
                ]);
            }
        }
        // Bảo hành.
        foreach ([['Thấm trần nhà tắm', 'waterproof', 'in_progress'], ['Ổ cắm không hoạt động', 'electrical', 'resolved']] as $i => [$title, $cat, $status]) {
            \App\Models\WarrantyRequest::create([
                'tenant_id' => $tenant->id, 'project_id' => $project->id, 'apartment_id' => $apts[$i]->id ?? null,
                'resident_id' => $residents[$i]->id ?? null, 'code' => 'BH-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'title' => $title, 'description' => $title.'.', 'category' => $cat, 'status' => $status,
                'reported_at' => Carbon::parse('2026-06-15')->addDays($i), 'resolved_at' => $status === 'resolved' ? Carbon::parse('2026-06-20') : null,
            ]);
        }

        // Cộng đồng.
        $group = \App\Models\CommunityGroup::create([
            'tenant_id' => $tenant->id, 'project_id' => $project->id, 'name' => 'Cư dân Sunshine Garden',
            'description' => 'Nhóm trao đổi chung', 'member_count' => 320, 'status' => 'active',
        ]);
        foreach (['Tìm người đi chung xe đi làm', 'Thanh lý tủ lạnh còn mới', 'Góp ý khu vui chơi trẻ em'] as $i => $title) {
            \App\Models\CommunityPost::create([
                'tenant_id' => $tenant->id, 'project_id' => $project->id, 'community_group_id' => $group->id,
                'author_resident_id' => $residents[$i % max(1, $residents->count())]->id ?? null,
                'title' => $title, 'body' => $title.'...', 'like_count' => 5 + $i * 3, 'comment_count' => $i * 2, 'status' => 'published',
            ]);
        }

        // Sự kiện.
        $event = \App\Models\Event::create([
            'tenant_id' => $tenant->id, 'project_id' => $project->id, 'title' => 'Tết Trung Thu 2026',
            'description' => 'Đêm hội trăng rằm cho các bé', 'location' => 'Sảnh chính', 'capacity' => 200, 'registered_count' => 0,
            'starts_at' => Carbon::parse('2026-09-15 18:00'), 'ends_at' => Carbon::parse('2026-09-15 21:00'), 'status' => 'upcoming',
        ]);
        $reg = 0;
        foreach ($residents as $i => $res) {
            $reg += 1 + $i;
            \App\Models\EventRegistration::create([
                'event_id' => $event->id, 'resident_id' => $res->id, 'guests' => $i, 'status' => 'registered',
            ]);
        }
        $event->update(['registered_count' => $reg]);

        // Bình chọn.
        $poll = \App\Models\Poll::create([
            'tenant_id' => $tenant->id, 'project_id' => $project->id, 'question' => 'Chọn màu sơn mới cho sảnh',
            'type' => 'single', 'status' => 'open', 'closes_at' => Carbon::parse('2026-07-31'),
        ]);
        $options = [];
        foreach (['Trắng kem', 'Xám nhạt', 'Xanh pastel'] as $s => $label) {
            $options[] = \App\Models\PollOption::create(['poll_id' => $poll->id, 'label' => $label, 'sort' => $s]);
        }
        $voteCount = 0;
        foreach ($residents as $i => $res) {
            $opt = $options[$i % count($options)];
            \App\Models\PollVote::create(['poll_id' => $poll->id, 'poll_option_id' => $opt->id, 'resident_id' => $res->id]);
            $opt->increment('vote_count');
            $voteCount++;
        }
        $poll->update(['vote_count' => $voteCount]);
    }

    /** Tier 4 / B4 — Form Builder (biểu mẫu động + lượt nộp). */
    private function seedTier4FormBuilder(Tenant $tenant, Project $project, User $admin): void
    {
        $residents = \App\Models\Resident::where('tenant_id', $tenant->id)->orderBy('id')->take(3)->get();
        $formDefs = [
            ['Đăng ký chuyển đồ', 'operations', [['Họ tên', 'text', true], ['Ngày chuyển', 'date', true], ['Thang máy', 'select', true]]],
            ['Đăng ký sửa chữa nội thất', 'operations', [['Nội dung', 'textarea', true], ['Đơn vị thi công', 'text', false], ['Thời gian', 'date', true]]],
        ];
        foreach ($formDefs as $fi => [$name, $cat, $fields]) {
            $form = \App\Models\DynamicForm::create([
                'tenant_id' => $tenant->id, 'project_id' => $project->id, 'code' => 'FORM-'.($fi + 1),
                'name' => $name, 'description' => $name, 'category' => $cat, 'status' => 'published',
                'current_version' => 1, 'created_by_id' => $admin->id,
            ]);
            \App\Models\FormVersion::create([
                'dynamic_form_id' => $form->id, 'version' => 1, 'schema' => ['fields' => count($fields)],
                'status' => 'published', 'published_at' => Carbon::parse('2026-06-01'),
            ]);
            $section = \App\Models\FormSection::create(['dynamic_form_id' => $form->id, 'title' => 'Thông tin', 'sort' => 0]);
            foreach ($fields as $s => [$label, $type, $required]) {
                \App\Models\FormField::create([
                    'dynamic_form_id' => $form->id, 'form_section_id' => $section->id,
                    'key' => 'field_'.($s + 1), 'label' => $label, 'type' => $type,
                    'options' => $type === 'select' ? ['A', 'B', 'C'] : null, 'required' => $required, 'sort' => $s,
                ]);
            }
            \App\Models\FormWorkflow::create([
                'dynamic_form_id' => $form->id, 'name' => 'Duyệt bởi BQL',
                'steps' => [['role' => 'building_manager', 'action' => 'approve']], 'status' => 'active',
            ]);

            // Lượt nộp.
            $fieldModels = \App\Models\FormField::where('dynamic_form_id', $form->id)->get();
            foreach (['submitted', 'approved'] as $si => $status) {
                $res = $residents[$si % max(1, $residents->count())] ?? null;
                $sub = \App\Models\FormSubmission::create([
                    'tenant_id' => $tenant->id, 'dynamic_form_id' => $form->id, 'resident_id' => $res?->id,
                    'status' => $status, 'data' => ['field_1' => 'Giá trị mẫu'], 'submitted_at' => Carbon::parse('2026-06-20')->addDays($si),
                ]);
                foreach ($fieldModels as $fm) {
                    \App\Models\FormSubmissionValue::create([
                        'form_submission_id' => $sub->id, 'form_field_id' => $fm->id,
                        'field_key' => $fm->key, 'value' => 'Giá trị '.$fm->label,
                    ]);
                }
            }
        }
    }

    /** Tier 4 / B3 — nhà thầu + hợp đồng + tài sản + đồng hồ + IoT. */
    private function seedTier4AssetsContractors(Tenant $tenant, Project $project, Building $building, User $admin): void
    {
        $team = \App\Models\Team::where('tenant_id', $tenant->id)->first();

        foreach ([['Cty Thang máy Thiên Nam', 'elevator', 4.6], ['Cty Vệ sinh Sạch Xanh', 'cleaning', 4.2]] as $ci => [$name, $svc, $rating]) {
            $contractor = \App\Models\Contractor::create([
                'tenant_id' => $tenant->id, 'code' => 'NT-'.($ci + 1), 'name' => $name, 'tax_code' => '03123'.$ci,
                'phone' => '028 3822 00'.$ci, 'service_type' => $svc, 'rating' => $rating, 'status' => 'active',
            ]);
            $contract = \App\Models\Contract::create([
                'tenant_id' => $tenant->id, 'project_id' => $project->id, 'contractor_id' => $contractor->id,
                'code' => 'HD-2026-'.str_pad((string) ($ci + 1), 3, '0', STR_PAD_LEFT), 'title' => 'Hợp đồng '.$svc.' 2026',
                'type' => 'maintenance', 'value' => 240_000_000 * ($ci + 1),
                'start_date' => Carbon::parse('2026-01-01'), 'end_date' => Carbon::parse('2026-12-31'), 'status' => 'active',
            ]);
            $pkg = \App\Models\ContractPackage::create([
                'contract_id' => $contract->id, 'name' => 'Bảo trì định kỳ quý', 'value' => 60_000_000 * ($ci + 1), 'status' => 'active',
            ]);
            \App\Models\ContractAcceptance::create([
                'contract_id' => $contract->id, 'contract_package_id' => $pkg->id, 'code' => 'NT-'.($ci + 1).'-Q1',
                'title' => 'Nghiệm thu Q1', 'amount' => 60_000_000 * ($ci + 1), 'status' => 'accepted',
                'accepted_by_id' => $admin->id, 'accepted_at' => Carbon::parse('2026-04-01'),
            ]);
            \App\Models\ContractorKpi::create([
                'contractor_id' => $contractor->id, 'period' => '2026-06', 'score' => $rating * 20,
                'on_time_rate' => 95 - $ci * 5, 'quality_score' => 90 - $ci * 3, 'note' => 'Đạt yêu cầu',
            ]);
            \App\Models\ContractorSettlement::create([
                'contractor_id' => $contractor->id, 'contract_id' => $contract->id, 'period' => '2026-Q1',
                'amount' => 60_000_000 * ($ci + 1), 'status' => 'paid', 'settled_at' => Carbon::parse('2026-04-10'),
            ]);
        }

        $cats = [];
        foreach (['Thang máy', 'Điện', 'PCCC', 'HVAC'] as $cn) {
            $cats[] = \App\Models\AssetCategory::create(['tenant_id' => $tenant->id, 'code' => strtoupper(substr($cn, 0, 3)), 'name' => $cn]);
        }
        for ($i = 0; $i < 6; $i++) {
            \App\Models\Asset::create([
                'tenant_id' => $tenant->id, 'project_id' => $project->id, 'building_id' => $building->id,
                'asset_category_id' => $cats[$i % count($cats)]->id, 'code' => 'TS-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'name' => ['Thang máy #1', 'Thang máy #2', 'Máy phát điện', 'Bơm PCCC', 'Chiller HVAC', 'Tủ điện tổng'][$i],
                'serial_no' => 'SN'.(1000 + $i), 'location' => 'Tầng kỹ thuật', 'purchase_date' => Carbon::parse('2022-05-01'),
                'value' => 500_000_000 + $i * 50_000_000, 'warranty_until' => Carbon::parse('2027-05-01'),
                'status' => $i === 4 ? 'maintenance' : 'active',
            ]);
        }
        $assets = \App\Models\Asset::where('tenant_id', $tenant->id)->take(2)->get();
        foreach ($assets as $i => $asset) {
            \App\Models\MaintenancePlan::create([
                'tenant_id' => $tenant->id, 'project_id' => $project->id, 'asset_id' => $asset->id, 'team_id' => $team?->id,
                'name' => 'Bảo trì '.$asset->name, 'frequency' => ['monthly', 'quarterly'][$i], 'status' => 'active',
                'next_due_at' => Carbon::parse('2026-08-01')->addDays($i * 15),
            ]);
        }

        for ($i = 0; $i < 4; $i++) {
            $meter = \App\Models\Meter::create([
                'tenant_id' => $tenant->id, 'project_id' => $project->id, 'building_id' => $building->id,
                'code' => 'MT-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'type' => ['electric', 'water', 'electric', 'water'][$i], 'unit' => $i % 2 === 0 ? 'kWh' : 'm³',
                'last_reading' => 1000 + $i * 100, 'status' => 'active', 'installed_at' => Carbon::parse('2022-06-01'),
            ]);
            \App\Models\MeterReading::create([
                'meter_id' => $meter->id, 'period' => '2026-06', 'previous_reading' => 900 + $i * 100,
                'current_reading' => 1000 + $i * 100, 'consumption' => 100, 'reading_date' => Carbon::parse('2026-06-30'),
                'recorded_by_id' => $admin->id,
            ]);
        }

        foreach ([['Cảm biến khói tầng 5', 'sensor', 'lora'], ['Gateway IoT trung tâm', 'gateway', 'mqtt'], ['Van nước tự động', 'actuator', 'modbus'], ['Cảm biến nhiệt HVAC', 'sensor', 'zigbee']] as $i => [$name, $type, $proto]) {
            \App\Models\IotDevice::create([
                'tenant_id' => $tenant->id, 'project_id' => $project->id, 'building_id' => $building->id,
                'code' => 'IOT-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT), 'name' => $name, 'type' => $type,
                'protocol' => $proto, 'status' => $i === 3 ? 'offline' : 'online', 'last_seen_at' => Carbon::parse('2026-07-01 12:00'),
            ]);
        }
    }

    /** Tier 4 / B2 — admin ops (ticket, data-fix, import/export, integration, gateway). */
    private function seedTier4AdminOps(Tenant $tenant, User $admin): void
    {
        // NOTE: support_tickets + data correction are now the platform-level Support
        // Center (Batch 10) — seeded in seedBatch10Support(), no longer per-tenant here.
        foreach ([['residents', 240, 236, 4], ['apartments', 160, 160, 0]] as $i => [$type, $tot, $ok, $err]) {
            \App\Models\ImportJob::create([
                'tenant_id' => $tenant->id, 'type' => $type, 'file_path' => 'imports/'.$type.'.xlsx',
                'status' => 'done', 'total_rows' => $tot, 'success_rows' => $ok, 'error_rows' => $err,
                'created_by_id' => $admin->id, 'finished_at' => Carbon::parse('2026-06-20 10:00')->addDays($i),
            ]);
        }
        foreach ([['statements', 'xlsx'], ['debts', 'pdf']] as $i => [$type, $fmt]) {
            \App\Models\ExportJob::create([
                'tenant_id' => $tenant->id, 'type' => $type, 'format' => $fmt, 'status' => 'done',
                'file_path' => 'exports/'.$type.'.'.$fmt, 'created_by_id' => $admin->id,
                'finished_at' => Carbon::parse('2026-07-01 08:00')->addHours($i),
            ]);
        }

        // NOTE: integration_connections is now the platform-level Integration Center
        // (Batch 08) — seeded in seedBatch08Integration(), no longer per-tenant here.
        foreach ([['vnpay', 'production', true], ['momo', 'sandbox', false]] as [$gw, $env, $active]) {
            \App\Models\PaymentGatewayConfig::create([
                'tenant_id' => $tenant->id, 'gateway' => $gw, 'merchant_id' => strtoupper($gw).'-MID-001',
                'environment' => $env, 'is_active' => $active,
            ]);
        }
    }

    /** Tier 4 / B1 — SaaS billing (gói, thuê bao, hóa đơn, module, usage). */
    private function seedTier4Saas(Tenant $tenant): void
    {
        // --- Modules (M01..M12) + features ---
        $moduleDefs = [
            ['M01', 'Core SaaS & Tenant', ['tenant_core', 'subscription']],
            ['M02', 'Global IAM & Account', ['global_account', 'resident_binding', 'rbac']],
            ['M03', 'Master Data & Building', ['master_data']],
            ['M04', 'Content & Project CMS', ['platform_content', 'public_project']],
            ['M05', 'Resident Engagement', ['notification', 'feedback', 'forms']],
            ['M06', 'Finance & Fee', ['fee_setup', 'billing', 'payment']],
            ['M07', 'Facility & Work Order', ['work_order', 'maintenance', 'sla']],
            ['M08', 'Asset / IOC / Security', ['asset', 'security', 'patrol']],
            ['M09', 'Reports / Dashboard', ['dashboard', 'report_library']],
            ['M10', 'Shared Contractor / Supplier', ['contractor_library', 'supplier_library']],
            ['M11', 'Document Template / KB', ['document_template', 'kb_inheritance']],
            ['M12', 'AI / Automation / Governance', ['x2ai', 'rag', 'prompt_guardrail', 'ai_audit']],
        ];
        $featureByCode = [];
        foreach ($moduleDefs as [$mcode, $mname, $fcodes]) {
            $module = \App\Models\Module::create(['code' => $mcode, 'name' => $mname]);
            foreach ($fcodes as $fc) {
                $featureByCode[$fc] = \App\Models\Feature::create(['module_id' => $module->id, 'code' => $fc, 'name' => ucwords(str_replace('_', ' ', $fc))]);
            }
        }

        // --- Plans (popular|full|intelligent) + plan_features ---
        $planDefs = [
            ['popular', 'Phổ biến', 2_000_000, 20_000_000],
            ['full', 'Đầy đủ', 6_000_000, 60_000_000],
            ['intelligent', 'Thông minh', 15_000_000, 150_000_000],
        ];
        // feature phổ biến / thêm ở full / thêm ở intelligent
        $popular = ['tenant_core', 'subscription', 'global_account', 'resident_binding', 'rbac', 'master_data', 'platform_content', 'notification', 'feedback', 'forms', 'fee_setup', 'billing', 'payment', 'work_order', 'maintenance', 'sla', 'asset', 'security', 'patrol', 'dashboard', 'document_template'];
        $fullExtra = ['public_project', 'contractor_library', 'supplier_library', 'report_library'];
        $intelExtra = ['kb_inheritance', 'x2ai', 'rag', 'prompt_guardrail', 'ai_audit'];
        $planByCode = [];
        foreach ($planDefs as [$code, $name, $mo, $yr]) {
            $plan = \App\Models\Plan::create(['code' => $code, 'name' => $name, 'description' => 'Gói '.$name, 'monthly_base_price' => $mo, 'yearly_base_price' => $yr]);
            $set = $popular;
            if ($code !== 'popular') {
                $set = array_merge($set, $fullExtra);
            }
            if ($code === 'intelligent') {
                $set = array_merge($set, $intelExtra);
            }
            foreach ($set as $fc) {
                if (isset($featureByCode[$fc])) {
                    \App\Models\PlanFeature::create(['plan_id' => $plan->id, 'feature_id' => $featureByCode[$fc]->id, 'enabled' => true]);
                }
            }
            $planByCode[$code] = $plan;
        }

        // --- Tenant demo: thuê bao gói Thông minh + entitlements (feature gate) ---
        \App\Models\TenantSubscription::create([
            'tenant_id' => $tenant->id, 'plan_id' => $planByCode['intelligent']->id, 'status' => 'active',
            'billing_cycle' => 'monthly', 'start_date' => Carbon::parse('2026-01-01'), 'end_date' => Carbon::parse('2026-12-31'),
            'auto_renew' => true, 'mrr' => 15_000_000, 'arr' => 180_000_000, 'currency' => 'VND',
        ]);
        foreach (\App\Models\PlanFeature::where('plan_id', $planByCode['intelligent']->id)->pluck('feature_id') as $fid) {
            \App\Models\TenantEntitlement::create(['tenant_id' => $tenant->id, 'feature_id' => $fid, 'enabled' => true, 'source' => 'plan', 'starts_at' => Carbon::parse('2026-07-01')]);
        }
        // 1 override: tắt marketplace-like module minh hoạ (M10) cho tenant demo.
        \App\Models\TenantModuleOverride::create([
            'tenant_id' => $tenant->id, 'module_id' => \App\Models\Module::where('code', 'M10')->value('id'),
            'enabled' => false, 'reason' => 'Chưa dùng thư viện đối tác dùng chung',
        ]);

        // Batch 07 — dữ liệu billing SaaS đầy đủ (6 tenant, contract, addon, usage, invoice, wallet, adjustment).
        $this->seedBatch07Billing($planByCode);
    }

    /** Batch 07 — SaaS billing/metering/revenue demo (theo BATCH_07_SEED_DATA_CATALOG). */
    private function seedBatch07Billing(array $planByCode): void
    {
        // Bảng giá theo chu kỳ.
        foreach ($planByCode as $plan) {
            foreach ([['monthly', 1, 0], ['quarterly', 3, 5], ['yearly', 12, 15]] as [$cycle, $mult, $disc]) {
                \App\Models\PlanPrice::create([
                    'plan_id' => $plan->id, 'billing_cycle' => $cycle, 'currency' => 'VND',
                    'price' => $plan->monthly_base_price * $mult, 'discount_percent' => $disc,
                ]);
            }
        }

        // Định nghĩa meter.
        $meters = [
            ['buildings', 'Số tòa', 'tòa'], ['apartments', 'Căn hộ', 'căn'], ['storage_gb', 'Lưu trữ', 'GB'],
            ['sms_count', 'SMS', 'tin'], ['zalo_zns_count', 'Zalo ZNS', 'tin'], ['email_count', 'Email', 'email'],
            ['api_calls', 'API calls', 'lượt'], ['ai_tokens', 'AI tokens', 'token'],
        ];
        foreach ($meters as [$code, $name, $unit]) {
            \App\Models\UsageMeter::create(['code' => $code, 'name' => $name, 'unit' => $unit, 'is_billable' => true]);
        }

        // 6 tenant billing (tạo tenant nếu chưa có theo code).
        $tenantDefs = [
            ['TEN-0001', 'Sunshine Group', 'intelligent', 'active', 245_000_000, 'HDTB-2026-0001', 'monthly', '2026-01-01', '2026-12-31', true],
            ['TEN-0002', 'An Phú Management', 'full', 'active', 186_000_000, 'HDTB-2026-0002', 'monthly', '2026-01-01', '2026-06-08', true],
            ['TEN-0003', 'Maple Residence Services', 'full', 'active', 124_000_000, 'HDTB-2026-0003', 'quarterly', '2026-03-15', '2026-06-14', true],
            ['TEN-0004', 'Green Home PM', 'popular', 'trial', 0, 'TRIAL-2026-0004', 'monthly', '2026-05-10', '2026-06-09', false],
            ['TEN-0005', 'Central Lake', 'full', 'pending_renewal', 36_000_000, 'HDTB-2026-0005', 'yearly', '2025-06-01', '2026-05-31', true],
            ['TEN-0006', 'Nova Operations', 'popular', 'suspended', 7_000_000, 'HDTB-2026-0006', 'monthly', '2026-02-01', '2026-04-30', false],
        ];
        $subByCode = [];
        $tenantByCode = [];
        foreach ($tenantDefs as [$code, $name, $planCode, $status, $mrr, $contractNo, $cycle, $start, $end, $auto]) {
            $t = \App\Models\Tenant::firstOrCreate(['code' => $code], ['name' => $name, 'status' => $status === 'suspended' ? 'suspended' : 'active']);
            $tenantByCode[$code] = $t;
            $contractStatus = ['pending_renewal' => 'near_expiry', 'suspended' => 'expired', 'trial' => 'draft'][$status] ?? 'active';
            $contract = \App\Models\SubscriptionContract::create([
                'tenant_id' => $t->id, 'contract_no' => $contractNo, 'contract_type' => $status === 'trial' ? 'trial' : 'standard',
                'status' => $contractStatus, 'start_date' => $start, 'end_date' => $end, 'annual_value' => $mrr * 12,
                'payment_terms' => 'Net 15', 'sla_code' => 'SLA-STD',
            ]);
            $sub = \App\Models\TenantSubscription::create([
                'tenant_id' => $t->id, 'plan_id' => $planByCode[$planCode]->id, 'status' => $status,
                'billing_cycle' => $cycle, 'start_date' => $start, 'end_date' => $end, 'auto_renew' => $auto,
                'mrr' => $mrr, 'arr' => $mrr * 12, 'currency' => 'VND', 'contract_id' => $contract->id,
            ]);
            $subByCode[$code] = $sub;
            \App\Models\SubscriptionItem::create([
                'subscription_id' => $sub->id, 'item_type' => 'plan', 'name' => 'Gói '.$planByCode[$planCode]->name,
                'quantity' => 1, 'unit_price' => $mrr, 'amount' => $mrr,
            ]);
        }

        // Renewal pipeline cho tenant sắp hết hạn.
        \App\Models\SubscriptionRenewal::create([
            'subscription_id' => $subByCode['TEN-0005']->id, 'contract_id' => $subByCode['TEN-0005']->contract_id,
            'stage' => 'negotiation', 'target_date' => '2026-05-31', 'proposed_value' => 432_000_000, 'note' => 'Đàm phán gia hạn năm 2',
        ]);

        // Add-ons.
        $addonDefs = [
            ['TEN-0001', 'ADD-AI-500K', 'AI Token Pack', 20_000_000, 'ai_token'],
            ['TEN-0001', 'ADD-SUPPORT-24-7', 'Premium Support 24/7', 10_000_000, null],
            ['TEN-0002', 'ADD-SMS-50K', 'SMS Pack', 5_000_000, 'sms'],
            ['TEN-0003', 'ADD-STORAGE-1TB', 'Storage 1TB', 2_500_000, 'storage'],
        ];
        foreach ($addonDefs as [$tc, $ac, $an, $price, $wt]) {
            \App\Models\SubscriptionAddon::create([
                'subscription_id' => $subByCode[$tc]->id, 'addon_code' => $ac, 'name' => $an,
                'quantity' => 1, 'unit_price' => $price, 'mrr' => $price, 'wallet_type' => $wt, 'status' => 'active', 'start_date' => '2026-05-01',
            ]);
        }

        // Kỳ usage đã khóa + records.
        $period = \App\Models\UsagePeriod::create([
            'code' => 'USAGE-2026-05', 'period_start' => '2026-05-01', 'period_end' => '2026-05-31',
            'status' => 'locked', 'locked_at' => Carbon::parse('2026-06-01 07:30'), 'locked_by' => 'Nguyễn Minh Anh',
        ]);
        $usageDefs = [
            ['TEN-0001', ['buildings' => [12, 20], 'apartments' => [3280, 30000], 'storage_gb' => [1256, 1000], 'sms_count' => [18420, 50000], 'ai_tokens' => [3200000, 20000000]], 28_600_000],
            ['TEN-0002', ['buildings' => [7, 5], 'apartments' => [2010, 5000], 'storage_gb' => [164, 200], 'sms_count' => [9870, 10000], 'ai_tokens' => [1500000, 0]], 16_200_000],
            ['TEN-0003', ['buildings' => [5, 5], 'apartments' => [1450, 5000], 'storage_gb' => [108, 200], 'sms_count' => [6120, 10000]], 9_800_000],
        ];
        foreach ($usageDefs as [$tc, $meters2, $overageTotal]) {
            foreach ($meters2 as $mt => [$val, $limit]) {
                $over = max(0, $val - $limit);
                \App\Models\UsageRecord::create([
                    'usage_period_id' => $period->id, 'tenant_id' => $tenantByCode[$tc]->id, 'meter_type' => $mt,
                    'usage_value' => $val, 'included_limit' => $limit, 'overage_value' => $over,
                    'overage_amount' => $over > 0 ? round($overageTotal / max(1, count(array_filter($meters2, fn ($m) => $m[0] > $m[1])))) : 0,
                    'source' => 'collected', 'status' => 'locked',
                ]);
            }
        }

        // Quota alerts.
        $qaDefs = [
            ['QA-2026-0001', 'TEN-0001', 'ai_tokens', 28_600_000, 20_000_000, 43, 96_000_000, 'Nâng lên gói Thông minh Plus hoặc mua AI Token Pack'],
            ['QA-2026-0002', 'TEN-0002', 'storage_gb', 18_000, 15_000, 20, 60_000_000, 'Tăng quota storage'],
            ['QA-2026-0003', 'TEN-0003', 'sms_count', 12_800, 10_000, 28, 12_800_000, 'Mua thêm SMS Pack'],
        ];
        foreach ($qaDefs as [$code, $tc, $mt, $usage, $limit, $pct, $fee, $rec]) {
            \App\Models\QuotaAlert::create([
                'code' => $code, 'tenant_id' => $tenantByCode[$tc]->id, 'usage_period_id' => $period->id,
                'meter_type' => $mt, 'usage_value' => $usage, 'included_limit' => $limit, 'over_percent' => $pct,
                'estimated_fee' => $fee, 'recommendation' => $rec, 'status' => 'open',
            ]);
        }

        // Invoices + lines + payments.
        $invDefs = [
            ['INV-2026-05-118', 'TEN-0001', '2026-05', 'partially_paid', '2026-06-15', 286_400_000, 245_000_000, [
                ['subscription', 'Gói Enterprise', 220_000_000], ['addon', 'AI Insight', 25_000_000],
                ['usage_overage', 'Overage lưu trữ 1.25 TB', 150_000_000], ['discount', 'Chiết khấu hợp đồng 10%', -39_500_000],
                ['tax', 'VAT 10%', 26_550_000],
            ]],
            ['INV-2026-05-119', 'TEN-0002', '2026-05', 'issued', '2026-06-15', 395_400_000, 0, [
                ['subscription', 'Gói Business Plus', 186_000_000], ['usage_overage', 'Overage SMS', 16_200_000], ['tax', 'VAT 10%', 20_220_000],
            ]],
        ];
        foreach ($invDefs as [$no, $tc, $per, $status, $due, $total, $paid, $lines]) {
            $sub = 0;
            $disc = 0;
            $tax = 0;
            foreach ($lines as [$lt, $desc, $amt]) {
                if ($lt === 'discount') {
                    $disc += abs($amt);
                } elseif ($lt === 'tax') {
                    $tax += abs($amt);
                } else {
                    $sub += $amt;
                }
            }
            $inv = \App\Models\BillingInvoice::create([
                'invoice_no' => $no, 'tenant_id' => $tenantByCode[$tc]->id, 'subscription_id' => $subByCode[$tc]->id,
                'period' => $per, 'status' => $status, 'issue_date' => '2026-06-01', 'due_date' => $due,
                'subtotal' => $sub, 'discount_total' => $disc, 'tax_total' => $tax, 'total_amount' => $total,
                'paid_amount' => $paid, 'remaining_amount' => $total - $paid, 'currency' => 'VND',
            ]);
            foreach ($lines as [$lt, $desc, $amt]) {
                \App\Models\BillingInvoiceLine::create([
                    'invoice_id' => $inv->id, 'line_type' => $lt, 'description' => $desc,
                    'quantity' => 1, 'unit_price' => $amt, 'amount' => $amt, 'tax_rate' => $lt === 'tax' ? 10 : 0,
                ]);
            }
            if ($paid > 0) {
                \App\Models\BillingPayment::create([
                    'invoice_id' => $inv->id, 'tenant_id' => $tenantByCode[$tc]->id, 'payment_method' => 'bank_transfer',
                    'amount' => $paid, 'paid_at' => Carbon::parse('2026-06-10'), 'transaction_ref' => 'FT'.$inv->id.'2026', 'status' => 'confirmed',
                ]);
            }
        }

        // Pass-through wallets + 1 giao dịch mẫu.
        $walletDefs = [
            ['TEN-0001', 'sms', 216_800_000, 2_400_000, true, 5_000_000],
            ['TEN-0002', 'zalo', 67_400_000, 1_200_000, true, 2_000_000],
            ['TEN-0003', 'email', 31_800_000, 850_000, false, 0],
            ['TEN-0004', 'ai_token', 82_300_000, 3_600_000, true, 10_000_000],
        ];
        foreach ($walletDefs as [$tc, $wt, $bal, $target, $auto, $topupAmt]) {
            $w = \App\Models\PassThroughWallet::create([
                'tenant_id' => $tenantByCode[$tc]->id, 'wallet_type' => $wt, 'balance' => $bal, 'currency' => 'VND',
                'monthly_target' => $target, 'low_balance_threshold' => $target * 2, 'auto_topup_enabled' => $auto,
                'auto_topup_amount' => $topupAmt, 'status' => 'active',
            ]);
            \App\Models\PassThroughTransaction::create([
                'wallet_id' => $w->id, 'tenant_id' => $tenantByCode[$tc]->id, 'transaction_type' => 'top_up',
                'amount' => $target, 'balance_after' => $bal, 'description' => 'Nạp đầu kỳ', 'status' => 'confirmed',
            ]);
        }

        // Adjustments + credit note.
        $adjDefs = [
            ['ADJ-2026-000145', 'TEN-0001', 'INV-2026-05-118', 'overcharge_sms', 4_250_000, 'pending_approval'],
            ['ADJ-2026-000144', 'TEN-0002', 'INV-2026-05-119', 'duplicate_overage', -2_150_000, 'pending_approval'],
            ['ADJ-2026-000143', 'TEN-0003', null, 'courtesy_discount', -5_000_000, 'approved'],
        ];
        foreach ($adjDefs as [$cid, $tc, $invNo, $type, $amt, $status]) {
            $inv = $invNo ? \App\Models\BillingInvoice::where('invoice_no', $invNo)->first() : null;
            $adj = \App\Models\BillingAdjustment::create([
                'case_id' => $cid, 'tenant_id' => $tenantByCode[$tc]->id, 'invoice_id' => $inv?->id,
                'adjustment_type' => $type, 'amount' => $amt, 'reason' => 'Điều chỉnh billing', 'status' => $status,
            ]);
            if ($status === 'approved') {
                \App\Models\CreditNote::create([
                    'credit_note_no' => 'CN-'.$cid, 'tenant_id' => $tenantByCode[$tc]->id, 'invoice_id' => $inv?->id,
                    'adjustment_id' => $adj->id, 'amount' => abs($amt), 'reason' => 'Ghi có từ điều chỉnh', 'status' => 'issued', 'issued_at' => now(),
                ]);
            }
        }
    }

    /** Tier 3 / A4 — an ninh & thiết bị (tuần tra, sự cố, SOS, access device, camera, alert action). */
    private function seedTier3Security(Tenant $tenant, Project $project, Building $building, User $admin): void
    {
        $staff = User::where('tenant_id', $tenant->id)->where('account_type', 'staff')->orderBy('id')->get();
        $guard = $staff->first() ?? $admin;
        $residents = \App\Models\Resident::where('tenant_id', $tenant->id)->orderBy('id')->take(4)->get();
        $apts = Apartment::where('building_id', $building->id)->orderBy('id')->take(4)->get();

        // Tuần tra.
        foreach ([['PT-A', 'Tuyến A - Tầng hầm & sảnh'], ['PT-B', 'Tuyến B - Hành lang & mái']] as $ri => [$code, $name]) {
            $route = \App\Models\PatrolRoute::create([
                'tenant_id' => $tenant->id, 'project_id' => $project->id, 'building_id' => $building->id,
                'code' => $code, 'name' => $name, 'expected_minutes' => 30, 'status' => 'active',
            ]);
            for ($c = 1; $c <= 4; $c++) {
                \App\Models\PatrolCheckpoint::create([
                    'patrol_route_id' => $route->id, 'code' => $code.'-C'.$c, 'name' => 'Chốt '.$c,
                    'location' => 'Vị trí '.$c, 'qr_code' => 'QRCP-'.$code.'-'.$c, 'sort' => $c,
                ]);
            }
            \App\Models\PatrolSession::create([
                'patrol_route_id' => $route->id, 'guard_id' => $guard->id,
                'status' => $ri === 0 ? 'completed' : 'in_progress', 'checkpoints_scanned' => $ri === 0 ? 4 : 2,
                'started_at' => Carbon::parse('2026-07-01 22:00'), 'finished_at' => $ri === 0 ? Carbon::parse('2026-07-01 22:35') : null,
            ]);
        }

        // Sự cố an ninh.
        $incidents = [
            ['theft', 'high', 'Mất xe máy tầng hầm B2', 'investigating'],
            ['trespass', 'medium', 'Người lạ vào khu kỹ thuật', 'resolved'],
            ['vandalism', 'low', 'Hư hỏng bảng tin sảnh', 'closed'],
        ];
        foreach ($incidents as $i => [$type, $sev, $title, $status]) {
            \App\Models\SecurityIncident::create([
                'tenant_id' => $tenant->id, 'project_id' => $project->id, 'building_id' => $building->id,
                'code' => 'SI-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT), 'type' => $type, 'severity' => $sev,
                'title' => $title, 'description' => $title.'.', 'location' => 'Tòa A', 'status' => $status,
                'reported_by_id' => $guard->id, 'occurred_at' => Carbon::parse('2026-06-28 20:00')->addDays($i),
                'resolved_at' => in_array($status, ['resolved', 'closed'], true) ? Carbon::parse('2026-06-29 10:00')->addDays($i) : null,
            ]);
        }

        // SOS.
        $sos = [['app', 'resolved'], ['panic_button', 'responding'], ['intercom', 'false_alarm']];
        foreach ($sos as $i => [$src, $status]) {
            \App\Models\SosAlert::create([
                'tenant_id' => $tenant->id, 'project_id' => $project->id, 'building_id' => $building->id,
                'apartment_id' => $apts[$i % max(1, $apts->count())]->id ?? null,
                'resident_id' => $residents[$i % max(1, $residents->count())]->id ?? null,
                'source' => $src, 'status' => $status, 'location' => 'Căn hộ',
                'triggered_at' => Carbon::parse('2026-07-01 21:00')->addMinutes($i * 20),
                'acknowledged_by_id' => $status !== 'triggered' ? $guard->id : null,
                'resolved_at' => in_array($status, ['resolved', 'false_alarm'], true) ? Carbon::parse('2026-07-01 21:30')->addMinutes($i * 20) : null,
            ]);
        }

        // Thiết bị & camera.
        foreach ([['ACD-01', 'Đầu đọc thẻ sảnh', 'card_reader'], ['ACD-02', 'Barrier hầm xe', 'barrier'], ['ACD-03', 'Cửa từ tầng KT', 'door'], ['ACD-04', 'Face gate thang máy', 'face']] as $i => [$code, $name, $type]) {
            \App\Models\AccessDevice::create([
                'tenant_id' => $tenant->id, 'project_id' => $project->id, 'building_id' => $building->id,
                'code' => $code, 'name' => $name, 'type' => $type, 'location' => 'Tòa A',
                'ip_address' => '10.0.1.'.(10 + $i), 'status' => $i === 3 ? 'maintenance' : 'online',
                'last_sync_at' => Carbon::parse('2026-07-01 12:00'),
            ]);
        }
        for ($i = 1; $i <= 5; $i++) {
            \App\Models\Camera::create([
                'tenant_id' => $tenant->id, 'project_id' => $project->id, 'building_id' => $building->id,
                'code' => 'CAM-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT), 'name' => 'Camera '.$i,
                'location' => ['Sảnh', 'Hầm B1', 'Hầm B2', 'Thang máy', 'Mái'][$i - 1], 'type' => ['dome', 'bullet', 'ptz'][$i % 3],
                'stream_url' => 'rtsp://10.0.2.'.$i.'/stream', 'status' => $i === 5 ? 'offline' : 'online',
                'last_seen_at' => Carbon::parse('2026-07-01 12:00'),
            ]);
        }

        // Hành động trên cảnh báo IOC có sẵn.
        $alerts = \App\Models\IocAlert::where('tenant_id', $tenant->id)->orderBy('id')->take(4)->get();
        foreach ($alerts as $i => $al) {
            \App\Models\AlertAction::create([
                'ioc_alert_id' => $al->id, 'action' => 'acknowledge', 'user_id' => $guard->id,
                'note' => 'Đã ghi nhận', 'acted_at' => Carbon::parse('2026-07-01 08:00')->addMinutes($i * 10),
            ]);
        }
    }

    /** Tier 3 / A3 — phê duyệt + tài chính vận hành (quỹ, đề nghị chi, phiếu thu/chi). */
    private function seedTier3Finance(Tenant $tenant, Project $project, User $admin): void
    {
        // Quỹ.
        $funds = [];
        foreach ([['QUY-VH', 'Quỹ vận hành', 'operating', 800_000_000], ['QUY-BT', 'Quỹ bảo trì', 'maintenance', 2_500_000_000]] as [$code, $name, $type, $open]) {
            $funds[] = \App\Models\Fund::create([
                'tenant_id' => $tenant->id, 'project_id' => $project->id, 'code' => $code, 'name' => $name,
                'type' => $type, 'opening_balance' => $open, 'current_balance' => $open, 'status' => 'active',
            ]);
        }
        $opFund = $funds[0];

        // Đề nghị chi + phiếu chi + giao dịch quỹ.
        $prDefs = [
            ['Thanh toán điện tháng 6', 'EVN HCMC', 42_000_000, 'utility', 'paid'],
            ['Bảo trì thang máy Q3', 'Cty Thang máy Thiên Nam', 18_000_000, 'maintenance', 'approved'],
            ['Mua vật tư vệ sinh', 'NCC Sạch Xanh', 6_500_000, 'supply', 'pending'],
            ['Lương bảo vệ tháng 6', 'Đội bảo vệ', 96_000_000, 'salary', 'draft'],
        ];
        $balance = (float) $opFund->current_balance;
        foreach ($prDefs as $i => [$title, $payee, $amount, $cat, $status]) {
            $pr = \App\Models\PaymentRequest::create([
                'tenant_id' => $tenant->id, 'project_id' => $project->id, 'fund_id' => $opFund->id,
                'code' => 'PR-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT), 'title' => $title, 'payee' => $payee,
                'amount' => $amount, 'category' => $cat, 'status' => $status,
                'due_date' => Carbon::parse('2026-07-10')->addDays($i), 'requested_by_id' => $admin->id,
                'paid_at' => $status === 'paid' ? Carbon::parse('2026-07-02 10:00') : null,
            ]);
            if ($status === 'paid') {
                $balance -= $amount;
                $cv = \App\Models\CashVoucher::create([
                    'tenant_id' => $tenant->id, 'project_id' => $project->id, 'fund_id' => $opFund->id,
                    'payment_request_id' => $pr->id, 'code' => 'PC-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                    'type' => 'payment', 'amount' => $amount, 'party' => $payee, 'description' => $title,
                    'voucher_date' => Carbon::parse('2026-07-02'), 'status' => 'posted', 'created_by_id' => $admin->id,
                ]);
                \App\Models\FundTransaction::create([
                    'fund_id' => $opFund->id, 'cash_voucher_id' => $cv->id, 'type' => 'out', 'amount' => $amount,
                    'balance_after' => $balance, 'description' => $title, 'transaction_date' => Carbon::parse('2026-07-02'),
                    'created_by_id' => $admin->id,
                ]);
            }
        }
        // Một phiếu thu (nộp phí tiền mặt).
        $balance += 15_000_000;
        $rcv = \App\Models\CashVoucher::create([
            'tenant_id' => $tenant->id, 'project_id' => $project->id, 'fund_id' => $opFund->id,
            'code' => 'PT-0001', 'type' => 'receipt', 'amount' => 15_000_000, 'party' => 'Cư dân nộp phí',
            'description' => 'Thu phí quản lý tiền mặt', 'voucher_date' => Carbon::parse('2026-07-01'),
            'status' => 'posted', 'created_by_id' => $admin->id,
        ]);
        \App\Models\FundTransaction::create([
            'fund_id' => $opFund->id, 'cash_voucher_id' => $rcv->id, 'type' => 'in', 'amount' => 15_000_000,
            'balance_after' => $balance, 'description' => 'Thu phí tiền mặt', 'transaction_date' => Carbon::parse('2026-07-01'),
            'created_by_id' => $admin->id,
        ]);
        $opFund->update(['current_balance' => $balance]);

        // Yêu cầu phê duyệt + các bước.
        $arDefs = [
            ['Duyệt chi bảo trì thang máy', 'expense', 18_000_000, 'pending', 2],
            ['Duyệt mua vật tư vệ sinh', 'purchase', 6_500_000, 'approved', 3],
            ['Duyệt bảng kê phí Q3', 'statement', 248_650_000, 'pending', 1],
        ];
        foreach ($arDefs as $i => [$title, $type, $amount, $status, $curStep]) {
            $ar = \App\Models\ApprovalRequest::create([
                'tenant_id' => $tenant->id, 'project_id' => $project->id,
                'code' => 'AR-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT), 'type' => $type, 'title' => $title,
                'amount' => $amount, 'status' => $status, 'current_step' => $curStep, 'requested_by_id' => $admin->id,
                'decided_at' => $status === 'approved' ? Carbon::parse('2026-07-02 15:00') : null,
            ]);
            foreach (['Kế toán trưởng', 'Trưởng BQL', 'Giám đốc'] as $s => $role) {
                \App\Models\ApprovalStep::create([
                    'approval_request_id' => $ar->id, 'step_no' => $s + 1, 'approver_id' => $admin->id, 'role' => $role,
                    'status' => ($s + 1) < $curStep || $status === 'approved' ? 'approved' : 'pending',
                    'decided_at' => (($s + 1) < $curStep || $status === 'approved') ? Carbon::parse('2026-07-02 14:00')->addMinutes($s * 30) : null,
                ]);
            }
        }
    }

    /** Tier 3 / A2 — work order con + SLA config + ca trực. */
    private function seedTier3Ops(Tenant $tenant, Project $project, Building $building, User $admin): void
    {
        $staff = User::where('tenant_id', $tenant->id)->where('account_type', 'staff')->orderBy('id')->get();
        $handler = $staff->first() ?? $admin;
        $team = \App\Models\Team::where('tenant_id', $tenant->id)->first();

        // Làm giàu work orders có sẵn + thêm con.
        $wos = WorkOrder::where('tenant_id', $tenant->id)->orderBy('id')->take(8)->get();
        foreach ($wos as $i => $wo) {
            $done = $i % 3 === 0;
            $assignee = $staff[$i % max(1, $staff->count())] ?? $handler;
            $wo->update([
                'project_id' => $project->id, 'assigned_to_id' => $assignee->id, 'team_id' => $team?->id,
                'created_by_id' => $admin->id,
                'description' => 'Nội dung công việc: '.$wo->title,
                'category' => ['electrical', 'plumbing', 'cleaning', 'security'][$i % 4],
                'scheduled_at' => Carbon::parse('2026-07-01 08:00')->addHours($i),
                'started_at' => $done ? Carbon::parse('2026-07-01 09:00')->addHours($i) : null,
                'completed_at' => $done ? Carbon::parse('2026-07-01 11:00')->addHours($i) : null,
                'cost' => $done ? 150000 * ($i + 1) : 0,
            ]);
            \App\Models\WorkOrderAssignment::create([
                'work_order_id' => $wo->id, 'assigned_to_id' => $assignee->id, 'assigned_by_id' => $admin->id,
                'team_id' => $team?->id, 'role' => 'primary', 'status' => $done ? 'done' : 'assigned',
                'assigned_at' => Carbon::parse('2026-07-01 08:30')->addHours($i),
            ]);
            $cl = \App\Models\WorkOrderChecklist::create(['work_order_id' => $wo->id, 'name' => 'Quy trình xử lý']);
            foreach (['Kiểm tra hiện trạng', 'Thực hiện sửa chữa', 'Vệ sinh & bàn giao'] as $s => $label) {
                \App\Models\WorkOrderChecklistItem::create([
                    'work_order_checklist_id' => $cl->id, 'work_order_id' => $wo->id, 'label' => $label,
                    'is_done' => $done, 'done_by_id' => $done ? $assignee->id : null,
                    'done_at' => $done ? Carbon::parse('2026-07-01 10:00')->addHours($i) : null, 'sort' => $s,
                ]);
            }
            if ($done) {
                \App\Models\WorkOrderAttachment::create([
                    'work_order_id' => $wo->id, 'path' => 'work-orders/wo-'.$wo->id.'-after.jpg',
                    'name' => 'after.jpg', 'mime' => 'image/jpeg', 'size' => 320000, 'type' => 'after',
                    'uploaded_by_id' => $assignee->id,
                ]);
                \App\Models\WorkOrderSignature::create([
                    'work_order_id' => $wo->id, 'signer_name' => $assignee->name, 'signer_role' => 'technician',
                    'signature_path' => 'signatures/wo-'.$wo->id.'.png', 'signed_at' => $wo->completed_at,
                ]);
            }
        }

        // SLA policies.
        $slas = [
            ['SLA phản ánh khẩn', 'feedback_request', 'urgent', 15, 240],
            ['SLA phản ánh thường', 'feedback_request', 'normal', 60, 1440],
            ['SLA công việc khẩn', 'work_order', 'urgent', 30, 480],
            ['SLA công việc thường', 'work_order', 'normal', 120, 2880],
        ];
        foreach ($slas as [$name, $applies, $priority, $resp, $resolve]) {
            \App\Models\SlaPolicy::create([
                'tenant_id' => $tenant->id, 'project_id' => $project->id, 'name' => $name,
                'applies_to' => $applies, 'priority' => $priority, 'response_minutes' => $resp,
                'resolve_minutes' => $resolve, 'business_hours_only' => $priority === 'normal', 'status' => 'active',
            ]);
        }

        // Ca trực + phân ca 3 ngày.
        $dept = Department::where('tenant_id', $tenant->id)->where('code', 'AN')->first();
        $shiftDefs = [['Ca sáng', '06:00', '14:00'], ['Ca chiều', '14:00', '22:00'], ['Ca đêm', '22:00', '06:00']];
        foreach ($shiftDefs as $si => [$name, $start, $end]) {
            $shift = \App\Models\Shift::create([
                'tenant_id' => $tenant->id, 'project_id' => $project->id, 'building_id' => $building->id,
                'department_id' => $dept?->id, 'name' => $name, 'start_time' => $start, 'end_time' => $end, 'status' => 'active',
            ]);
            for ($d = 0; $d < 3; $d++) {
                $u = $staff[($si + $d) % max(1, $staff->count())] ?? $handler;
                \App\Models\DutyRoster::create([
                    'shift_id' => $shift->id, 'user_id' => $u->id,
                    'duty_date' => Carbon::parse('2026-07-01')->addDays($d),
                    'status' => $d === 0 ? 'present' : 'scheduled',
                ]);
            }
        }
    }

    /** Tier 2 vá nốt — emergency_alerts, qr_payment_tokens, service_evaluations, access_logs, intercom_events. */
    private function seedTier2Patch(Tenant $tenant, Project $project, Building $building, User $admin): void
    {
        $apts = Apartment::where('building_id', $building->id)->orderBy('id')->take(10)->get();
        $residents = \App\Models\Resident::where('tenant_id', $tenant->id)->orderBy('id')->take(10)->get();

        \App\Models\EmergencyAlert::create([
            'tenant_id' => $tenant->id, 'project_id' => $project->id, 'building_id' => $building->id,
            'code' => 'EMG-001', 'type' => 'fire', 'title' => 'Cảnh báo cháy tầng hầm B1',
            'message' => 'Phát hiện khói tầng hầm B1. Vui lòng di chuyển theo lối thoát hiểm.',
            'severity' => 'critical', 'status' => 'resolved',
            'starts_at' => Carbon::parse('2026-06-20 14:00'), 'ends_at' => Carbon::parse('2026-06-20 14:40'),
            'resolved_at' => Carbon::parse('2026-06-20 14:40'), 'created_by_id' => $admin->id,
        ]);
        \App\Models\EmergencyAlert::create([
            'tenant_id' => $tenant->id, 'project_id' => $project->id, 'building_id' => $building->id,
            'code' => 'EMG-002', 'type' => 'health', 'title' => 'Phun khử khuẩn định kỳ',
            'message' => 'Khu vực sảnh sẽ phun khử khuẩn 20:00 hôm nay.',
            'severity' => 'info', 'status' => 'active', 'starts_at' => Carbon::parse('2026-07-01 20:00'),
            'created_by_id' => $admin->id,
        ]);

        // QR thanh toán cho vài bảng kê.
        $statements = \App\Models\Statement::where('tenant_id', $tenant->id)->orderBy('id')->take(5)->get();
        foreach ($statements as $i => $st) {
            \App\Models\QrPaymentToken::create([
                'tenant_id' => $tenant->id, 'statement_id' => $st->id,
                'code' => 'QRP-'.strtoupper(substr(md5('qrp'.$st->id), 0, 12)),
                'amount' => $st->total_amount ?? $st->total ?? 500000, 'provider' => ['vietqr', 'momo', 'vnpay'][$i % 3],
                'status' => $i === 0 ? 'used' : 'active',
                'expires_at' => Carbon::parse('2026-07-15 23:59'), 'paid_at' => $i === 0 ? Carbon::parse('2026-07-02 10:00') : null,
            ]);
        }

        // Đánh giá dịch vụ cho vài phản ánh đã đóng.
        $resolved = FeedbackRequest::where('tenant_id', $tenant->id)
            ->whereIn('status', ['resolved', 'closed'])->orderBy('id')->take(5)->get();
        foreach ($resolved as $i => $req) {
            \App\Models\ServiceEvaluation::create([
                'tenant_id' => $tenant->id, 'feedback_request_id' => $req->id,
                'resident_id' => $residents[$i % max(1, $residents->count())]->id ?? null,
                'rating' => 5 - ($i % 3), 'criteria' => ['thoi_gian' => 5 - ($i % 2), 'thai_do' => 5, 'ket_qua' => 4],
                'comment' => 'Xử lý nhanh, cảm ơn BQL.', 'evaluated_at' => Carbon::parse('2026-06-25')->addDays($i),
            ]);
        }

        // Nhật ký ra/vào + intercom.
        for ($i = 0; $i < 12; $i++) {
            $apt = $apts[$i % max(1, $apts->count())] ?? null;
            $res = $residents[$i % max(1, $residents->count())] ?? null;
            \App\Models\AccessLog::create([
                'tenant_id' => $tenant->id, 'project_id' => $project->id, 'building_id' => $building->id,
                'apartment_id' => $apt?->id, 'resident_id' => $res?->id,
                'device_name' => 'Cổng chính', 'gate' => 'GATE-01',
                'direction' => $i % 2 === 0 ? 'in' : 'out', 'method' => ['card', 'qr', 'face'][$i % 3],
                'status' => 'granted', 'event_at' => Carbon::parse('2026-07-01 07:00')->addMinutes($i * 37),
            ]);
        }
        for ($i = 0; $i < 5; $i++) {
            $apt = $apts[$i % max(1, $apts->count())] ?? null;
            $res = $residents[$i % max(1, $residents->count())] ?? null;
            \App\Models\IntercomEvent::create([
                'tenant_id' => $tenant->id, 'building_id' => $building->id,
                'apartment_id' => $apt?->id, 'resident_id' => $res?->id,
                'from_device' => 'lobby_gate', 'direction' => 'incoming',
                'status' => ['answered', 'missed', 'rejected'][$i % 3], 'duration_seconds' => $i * 15,
                'event_at' => Carbon::parse('2026-07-01 09:00')->addHours($i),
            ]);
        }
    }

    /**
     * WEB-UX-09 — "AI Engine" demo data backing all four screens: usage/audit logs
     * (09-01/09-02), policies + prompt templates (09-02), workflows + runs (09-03)
     * and the knowledge base (09-04). All tenant-scoped, nothing hardcoded in the UI.
     */
    private function seedAiEngine(Tenant $tenant, Project $project, Building $building, User $admin): void
    {
        $staff = User::where('tenant_id', $tenant->id)->where('account_type', 'staff')->pluck('id')->all();
        $actorIds = array_values(array_filter(array_merge([$admin->id], $staff)));
        $actorNames = User::whereIn('id', $actorIds)->pluck('name', 'id')->all();

        // --- ai_usage_logs (09-01 usage + 09-02 audit) -------------------------
        $surfaces = [
            'finance/statement-approvals' => ['summarize', 'analyze'],
            'residents/create' => ['draft', 'lookup'],
            'operational-dashboard' => ['analyze', 'chat'],
            'feedback' => ['draft', 'summarize'],
            'work-orders' => ['draft', 'analyze'],
            'knowledge-base' => ['lookup', 'chat'],
        ];
        $models = ['claude-haiku-4-5', 'claude-haiku-4-5', 'claude-sonnet-4-6'];
        $surfaceKeys = array_keys($surfaces);

        for ($i = 0; $i < 90; $i++) {
            $surface = $surfaceKeys[$i % count($surfaceKeys)];
            $action = $surfaces[$surface][$i % count($surfaces[$surface])];
            $uid = $actorIds[$i % count($actorIds)];
            // Mostly success; a few failures / high-risk awaiting approval.
            $roll = $i % 12;
            $risk = $roll === 0 ? 'high' : ($roll < 3 ? 'medium' : 'low');
            $status = $roll === 5 ? 'failed' : ($risk === 'high' && $roll === 0 ? 'pending_approval' : 'success');
            $createdAt = Carbon::parse('2026-06-30 18:00')->subHours($i * 7 + ($i % 5));

            \App\Models\AiUsageLog::create([
                'tenant_id' => $tenant->id,
                'project_id' => $project->id,
                'building_id' => $building->id,
                'user_id' => $uid,
                'actor_name' => $actorNames[$uid] ?? 'Hệ thống',
                'surface' => $surface,
                'mode' => $action === 'lookup' ? 'lookup' : ($action === 'chat' ? 'context' : 'context'),
                'model' => $models[$i % count($models)],
                'action' => $action,
                'risk_level' => $risk,
                'status' => $status,
                'requires_approval' => $risk === 'high',
                'approver_id' => $status === 'pending_approval' ? null : ($risk === 'high' ? $admin->id : null),
                'prompt_excerpt' => match ($action) {
                    'summarize' => 'Tóm tắt bảng kê phí kỳ 06/2026 tòa A',
                    'draft' => 'Soạn thông báo nhắc phí gửi cư dân',
                    'analyze' => 'Phân tích bất thường tiêu thụ nước tháng này',
                    'lookup' => 'Tra cứu công nợ căn hộ A-1203',
                    default => 'Trợ giúp thao tác trên màn hình hiện tại',
                },
                'tokens_in' => 400 + ($i * 13 % 1800),
                'tokens_out' => 120 + ($i * 7 % 900),
                'latency_ms' => 600 + ($i * 37 % 3200),
                'cost' => round((400 + ($i * 13 % 1800)) * 0.000003 + (120 + ($i * 7 % 900)) * 0.000015, 4) * 24000,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        // --- ai_policies (09-02 · Chính sách AI) -------------------------------
        $policies = [
            ['Ẩn dữ liệu cá nhân (PII)', 'Tự động che CCCD, SĐT khi đưa vào prompt.', 'data', 'high', 'active'],
            ['Giới hạn truy cập tài chính', 'AI chỉ đọc số liệu trong workspace được cấp.', 'access', 'high', 'active'],
            ['Chặn xuất dữ liệu cư dân', 'Không cho AI sinh file chứa danh sách cư dân thô.', 'data', 'high', 'active'],
            ['Hành động rủi ro cao cần người duyệt', 'Mọi action ghi dữ liệu phải có human approval.', 'risk', 'high', 'active'],
            ['Lưu nhật ký toàn bộ tương tác', 'Ghi audit mọi prompt/response phục vụ kiểm toán.', 'risk', 'medium', 'active'],
            ['Kiểm duyệt nội dung gửi cư dân', 'Lọc nội dung không phù hợp trước khi phát hành.', 'content', 'medium', 'active'],
            ['Giới hạn mô hình theo chi phí', 'Ưu tiên Haiku; chỉ dùng Sonnet cho tác vụ phức tạp.', 'access', 'low', 'inactive'],
        ];
        foreach ($policies as [$name, $desc, $cat, $risk, $st]) {
            \App\Models\AiPolicy::create([
                'tenant_id' => $tenant->id,
                'name' => $name, 'description' => $desc,
                'category' => $cat, 'risk_level' => $risk, 'status' => $st,
            ]);
        }

        // --- ai_prompt_templates (09-02 · Prompt & phân loại + 09-01 Gợi ý) ----
        $prompts = [
            ['Tóm tắt bảng kê phí', 'finance', 'Tài chính', 'finance/statement-approvals', 342],
            ['Soạn thông báo cư dân', 'communication', 'Truyền thông', 'feedback', 287],
            ['Phân tích công nợ theo căn', 'finance', 'Tài chính', 'operational-dashboard', 198],
            ['Trả lời CSKH tự động', 'support', 'CSKH', 'feedback', 421],
            ['Phân loại phản ánh cư dân', 'classification', 'Vận hành', 'feedback', 256],
            ['Tạo lệnh làm việc từ phản ánh', 'operations', 'Vận hành', 'work-orders', 173],
            ['Giải thích hóa đơn cho cư dân', 'support', 'CSKH', 'finance/statement-approvals', 134],
            ['Tóm tắt biên bản cuộc họp', 'general', 'Văn phòng', null, 96],
        ];
        foreach ($prompts as [$name, $cat, $cls, $surface, $uses]) {
            \App\Models\AiPromptTemplate::create([
                'tenant_id' => $tenant->id,
                'name' => $name, 'category' => $cat, 'classification' => $cls,
                'surface' => $surface, 'usage_count' => $uses, 'status' => 'active',
                'body' => 'Mẫu prompt chuẩn cho tác vụ "'.$name.'".',
            ]);
        }

        // --- ai_workflows + runs (09-03) ---------------------------------------
        $workflows = [
            ['Tự động nhắc nợ quá hạn', 'Gửi nhắc phí khi công nợ quá 15 ngày.', 'schedule', 'Hằng ngày 08:00', 'active', 312, 305],
            ['Phân loại phản ánh → giao việc', 'Phân loại phản ánh và tạo lệnh làm việc.', 'event', 'Khi có phản ánh mới', 'active', 489, 471],
            ['Tóm tắt bảng kê hàng kỳ', 'Tóm tắt & gắn ghi chú vào bảng kê chờ duyệt.', 'event', 'Khi tạo bảng kê', 'active', 64, 64],
            ['Cảnh báo bất thường tiêu thụ', 'Phát hiện tiêu thụ điện/nước bất thường.', 'schedule', 'Hằng tuần T2', 'active', 52, 49],
            ['Chào mừng cư dân mới', 'Gửi hướng dẫn khi cư dân được duyệt.', 'event', 'Khi duyệt cư dân', 'paused', 128, 126],
            ['Nhắc gia hạn thẻ xe', 'Nhắc cư dân trước khi thẻ xe hết hạn.', 'schedule', 'Hằng ngày 09:00', 'draft', 0, 0],
        ];
        foreach ($workflows as $w) {
            [$name, $desc, $trigger, $schedule, $status, $runs, $ok] = $w;
            $wf = \App\Models\AiWorkflow::create([
                'tenant_id' => $tenant->id,
                'project_id' => $project->id,
                'name' => $name, 'description' => $desc,
                'trigger_type' => $trigger, 'schedule' => $schedule, 'status' => $status,
                'steps' => [
                    ['type' => 'trigger', 'label' => $schedule],
                    ['type' => 'ai', 'label' => 'X2AI xử lý nội dung'],
                    ['type' => 'condition', 'label' => 'Kiểm tra điều kiện'],
                    ['type' => 'action', 'label' => 'Gửi thông báo / tạo việc'],
                ],
                'runs_count' => $runs, 'success_count' => $ok,
                'last_run_at' => $runs > 0 ? Carbon::parse('2026-06-30 08:00')->subHours(array_search($w, $workflows, true)) : null,
                'created_by_id' => $admin->id,
            ]);

            for ($r = 0; $r < min(6, $runs); $r++) {
                $failed = $r === 2 && $ok < $runs;
                $start = Carbon::parse('2026-06-30 08:00')->subDays($r);
                \App\Models\AiWorkflowRun::create([
                    'ai_workflow_id' => $wf->id,
                    'status' => $failed ? 'failed' : 'success',
                    'trigger_source' => $trigger,
                    'duration_ms' => 800 + ($r * 350),
                    'note' => $failed ? 'Lỗi gọi API, đã thử lại' : 'Hoàn tất',
                    'started_at' => $start,
                    'finished_at' => $start->copy()->addSeconds(2 + $r),
                ]);
            }
        }

        // --- knowledge base (09-04) --------------------------------------------
        $categories = [
            ['Hướng dẫn cư dân', 'heroicon-o-user-group', '#2563eb'],
            ['Quy trình BQL', 'heroicon-o-clipboard-document-list', '#0ea5e9'],
            ['Tài chính & Phí', 'heroicon-o-banknotes', '#16a34a'],
            ['Kỹ thuật & Bảo trì', 'heroicon-o-wrench-screwdriver', '#f59e0b'],
            ['An ninh & An toàn', 'heroicon-o-shield-check', '#dc2626'],
            ['Chính sách & Pháp lý', 'heroicon-o-scale', '#7c3aed'],
        ];
        $articlesByCat = [
            'Hướng dẫn cư dân' => ['Cách đăng ký thẻ xe', 'Đăng ký tạm trú trên app', 'Đặt tiện ích nội khu', 'Báo sự cố căn hộ'],
            'Quy trình BQL' => ['Quy trình tiếp nhận phản ánh', 'Quy trình bàn giao căn hộ', 'Lịch trực vận hành'],
            'Tài chính & Phí' => ['Cách đọc bảng kê phí', 'Các kênh thanh toán phí', 'Chính sách phí quản lý 2026'],
            'Kỹ thuật & Bảo trì' => ['Lịch bảo trì thang máy', 'Hướng dẫn xử lý mất nước', 'Quy trình PCCC'],
            'An ninh & An toàn' => ['Quy định ra vào tòa nhà', 'Hướng dẫn thoát hiểm'],
            'Chính sách & Pháp lý' => ['Nội quy chung cư', 'Chính sách bảo vệ dữ liệu cá nhân'],
        ];
        $idx = 0;
        foreach ($categories as [$name, $icon, $color]) {
            $cat = \App\Models\KnowledgeCategory::create([
                'tenant_id' => $tenant->id,
                'name' => $name, 'slug' => \Illuminate\Support\Str::slug($name),
                'icon' => $icon, 'color' => $color,
                'articles_count' => count($articlesByCat[$name] ?? []),
            ]);
            foreach ($articlesByCat[$name] ?? [] as $title) {
                $idx++;
                $status = $idx % 9 === 0 ? 'draft' : 'published';
                // Đa số là tài liệu DỰ ÁN (Sunshine); mỗi bài thứ 5 là tài liệu CÔNG TY
                // chia sẻ xuống mọi dự án (để BQL nhìn thấy tài liệu công ty).
                $isCompany = $idx % 5 === 0;
                $body = '<p>Nội dung hướng dẫn cho "'.$title.'". Áp dụng cho cư dân và ban quản lý dự án.</p>';
                \App\Models\KnowledgeArticle::create([
                    'tenant_id' => $tenant->id,
                    'owner_level' => $isCompany ? 'tenant' : 'project',
                    'project_id' => $isCompany ? null : $project->id,
                    'share_mode' => $isCompany ? 'descendants' : 'private',
                    'knowledge_category_id' => $cat->id,
                    'title' => $title, 'slug' => \Illuminate\Support\Str::slug($title),
                    'excerpt' => $title.' — hướng dẫn chi tiết cho cư dân và BQL.',
                    'body' => $body,
                    'content_text' => app(\App\Support\Knowledge\DocumentTextExtractor::class)->htmlToText($body),
                    'status' => $status,
                    'views' => 120 + ($idx * 137 % 4200),
                    'helpful_count' => 30 + ($idx * 17 % 280),
                    'not_helpful_count' => $idx * 3 % 24,
                    'author_id' => $admin->id,
                    'published_at' => $status === 'published' ? Carbon::parse('2026-05-01')->addDays($idx * 3) : null,
                ]);
            }
        }

        // --- Tài liệu PLATFORM (superadmin) — dùng chung, chia sẻ xuống ---------
        $platformDocs = [
            ['Chính sách bảo vệ dữ liệu cá nhân (toàn hệ thống)', 'Nguyên tắc xử lý dữ liệu cư dân áp dụng cho mọi công ty & dự án trên X2-BMS.', 'descendants'],
            ['Hướng dẫn sử dụng X2-BMS cho BQL', 'Cẩm nang thao tác nghiệp vụ trên hệ thống dành cho ban quản lý.', 'descendants'],
            ['Quy chuẩn vận hành mẫu (chỉ chia sẻ có chọn lọc)', 'Bộ quy chuẩn vận hành mẫu, chia sẻ theo từng công ty được duyệt.', 'custom'],
        ];
        foreach ($platformDocs as $i => [$title, $excerpt, $shareMode]) {
            $body = '<p>'.$excerpt.'</p><p>Tài liệu do đội ngũ nền tảng X2-BMS biên soạn.</p>';
            $doc = \App\Models\KnowledgeArticle::create([
                'tenant_id' => null,
                'owner_level' => 'platform',
                'project_id' => null,
                'share_mode' => $shareMode,
                'knowledge_category_id' => null,
                'title' => $title, 'slug' => \Illuminate\Support\Str::slug($title),
                'excerpt' => $excerpt,
                'body' => $body,
                'content_text' => app(\App\Support\Knowledge\DocumentTextExtractor::class)->htmlToText($body),
                'status' => 'published',
                'views' => 800 + $i * 250,
                'helpful_count' => 120 + $i * 30,
                'not_helpful_count' => $i * 4,
                'author_id' => $admin->id,
                'published_at' => Carbon::parse('2026-04-15')->addDays($i * 5),
            ]);
            // Bản "custom" chỉ chia sẻ cho tenant demo (công ty 1).
            if ($shareMode === 'custom') {
                \App\Models\KnowledgeArticleShare::create([
                    'knowledge_article_id' => $doc->id, 'scope_type' => 'tenant', 'scope_id' => $tenant->id,
                ]);
            }
        }

        // --- Tài liệu DỰ ÁN KHÁC (cùng công ty) — minh hoạ cô lập giữa các BQL ----
        $otherProject = Project::where('tenant_id', $tenant->id)->where('id', '!=', $project->id)->first();
        if ($otherProject) {
            foreach (['Nội quy dự án '.$otherProject->name, 'Quy trình bàn giao '.$otherProject->name] as $j => $title) {
                $body = '<p>Tài liệu riêng của dự án '.$otherProject->name.', không chia sẻ ra ngoài.</p>';
                \App\Models\KnowledgeArticle::create([
                    'tenant_id' => $tenant->id, 'owner_level' => 'project', 'project_id' => $otherProject->id,
                    'share_mode' => 'private', 'knowledge_category_id' => null,
                    'title' => $title, 'slug' => \Illuminate\Support\Str::slug($title),
                    'excerpt' => $title.'.', 'body' => $body,
                    'content_text' => app(\App\Support\Knowledge\DocumentTextExtractor::class)->htmlToText($body),
                    'status' => 'published', 'views' => 200 + $j * 40, 'helpful_count' => 20, 'not_helpful_count' => 1,
                    'author_id' => $admin->id, 'published_at' => Carbon::parse('2026-05-10')->addDays($j),
                ]);
            }
        }
    }

    /**
     * WEB-FORM-07-04 — several billing-run batches with mixed approval states so
     * the "Duyệt bảng kê hàng loạt" queue is populated (Chờ duyệt / Đang rà soát /
     * Đã duyệt / Cần bổ sung / Bị từ chối).
     */
    private function seedApprovalQueueRuns(Tenant $tenant, Project $project, User $admin): void
    {
        $buildings = Building::where('project_id', $project->id)->get();
        if ($buildings->isEmpty()) {
            return;
        }
        $period = BillingPeriod::where('tenant_id', $tenant->id)->where('is_current', true)->first();
        $creator = User::where('tenant_id', $tenant->id)->where('account_type', 'staff')
            ->where('is_platform_admin', false)->first() ?? $admin;

        // [status, approver?, số căn, tổng tiền, SLA (ngày từ 30/06, null = không)]
        $plan = [
            ['pending', null, 282, 248_650_000, 1],
            ['pending', null, 256, 232_180_000, 1],
            ['reviewing', $admin->id, 310, 286_450_000, 2],
            ['approved', $admin->id, 124, 113_900_000, null],
            ['need_more', $admin->id, 86, 78_560_000, null],
            ['rejected', $admin->id, 1, 15_800_000, null],
        ];

        foreach ($plan as $i => [$status, $approverId, $count, $total, $slaDays]) {
            $b = $buildings[$i % $buildings->count()];
            \App\Models\BillingRun::create([
                'tenant_id' => $tenant->id,
                'building_id' => $b->id,
                'billing_period_id' => $period?->id,
                'code' => sprintf('BK-2607-Q%02d', $i + 1),
                'status' => 'completed',
                'approval_status' => $status,
                'total_billed' => $total,
                'statements_count' => $count,
                'apartment_count' => $count,
                'created_by_id' => $creator->id,
                'approver_id' => $approverId,
                'sla_due_at' => $slaDays ? Carbon::parse('2026-06-30')->addDays($slaDays) : null,
                'run_at' => Carbon::parse('2026-07-01 08:00'),
            ]);
        }
    }

    /**
     * WEB-FORM-06 — fee catalog: types + dated tariffs + a formula + a scope
     * assignment. Amounts mirror the demo (management 16.500đ/m², parking, etc.).
     */
    private function seedFeeCatalog(Tenant $tenant, Project $project, Building $building): void
    {
        // [code, name, category, unit, amount, note, applies_to, vat%, formula_text, is_complex]
        $types = [
            ['QL', 'Phí quản lý', 'management', 'per_sqm', 16_500, 'Đồng/m²/tháng', 'Căn hộ, Văn phòng', 10, 'Đơn giá (VND/m²) × Diện tích', false],
            ['OTO', 'Phí gửi ô tô', 'parking', 'per_vehicle', 1_200_000, 'Đồng/xe/tháng', 'Cư dân, Khách thuê', 10, 'Theo bậc số lượng xe', true],
            ['XEMAY', 'Phí gửi xe máy', 'parking', 'per_vehicle', 120_000, 'Đồng/xe/tháng', 'Cư dân, Khách thuê', 10, 'Theo bậc số lượng xe', true],
            ['NUOC', 'Phí nước sinh hoạt', 'utility', 'per_m3', 15_000, 'Đồng/m³', 'Căn hộ, Văn phòng', 5, '(Chỉ số mới − Chỉ số cũ) × Đơn giá', false],
            ['RAC', 'Phí vệ sinh', 'service', 'fixed', 50_000, 'Đồng/tháng', 'Căn hộ', 0, 'Đơn giá cố định', false],
        ];

        $managementType = null;
        $managementRate = null;
        foreach ($types as [$code, $name, $cat, $unit, $amount, $note, $appliesTo, $vat, $formulaText, $complex]) {
            $feeType = \App\Models\FeeType::create([
                'tenant_id' => $tenant->id,
                'code' => $code,
                'name' => $name,
                'category' => $cat,
                'unit' => $unit,
                'is_recurring' => true,
                'status' => 'active',
                'note' => $note,
                'applies_to' => $appliesTo,
                'frequency' => 'monthly',
                'vat_percent' => $vat,
                'formula_text' => $formulaText,
                'effective_from' => Carbon::parse('2026-01-01'),
                'is_complex' => $complex,
            ]);
            $rate = \App\Models\FeeRate::create([
                'tenant_id' => $tenant->id,
                'fee_type_id' => $feeType->id,
                'code' => $code.'-2026',
                'name' => $name.' (2026)',
                'amount' => $amount,
                'unit' => $unit,
                'effective_from' => Carbon::parse('2026-01-01'),
                'status' => 'active',
            ]);
            if ($code === 'QL') {
                $managementType = $feeType;
                $managementRate = $rate;
            }
        }

        // Management fee formula (+ first version).
        $formula = \App\Models\FeeFormula::create([
            'tenant_id' => $tenant->id,
            'fee_type_id' => $managementType->id,
            'code' => 'F-QL',
            'name' => 'Công thức phí quản lý',
            'expression' => 'area_sqm * rate',
            'variables' => ['area_sqm' => 'Diện tích căn (m²)', 'rate' => 'Đơn giá (đ/m²)'],
            'status' => 'active',
        ]);
        \App\Models\FeeFormulaVersion::create([
            'fee_formula_id' => $formula->id,
            'version' => 1,
            'expression' => 'area_sqm * rate',
            'effective_from' => Carbon::parse('2026-01-01'),
            'note' => 'Phiên bản đầu',
        ]);

        // Apply management fee to the whole project.
        \App\Models\FeeScopeAssignment::create([
            'tenant_id' => $tenant->id,
            'fee_type_id' => $managementType->id,
            'fee_rate_id' => $managementRate->id,
            'scope_type' => 'project',
            'project_id' => $project->id,
            'effective_from' => Carbon::parse('2026-01-01'),
        ]);

        $this->seedBql03CatalogExtra($tenant);
    }

    /**
     * BQL-03-01 — extra catalogue fee rules (display-only, not wired into billing)
     * so "Biểu phí & quy tắc tính phí" and its KPI counters reflect a realistic
     * catalogue: 28 active / 6 pending / 4 inactive, 9 complex formulas, 12 touched
     * this month. Codes use the BF- prefix; the 5 functional types keep QL/RAC/etc.
     */
    private function seedBql03CatalogExtra(Tenant $tenant): void
    {
        // [name, category, applies_to, vat, formula_text, frequency, complex]
        $active = [
            ['Phí quản lý theo gói', 'management', 'Văn phòng', 10, 'Theo gói diện tích', 'monthly', false],
            ['Phí quản lý khu TMDV', 'management', 'Khách thuê', 10, 'Đơn giá (VND/m²) × Diện tích', 'monthly', false],
            ['Phí gửi xe đạp', 'parking', 'Cư dân', 0, 'Đơn giá cố định', 'monthly', false],
            ['Phí gửi ô tô vãng lai', 'parking', 'Khách thuê', 10, 'Đơn giá × Số giờ', 'per_use', true],
            ['Phí gửi xe tầng hầm B2', 'parking', 'Cư dân', 10, 'Theo bậc số lượng xe', 'monthly', true],
            ['Tiền điện chỉ số', 'utility', 'Căn hộ, Văn phòng', 10, '(Chỉ số mới − Chỉ số cũ) × Đơn giá', 'monthly', false],
            ['Điện theo khung giờ', 'utility', 'Văn phòng', 10, 'Theo khung giờ × Đơn giá', 'monthly', true],
            ['Nước nóng trung tâm', 'utility', 'Căn hộ', 5, '(Chỉ số mới − Chỉ số cũ) × Đơn giá', 'monthly', false],
            ['Phí điều hòa trung tâm', 'utility', 'Văn phòng', 10, 'Theo công suất × Đơn giá', 'monthly', true],
            ['Quỹ bảo trì', 'reserve', 'Căn hộ', 0, '% Giá trị căn hộ × Tỷ lệ QBT', 'yearly', true],
            ['Quỹ dự phòng vận hành', 'reserve', 'Căn hộ', 0, 'Đơn giá cố định', 'yearly', false],
            ['Phí Internet nội bộ', 'service', 'Căn hộ', 10, 'Gói cơ bản', 'monthly', false],
            ['Phí truyền hình cáp', 'service', 'Căn hộ', 10, 'Gói cơ bản', 'monthly', false],
            ['Phí vệ sinh khu TMDV', 'service', 'Khách thuê', 10, 'Đơn giá theo diện tích', 'monthly', false],
            ['Phí diệt côn trùng', 'service', 'Căn hộ', 10, 'Đơn giá cố định', 'quarterly', false],
            ['Phí sử dụng bể bơi', 'service', 'Cư dân', 10, 'Đơn giá × Lượt', 'per_use', false],
            ['Phí sử dụng gym', 'service', 'Cư dân', 10, 'Đơn giá cố định', 'monthly', false],
            ['Phí thẻ ra vào bổ sung', 'surcharge', 'Cư dân, Khách thuê', 10, 'Đơn giá × Số thẻ', 'per_use', false],
            ['Phụ thu ngoài giờ', 'surcharge', 'Cư dân, Khách thuê', 10, 'Đơn giá/giờ × Số giờ', 'per_use', false],
            ['Phụ thu chuyển nhà', 'surcharge', 'Cư dân', 10, 'Đơn giá cố định', 'per_use', false],
            ['Phí đăng ký thi công', 'surcharge', 'Cư dân', 10, 'Đơn giá cố định', 'per_use', false],
            ['Phí giữ chỗ tiện ích', 'surcharge', 'Cư dân', 10, 'Đơn giá × Lượt', 'per_use', false],
            ['Phí quảng cáo thang máy', 'other', 'Khách thuê', 10, 'Theo hợp đồng', 'monthly', false],
        ];
        $pending = [
            ['Phí quản lý theo gói 2027', 'management', 'Văn phòng', 10, 'Theo gói diện tích', 'monthly', false],
            ['Phụ thu cuối tuần', 'surcharge', 'Cư dân, Khách thuê', 10, 'Đơn giá × Hệ số cuối tuần', 'per_use', true],
            ['Điện năng lượng mặt trời', 'utility', 'Căn hộ', 5, 'Theo sản lượng × Đơn giá', 'monthly', true],
            ['Phí sạc xe điện', 'utility', 'Cư dân', 10, 'Chỉ số × Đơn giá', 'monthly', false],
            ['Phí kho chứa đồ', 'service', 'Cư dân', 10, 'Đơn giá/m² × Diện tích', 'monthly', false],
            ['Gói dịch vụ cao cấp', 'service', 'Căn hộ', 10, 'Gói cao cấp', 'monthly', false],
        ];
        $inactive = [
            ['Phí điện theo khung giờ (cũ)', 'utility', 'Căn hộ, Văn phòng', 10, 'Theo khung giờ × Đơn giá', 'monthly', false],
            ['Phụ thu cuối tuần (cũ)', 'surcharge', 'Cư dân, Khách thuê', 10, 'Đơn giá × Hệ số cuối tuần', 'per_use', false],
            ['Phí giữ xe cũ 2025', 'parking', 'Cư dân', 10, 'Theo bậc số lượng xe', 'monthly', false],
            ['Gói Internet cũ 2025', 'service', 'Căn hộ', 10, 'Gói cơ bản', 'monthly', false],
        ];

        $prefix = ['management' => 'QL', 'parking' => 'GX', 'utility' => 'DN', 'reserve' => 'GBT', 'service' => 'DV', 'surcharge' => 'PT', 'other' => 'KH'];
        $counter = [];
        $recentIds = [];   // fee types "updated this month" (target 12: 5 functional + 7 here)
        $seq = 0;

        $make = function (array $def, string $status, ?string $effectiveFrom) use ($tenant, $prefix, &$counter, &$recentIds, &$seq) {
            [$name, $cat, $appliesTo, $vat, $formula, $freq, $complex] = $def;
            $counter[$cat] = ($counter[$cat] ?? 0) + 1;
            $ft = \App\Models\FeeType::create([
                'tenant_id' => $tenant->id,
                'code' => 'BF-'.$prefix[$cat].'-'.str_pad((string) $counter[$cat], 2, '0', STR_PAD_LEFT),
                'name' => $name,
                'category' => $cat,
                'unit' => 'per_unit',
                'is_recurring' => $freq === 'monthly',
                'status' => $status,
                'applies_to' => $appliesTo,
                'frequency' => $freq,
                'vat_percent' => $vat,
                'formula_text' => $formula,
                'effective_from' => $effectiveFrom ? Carbon::parse($effectiveFrom) : null,
                'is_complex' => $complex,
            ]);
            if ($seq < 7) {
                $recentIds[] = $ft->id;   // keep updated_at = now (this month)
            }
            $seq++;

            return $ft;
        };

        foreach ($active as $def) {
            $make($def, 'active', '2026-01-01');
        }
        foreach ($pending as $def) {
            $make($def, 'pending', '2026-08-01');
        }
        foreach ($inactive as $def) {
            $make($def, 'inactive', null);
        }

        // Backdate "updated_at" for every extra row except the first 7 so the
        // "Cập nhật tháng này" KPI lands at 12 (5 functional + 7 recent extras).
        \App\Models\FeeType::where('tenant_id', $tenant->id)
            ->where('code', 'like', 'BF-%')
            ->whereNotIn('id', $recentIds)
            ->update(['updated_at' => Carbon::parse('2026-05-15 09:00')]);
    }

    /**
     * BQL-03-02..09 backbone — scale Tòa A to 1,248 real apartments (+residents),
     * then generate the current-period statement book and the debt/aging ledger so
     * every finance screen's headline matches the handoff images with 100% real
     * rows (no snapshots): 1.086 published / 124 pending / 732 viewed / 148 overdue,
     * total receivable 8,42 tỷ; 24 debtor ledgers, aging 1,02 tỷ / 650tr / 320tr /
     * 210tr. Uses bulk inserts for speed.
     */
    private function seedBql03Receivables(Tenant $tenant, Building $building, Project $project, BillingPeriod $period, User $admin): void
    {
        $db = \Illuminate\Support\Facades\DB::connection()->getName();
        $DB = fn (string $t) => \Illuminate\Support\Facades\DB::table($t);
        $now = Carbon::parse('2026-07-02 09:00');
        $tid = $tenant->id;
        $bid = $building->id;

        // Exact-sum distributor: split $total into $count parts using $weight(i).
        $distribute = function (int $total, int $count, callable $weight): array {
            $w = [];
            $sum = 0.0;
            for ($i = 0; $i < $count; $i++) {
                $wi = $weight($i);
                $w[] = $wi;
                $sum += $wi;
            }
            $out = [];
            $acc = 0;
            for ($i = 0; $i < $count - 1; $i++) {
                $v = (int) round($total * $w[$i] / $sum);
                $out[] = $v;
                $acc += $v;
            }
            $out[] = $total - $acc;

            return $out;
        };

        // ---- 1. Scale apartments in Tòa A up to 1,248 (+ a resident each) ----
        $target = 1248;
        $existing = Apartment::where('building_id', $bid)->count();
        $floorIds = \App\Models\Floor::where('building_id', $bid)->pluck('id')->all() ?: [null];
        $firstNames = ['An', 'Bình', 'Cường', 'Dũng', 'Giang', 'Hà', 'Hùng', 'Khánh', 'Lan', 'Minh', 'Nam', 'Oanh', 'Phúc', 'Quân', 'Sơn', 'Thảo', 'Uyên', 'Vân', 'Xuân', 'Yến'];
        $lastNames = ['Nguyễn Văn', 'Trần Thị', 'Lê Minh', 'Phạm Quang', 'Đỗ Thu', 'Vũ Hoàng', 'Ngô Đức', 'Đặng Thùy', 'Bùi Thị', 'Hoàng Anh', 'Trịnh Văn', 'Đinh Văn', 'Trương Hải', 'Phan Thị', 'Dương Minh'];

        $aptRows = [];
        for ($i = $existing; $i < $target; $i++) {
            $block = ['A', 'B', 'C'][intdiv($i, 416) % 3];
            $floorNo = (intdiv($i, 8) % 25) + 1;
            $unitNo = ($i % 8) + 1;
            $aptRows[] = [
                'tenant_id' => $tid, 'building_id' => $bid,
                'floor_id' => $floorIds[$i % count($floorIds)],
                'code' => sprintf('%s%02d.%02d', $block, $floorNo, $unitNo),
                'status' => 'occupied',
                'area_sqm' => 60 + ($i % 8) * 7.5,
                'type' => ['1PN - 1WC', '2PN - 2WC', '3PN - 2WC'][$i % 3],
                'created_at' => $now, 'updated_at' => $now,
            ];
        }
        foreach (array_chunk($aptRows, 500) as $chunk) {
            $DB('apartments')->insert($chunk);
        }

        // New apartments carry a dotted code (existing use "A-1206"); fetch in order.
        $newApts = $DB('apartments')->where('building_id', $bid)->where('code', 'like', '%.%')
            ->orderBy('id')->pluck('id')->all();

        // One resident + primary relation per new apartment.
        $resRows = [];
        foreach ($newApts as $k => $aptId) {
            $name = $lastNames[$k % count($lastNames)].' '.$firstNames[($k * 3) % count($firstNames)];
            $resRows[] = [
                'tenant_id' => $tid, 'building_id' => $bid,
                'code' => sprintf('CDX-%05d', $k + 1),
                'full_name' => $name,
                'phone' => '09'.str_pad((string) (30000000 + $k), 8, '0', STR_PAD_LEFT),
                'email' => 'cdx'.($k + 1).'@x2bms.vn',
                'status' => 'active',
                'created_at' => $now, 'updated_at' => $now,
            ];
        }
        foreach (array_chunk($resRows, 500) as $chunk) {
            $DB('residents')->insert($chunk);
        }
        $newRes = $DB('residents')->where('code', 'like', 'CDX-%')->orderBy('id')->pluck('id')->all();
        $relRows = [];
        foreach ($newApts as $k => $aptId) {
            $relRows[] = [
                'tenant_id' => $tid, 'resident_id' => $newRes[$k], 'apartment_id' => $aptId,
                'role' => 'owner', 'is_primary' => true, 'start_date' => '2023-01-01',
                'created_at' => $now, 'updated_at' => $now,
            ];
        }
        foreach (array_chunk($relRows, 500) as $chunk) {
            $DB('resident_apartment_relations')->insert($chunk);
        }

        // ---- 2. Statement book for the current period ----
        // Normalise the ~12 statements already seeded for this period to "published"
        // so counts start from a known base, then add the rest.
        $DB('statements')->where('billing_period_id', $period->id)->update([
            'approval_status' => 'published',
            'published_at' => Carbon::parse('2026-07-01 10:00'),
            'due_date' => '2026-07-20',
            'sent_channel' => 'app',
            'assignee_name' => 'Nguyễn Thị Lan',
        ]);
        $baseStmts = $DB('statements')->where('billing_period_id', $period->id)->count();
        $baseSum = (int) $DB('statements')->where('billing_period_id', $period->id)->sum('total_amount');
        // Mark 12 of the base as viewed (contributes to the 732 "đã xem").
        $baseIds = $DB('statements')->where('billing_period_id', $period->id)->orderBy('id')->pluck('id')->all();
        $DB('statements')->whereIn('id', array_slice($baseIds, 0, min(12, count($baseIds))))
            ->update(['viewed_at' => Carbon::parse('2026-07-03 08:00')]);
        $baseViewed = min(12, count($baseIds));

        $totalReceivable = 8_420_000_000;
        $publishedTarget = 1086;
        $pendingTarget = 124;
        $viewedTarget = 732;
        $overdueTarget = 148;

        $newPublished = max(0, $publishedTarget - $baseStmts);   // e.g. 1074
        $newPending = $pendingTarget;                             // 124
        $newCount = $newPublished + $newPending;                  // 1198

        // Apartments for the new statements: those right after the ones already billed.
        $allApts = Apartment::where('building_id', $bid)->orderBy('id')->pluck('id')->all();
        $stmtApts = array_slice($allApts, $baseStmts, $newCount);

        // Totals distributed so the grand period sum is exactly 8,42 tỷ.
        $remaining = $totalReceivable - $baseSum;
        $totals = $distribute($remaining, $newCount, fn ($i) => 0.75 + ($i % 9) * 0.06);

        $viewedNeeded = $viewedTarget - $baseViewed;   // new published to mark viewed
        $stmtRows = [];
        foreach ($stmtApts as $j => $aptId) {
            $isPublished = $j < $newPublished;
            $total = max(1_000_000, $totals[$j]);
            $isOverdue = $isPublished && $j >= ($newPublished - $overdueTarget); // last 148 published
            if ($isOverdue) {
                $paid = 0;
                $status = 'issued';
            } elseif ($j % 3 === 0 && $isPublished) {
                $paid = $total;
                $status = 'paid';
            } elseif ($isPublished) {
                $paid = (int) round($total * 0.4);
                $status = 'partial';
            } else { // pending publish
                $paid = 0;
                $status = 'issued';
            }
            $stmtRows[] = [
                'tenant_id' => $tid, 'building_id' => $bid, 'billing_period_id' => $period->id,
                'apartment_id' => $aptId,
                'code' => sprintf('BK-2026-07-%04d', $baseStmts + $j + 1),
                'total_amount' => $total, 'paid_amount' => $paid, 'status' => $status,
                'approval_status' => $isPublished ? 'published' : 'pending',
                'published_at' => $isPublished ? Carbon::parse('2026-07-01 10:00') : null,
                'viewed_at' => ($isPublished && $j < $viewedNeeded) ? Carbon::parse('2026-07-04 09:00') : null,
                'due_date' => $isOverdue ? '2026-06-20' : '2026-07-20',
                'assignee_name' => ['Nguyễn Thị Lan', 'Phạm Văn Tuấn', 'Nguyễn Huy Hoàng'][$j % 3],
                'sent_channel' => ['app', 'email', 'sms', 'zalo'][$j % 4],
                'created_at' => $now, 'updated_at' => $now,
            ];
        }
        foreach (array_chunk($stmtRows, 500) as $chunk) {
            $DB('statements')->insert($chunk);
        }

        // Fee lines for the new statements (drives "Số khoản phí" on 03-04 and the
        // statement detail on 03-09). 5-7 lines each, amounts summing to the total.
        $feeItems = [
            ['Phí quản lý', 0.40], ['Phí gửi xe ô tô', 0.13], ['Phí gửi xe máy', 0.05],
            ['Điện sinh hoạt', 0.22], ['Nước sinh hoạt', 0.07], ['Internet nội bộ', 0.05], ['Phí tiện ích', 0.08],
        ];
        $newStmts = $DB('statements')->where('billing_period_id', $period->id)->whereNotNull('code')
            ->orderBy('id')->get(['id', 'total_amount']);
        $lineRows = [];
        foreach ($newStmts as $s) {
            $n = 5 + ($s->id % 3); // 5..7 lines
            $items = array_slice($feeItems, 0, $n);
            $parts = $distribute((int) $s->total_amount, $n, fn ($i) => $items[$i][1]);
            foreach ($items as $i => [$label, $w]) {
                $amt = $parts[$i];
                $lineRows[] = [
                    'statement_id' => $s->id, 'fee_type' => $label, 'amount' => $amt,
                    'quantity' => 1, 'unit_price' => $amt, 'created_at' => $now, 'updated_at' => $now,
                ];
            }
        }
        foreach (array_chunk($lineRows, 1000) as $chunk) {
            $DB('statement_lines')->insert($chunk);
        }

        // ---- 3. Debt / aging ledger: 24 debtor records ----
        $agingBuckets = [
            'bucket_0_30' => 1_020_000_000,
            'bucket_31_60' => 650_000_000,
            'bucket_61_90' => 320_000_000,
            'bucket_over_90' => 210_000_000,
        ];
        $debtCount = 24;
        $split = [];
        foreach ($agingBuckets as $col => $bTotal) {
            $split[$col] = $distribute($bTotal, $debtCount, fn ($i) => max(0.0, 1.0 + sin($i * 1.3) + ($i % 4) * 0.4));
        }

        // Reuse the ~12 existing Tòa A debts, top up to 24, then rewrite all with buckets.
        $debtApts = array_slice($allApts, 0, $debtCount);
        $existingDebtCount = $DB('debts')->where('building_id', $bid)->count();
        for ($n = $existingDebtCount; $n < $debtCount; $n++) {
            $DB('debts')->insert([
                'tenant_id' => $tid, 'building_id' => $bid, 'apartment_id' => $debtApts[$n],
                'amount' => 0, 'is_overdue' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
        $debtIds = $DB('debts')->where('building_id', $bid)->orderBy('id')->limit($debtCount)->pluck('id')->all();
        $recovery = ['new', 'in_progress', 'overdue_handling'];
        $assignees = ['Nguyễn Thị Lan', 'Phạm Văn Tuấn', 'Nguyễn Huy Hoàng'];
        $periodsAgo = ['07/2026', '06/2026', '05/2026', '04/2026', '03/2026'];

        // Risk by overdue severity rank (older buckets weigh more): top 4 critical,
        // next 6 high, next 8 medium, rest low — a realistic spread like the image.
        $severity = [];
        foreach ($debtIds as $n => $debtId) {
            $severity[$n] = $split['bucket_over_90'][$n] * 3 + $split['bucket_61_90'][$n] * 2 + $split['bucket_31_60'][$n];
        }
        arsort($severity);
        $riskByIndex = [];
        $rank = 0;
        foreach (array_keys($severity) as $n) {
            $riskByIndex[$n] = $rank < 4 ? 'critical' : ($rank < 10 ? 'high' : ($rank < 18 ? 'medium' : 'low'));
            $rank++;
        }

        foreach ($debtIds as $n => $debtId) {
            $b0 = $split['bucket_0_30'][$n];
            $b1 = $split['bucket_31_60'][$n];
            $b2 = $split['bucket_61_90'][$n];
            $b3 = $split['bucket_over_90'][$n];
            $amount = $b0 + $b1 + $b2 + $b3;
            $riskKey = $riskByIndex[$n];
            $apt = Apartment::find($debtApts[$n]);
            $resName = optional(\App\Models\Resident::whereHas('apartmentRelations', fn ($q) => $q->where('apartment_id', $debtApts[$n]))->first())->full_name
                ?? ('Cư dân '.($apt->code ?? $n));
            $DB('debts')->where('id', $debtId)->update([
                'code' => sprintf('AR-2026-%04d', $n + 1),
                'apartment_id' => $debtApts[$n],
                'resident_name' => $resName,
                'last_period_code' => $periodsAgo[$n % count($periodsAgo)],
                'amount' => $amount,
                'bucket_0_30' => $b0, 'bucket_31_60' => $b1, 'bucket_61_90' => $b2, 'bucket_over_90' => $b3,
                'is_overdue' => ($b1 + $b2 + $b3) > 0,
                'risk_level' => $riskKey,
                'recovery_status' => $recovery[$n % 3],
                'assignee_name' => $assignees[$n % 3],
                'updated_at' => $now,
            ]);
        }

        // ---- 4. Prior-period statement history for the 24 debtors (03-06 ledger) ----
        // Fully-paid statements for the 6 months before the current period so each
        // apartment's debt book shows a real multi-period history.
        $priorPeriods = BillingPeriod::where('building_id', $bid)->where('id', '<>', $period->id)
            ->orderByDesc('period_month')->take(6)->get();
        $histRows = [];
        foreach ($debtApts as $n => $aptId) {
            foreach ($priorPeriods as $p) {
                $total = 5_000_000 + ($n % 5) * 250_000;
                $histRows[] = [
                    'tenant_id' => $tid, 'building_id' => $bid, 'billing_period_id' => $p->id,
                    'apartment_id' => $aptId,
                    'code' => sprintf('BK-%s-D%03d', str_replace('-', '', $p->code), $n + 1),
                    'total_amount' => $total, 'paid_amount' => $total, 'status' => 'paid',
                    'approval_status' => 'published',
                    'published_at' => Carbon::parse($p->period_month)->addDay()->setTime(10, 0),
                    'due_date' => Carbon::parse($p->period_month)->addDays(19)->toDateString(),
                    'sent_channel' => 'app',
                    'created_at' => $now, 'updated_at' => $now,
                ];
            }
        }
        foreach (array_chunk($histRows, 500) as $chunk) {
            $DB('statements')->insert($chunk);
        }
    }

    /**
     * BQL-03-02 — fee cycles list ("Chu kỳ phí & đợt thu"): one cycle per fee type
     * per month (CP-YYYY-MM-XX), matching the handoff image. Separate from the
     * monthly billing_periods used by the statement backbone (filtered by CP- code).
     * KPI targets: 6 đang mở / 3 chờ chốt.
     */
    private function seedBql0302Cycles(Tenant $tenant, Building $building): void
    {
        $scope = 'Tòa A1, A2, A3';
        // [suffix, month, name, fee_category, scope, status]
        $cycles = [
            ['DV', '2026-07', 'Phí quản lý tháng 07/2026', 'Phí quản lý', $scope, 'open'],
            ['XE', '2026-07', 'Phí gửi xe tháng 07/2026', 'Phí gửi xe', $scope, 'open'],
            ['DN', '2026-07', 'Điện nước tháng 07/2026', 'Điện nước', $scope, 'open'],
            ['DVTM', '2026-07', 'Phí dịch vụ khác 07/2026', 'Phí dịch vụ', 'Tòa A1', 'pending_close'],
            ['DV', '2026-06', 'Phí quản lý tháng 06/2026', 'Phí quản lý', $scope, 'open'],
            ['XE', '2026-06', 'Phí gửi xe tháng 06/2026', 'Phí gửi xe', $scope, 'open'],
            ['DN', '2026-06', 'Điện nước tháng 06/2026', 'Điện nước', $scope, 'open'],
            ['DV', '2026-05', 'Phí quản lý tháng 05/2026', 'Phí quản lý', $scope, 'pending_close'],
            ['XE', '2026-05', 'Phí gửi xe tháng 05/2026', 'Phí gửi xe', $scope, 'pending_close'],
            ['DV', '2026-04', 'Phí quản lý tháng 04/2026', 'Phí quản lý', $scope, 'published'],
        ];
        $expectedByCategory = ['Phí quản lý' => 4_216_800_000, 'Phí gửi xe' => 1_248_000_000, 'Điện nước' => 1_856_300_000, 'Phí dịch vụ' => 312_000_000];

        foreach ($cycles as [$suffix, $month, $name, $cat, $scopeLabel, $status]) {
            BillingPeriod::create([
                'tenant_id' => $tenant->id,
                'building_id' => $building->id,
                'code' => sprintf('CP-%s-%s', $month, $suffix),
                'label' => 'Kỳ '.substr($month, 5, 2).'/'.substr($month, 0, 4),
                'name' => $name,
                'fee_category' => $cat,
                'scope_label' => $scopeLabel,
                'period_month' => Carbon::parse($month.'-01'),
                'expected_units' => $scopeLabel === 'Tòa A1' ? 416 : 1248,
                'expected_amount' => $expectedByCategory[$cat] ?? 0,
                'status' => $status,
                'is_current' => false,
            ]);
        }
    }

    /**
     * WEB-FORM-07/08 — billing run + statement lines/approval/publish, then
     * payments + allocations + receipts + a bank import reconciled to payments.
     */
    private function seedBillingAndPayments(Tenant $tenant, Building $building, BillingPeriod $period, User $admin): void
    {
        // Mark the current period published & due.
        $period->update(['status' => 'published', 'due_date' => Carbon::parse('2026-07-15')]);

        $feeTypes = \App\Models\FeeType::where('tenant_id', $tenant->id)->get()->keyBy('code');
        $statements = Statement::where('billing_period_id', $period->id)->get();

        // Approval lifecycle variety for the queue (WEB-FORM-07-04).
        foreach ($statements as $i => $st) {
            $approval = $i < 4 ? 'published' : ($i < 7 ? 'approved' : 'pending');
            $st->update([
                'approval_status' => $approval,
                'published_at' => $approval === 'published' ? Carbon::parse('2026-07-01 10:00') : null,
            ]);
        }

        // Statement lines (QL theo m² + RAC cố định) tied to the fee catalog.
        foreach ($statements as $st) {
            $apt = Apartment::find($st->apartment_id);
            $area = (float) ($apt->area_sqm ?? 70);
            \App\Models\StatementLine::create([
                'statement_id' => $st->id, 'fee_type_id' => $feeTypes['QL']->id ?? null,
                'fee_type' => 'Phí quản lý', 'quantity' => $area, 'unit_price' => 16_500,
                'amount' => round($area * 16_500),
            ]);
            \App\Models\StatementLine::create([
                'statement_id' => $st->id, 'fee_type_id' => $feeTypes['RAC']->id ?? null,
                'fee_type' => 'Phí vệ sinh', 'quantity' => 1, 'unit_price' => 50_000, 'amount' => 50_000,
            ]);
        }

        // Billing run that produced this period's statements.
        $run = \App\Models\BillingRun::create([
            'tenant_id' => $tenant->id, 'building_id' => $building->id, 'billing_period_id' => $period->id,
            'code' => 'BK-2607-A', 'status' => 'completed', 'approval_status' => 'approved',
            'total_billed' => $statements->sum('total_amount'), 'statements_count' => $statements->count(),
            'apartment_count' => $statements->count(),
            'created_by_id' => $admin->id, 'approver_id' => $admin->id,
            'run_at' => Carbon::parse('2026-07-01 08:00'),
        ]);
        foreach ($statements as $st) {
            \App\Models\BillingRunItem::create([
                'billing_run_id' => $run->id, 'apartment_id' => $st->apartment_id,
                'statement_id' => $st->id, 'amount' => $st->total_amount, 'status' => 'ok',
            ]);
        }

        // Approval + publish.
        \App\Models\StatementApproval::create([
            'tenant_id' => $tenant->id, 'billing_period_id' => $period->id, 'approver_id' => $admin->id,
            'level' => 1, 'status' => 'approved', 'note' => 'Duyệt phát hành kỳ T7/2026',
            'decided_at' => Carbon::parse('2026-07-01 09:30'),
        ]);
        \App\Models\StatementPublishLog::create([
            'tenant_id' => $tenant->id, 'billing_period_id' => $period->id, 'published_by_id' => $admin->id,
            'channel' => 'app', 'statements_count' => $statements->count(), 'published_at' => Carbon::parse('2026-07-01 10:00'),
        ]);

        // Payment methods + bank account.
        $methods = [];
        foreach ([['CASH', 'Tiền mặt', 'cash'], ['BANK', 'Chuyển khoản', 'bank'], ['QR', 'QR VietQR', 'qr']] as [$c, $n, $t]) {
            $methods[$c] = \App\Models\PaymentMethod::create(['tenant_id' => $tenant->id, 'code' => $c, 'name' => $n, 'type' => $t]);
        }
        $bank = \App\Models\BankAccount::create([
            'tenant_id' => $tenant->id, 'bank_name' => 'Vietcombank', 'account_no' => '0123456789',
            'account_name' => 'CTY QL VH SUNSHINE', 'is_active' => true,
        ]);

        // Payments for the paid statements (+ allocation + receipt).
        $import = \App\Models\BankStatementImport::create([
            'tenant_id' => $tenant->id, 'bank_account_id' => $bank->id, 'code' => 'IMP-2026-07',
            'status' => 'done', 'row_count' => 0, 'imported_at' => Carbon::parse('2026-07-08 07:00'),
        ]);

        $paid = $statements->where('status', 'paid')->values();
        $rows = 0;
        foreach ($paid as $i => $st) {
            $payment = \App\Models\Payment::create([
                'tenant_id' => $tenant->id, 'building_id' => $building->id, 'apartment_id' => $st->apartment_id,
                'payment_method_id' => ($i % 2 ? $methods['BANK'] : $methods['QR'])->id,
                'code' => 'PAY-2607-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'amount' => $st->paid_amount, 'paid_at' => Carbon::parse('2026-07-05')->addDays($i),
                'reference_no' => 'FT2607'.(100000 + $i), 'status' => 'confirmed',
            ]);
            \App\Models\PaymentAllocation::create([
                'payment_id' => $payment->id, 'statement_id' => $st->id, 'amount' => $st->paid_amount,
            ]);
            \App\Models\Receipt::create([
                'tenant_id' => $tenant->id, 'payment_id' => $payment->id, 'code' => 'BL-2607-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'amount' => $st->paid_amount, 'issued_at' => $payment->paid_at, 'issued_by_id' => $admin->id,
            ]);

            // Matching bank credit for ~first 5 payments.
            if ($i < 5) {
                $txn = \App\Models\BankTransaction::create([
                    'tenant_id' => $tenant->id, 'bank_account_id' => $bank->id, 'bank_statement_import_id' => $import->id,
                    'txn_date' => $payment->paid_at->toDateString(), 'amount' => $payment->amount, 'direction' => 'credit',
                    'description' => 'TT phi '.$st->apartment_id, 'reference_no' => $payment->reference_no,
                    'is_matched' => true, 'payment_id' => $payment->id,
                ]);
                \App\Models\ReconciliationMatch::create([
                    'tenant_id' => $tenant->id, 'bank_transaction_id' => $txn->id, 'payment_id' => $payment->id,
                    'statement_id' => $st->id, 'amount' => $payment->amount, 'status' => 'confirmed', 'matched_by_id' => $admin->id,
                ]);
                $rows++;
            }
        }

        // A couple of unmatched credits to populate the reconciliation queue.
        for ($k = 0; $k < 2; $k++) {
            \App\Models\BankTransaction::create([
                'tenant_id' => $tenant->id, 'bank_account_id' => $bank->id, 'bank_statement_import_id' => $import->id,
                'txn_date' => '2026-07-09', 'amount' => 500_000 + $k * 100_000, 'direction' => 'credit',
                'description' => 'CK chua doi soat #'.($k + 1), 'is_matched' => false,
            ]);
            $rows++;
        }
        $import->update(['row_count' => $rows]);
    }

    /**
     * Demonstrates the SaaS identity flow: ONE self-registered, KYC'd account
     * (Nguyễn Văn Anh) that is a resident in TWO different companies/projects,
     * each with a different locally-typed name (Nguyễn Văn A / Anh A), unified by
     * CCCD via residents.user_id. The account is global (tenant_id null).
     */
    private function seedCrossCompanyResident(Tenant $sunshineTenant, Apartment $sunshineApartment): void
    {
        $cccd = '079200001555';

        $account = User::create([
            'tenant_id' => null,            // global — not bound to any company
            'account_type' => 'resident',
            'name' => 'Nguyễn Văn Anh',     // canonical KYC name
            'email' => 'nguyenvananh@gmail.com',
            'password' => Hash::make('Resident@2026!'),
            'email_verified_at' => now(),
            'phone' => '0900000555',
            'id_no' => $cccd,
            'dob' => Carbon::parse('1986-03-12'),
            'gender' => 'Nam',
            'nationality' => 'Việt Nam',
            'kyc_status' => 'verified',
            'kyc_verified_at' => now(),
            'is_platform_admin' => false,
        ]);

        // Membership 1 — Sunshine (Cty Sunshine). BQL typed "Nguyễn Văn A".
        $r1 = \App\Models\Resident::create([
            'tenant_id' => $sunshineTenant->id,
            'building_id' => $sunshineApartment->building_id,
            'user_id' => $account->id,
            'link_status' => 'linked',
            'linked_at' => now(),
            'code' => 'CD-LINK-A',
            'full_name' => 'Nguyễn Văn A',  // diverges from the KYC name on purpose
            'phone' => '0900000555',
            'email' => 'nguyenvananh@gmail.com',
            'id_no' => $cccd,
            'status' => 'active',
            'profile_status' => 'hoat_dong',
            'source' => 'bql_manual',
            'kyc_status' => 'verified',
            'requested_role' => 'owner',
        ]);
        \App\Models\ResidentApartmentRelation::create([
            'tenant_id' => $sunshineTenant->id,
            'resident_id' => $r1->id,
            'apartment_id' => $sunshineApartment->id,
            'role' => 'owner',
            'is_primary' => false,
            'start_date' => Carbon::parse('2025-03-01'),
        ]);

        // A SECOND management company (different tenant) where the same person owns a unit.
        $tenant2 = Tenant::create([
            'code' => 'T-DAIPHUC',
            'name' => 'Công ty CP Quản lý BĐS Đại Phúc',
            'short_name' => 'Đại Phúc OM',
            'plan' => 'standard',
            'status' => 'active',
        ]);
        $project2 = Project::create([
            'tenant_id' => $tenant2->id,
            'code' => 'DAIPHUC-RS',
            'name' => 'Đại Phúc Riverside',
            'type' => 'apartment',
            'status' => 'active',
            'city' => 'TP. Hồ Chí Minh',
        ]);
        $building2 = Building::create([
            'tenant_id' => $tenant2->id,
            'project_id' => $project2->id,
            'code' => 'DP-A',
            'name' => 'Đại Phúc Riverside - Tòa A',
            'type' => 'residential',
            'status' => 'active',
            'apartment_count' => 50,
            'floor_count' => 10,
        ]);
        $floor2 = \App\Models\Floor::create([
            'tenant_id' => $tenant2->id, 'building_id' => $building2->id,
            'code' => 'DP-F08', 'name' => 'Tầng 8', 'level' => 8,
        ]);
        $apt2 = Apartment::create([
            'tenant_id' => $tenant2->id, 'building_id' => $building2->id, 'floor_id' => $floor2->id,
            'code' => 'DP-08.12', 'status' => 'occupied', 'area_sqm' => 88, 'type' => '2PN - 2WC',
            'ownership_type' => 'Sở hữu lâu dài',
        ]);

        // Membership 2 — Đại Phúc (Cty Đại Phúc). BQL typed "Anh A".
        $r2 = \App\Models\Resident::create([
            'tenant_id' => $tenant2->id,
            'building_id' => $building2->id,
            'user_id' => $account->id,      // SAME global account
            'link_status' => 'linked',
            'linked_at' => now(),
            'code' => 'CD-LINK-B',
            'full_name' => 'Anh A',         // a different local label again
            'phone' => '0900000555',
            'id_no' => $cccd,
            'status' => 'active',
            'profile_status' => 'hoat_dong',
            'source' => 'bql_manual',
            'kyc_status' => 'verified',
            'requested_role' => 'owner',
        ]);
        \App\Models\ResidentApartmentRelation::create([
            'tenant_id' => $tenant2->id,
            'resident_id' => $r2->id,
            'apartment_id' => $apt2->id,
            'role' => 'owner',
            'is_primary' => true,
            'start_date' => Carbon::parse('2025-05-01'),
        ]);
    }

    /**
     * A second project (workspace) under the same tenant, with several buildings.
     * Buildings here are pure structural DATA — the project is the work context;
     * tòa is only a filter. Seeded light (structure + residents) so switching the
     * project context shows a real, non-empty second workspace.
     */
    private function seedSecondProject(Tenant $tenant, array $firstNames): void
    {
        $project = Project::create([
            'tenant_id' => $tenant->id,
            'code' => 'RIVERSIDE',
            'name' => 'Riverside Residence',
        ]);

        $buildings = [
            ['R1', 'Riverside - Tòa R1', 80],
            ['R2', 'Riverside - Tòa R2', 64],
            ['R3', 'Riverside - Tòa R3', 96],
        ];

        $resSeq = 0;
        foreach ($buildings as $b => [$code, $name, $count]) {
            $building = Building::create([
                'tenant_id' => $tenant->id,
                'project_id' => $project->id,
                'code' => $code,
                'name' => $name,
                'apartment_count' => $count,
            ]);
            $scope = ['tenant_id' => $tenant->id, 'building_id' => $building->id];

            // A few floors + apartments per tòa (data only).
            for ($level = 1; $level <= 3; $level++) {
                $floor = \App\Models\Floor::create($scope + [
                    'code' => sprintf('%s-F%02d', $code, $level),
                    'name' => "Tầng {$level}",
                    'level' => $level,
                ]);
                for ($unit = 1; $unit <= 4; $unit++) {
                    $apt = Apartment::create($scope + [
                        'floor_id' => $floor->id,
                        'code' => sprintf('%s-%02d%02d', $code, $level, $unit),
                        'status' => 'occupied',
                        'area_sqm' => 58 + ($unit * 7),
                        'type' => ['1PN - 1WC', '2PN - 2WC', '3PN - 2WC'][$unit % 3],
                        'ownership_type' => 'Sở hữu lâu dài',
                        'handover_date' => Carbon::parse('2024-09-01'),
                        'management_fee' => 14000,
                    ]);
                    $resSeq++;
                    $resident = \App\Models\Resident::create($scope + [
                        'code' => sprintf('CDR-%04d', $resSeq),
                        'full_name' => 'Lê Văn '.$firstNames[$resSeq % count($firstNames)],
                        'phone' => '07'.str_pad((string) (50000000 + $resSeq), 8, '0', STR_PAD_LEFT),
                        'email' => 'cudanr'.$resSeq.'@x2bms.vn',
                        'status' => 'active',
                        'gender' => $resSeq % 2 ? 'Nam' : 'Nữ',
                        'nationality' => 'Việt Nam',
                        'join_date' => Carbon::parse('2024-09-10'),
                    ]);
                    \App\Models\ResidentApartmentRelation::create([
                        'tenant_id' => $tenant->id,
                        'resident_id' => $resident->id,
                        'apartment_id' => $apt->id,
                        'role' => 'owner',
                        'is_primary' => true,
                        'start_date' => Carbon::parse('2025-01-01'),
                    ]);
                }
            }
        }
    }

    /**
     * A compact second building (Tòa B) so the building context switcher
     * (WEB-UX-01) filters real data and shows resident status variety.
     */
    private function seedSecondaryBuilding(Tenant $tenant, Project $project, array $firstNames): void
    {
        $building = Building::create([
            'tenant_id' => $tenant->id,
            'project_id' => $project->id,
            'code' => 'SG-B',
            'name' => 'Sunshine Garden - Tòa B',
            'apartment_count' => 40,
        ]);
        $scope = ['tenant_id' => $tenant->id, 'building_id' => $building->id];

        $depts = collect([
            ['code' => 'KT', 'name' => 'Kỹ thuật'],
            ['code' => 'AN', 'name' => 'An ninh'],
            ['code' => 'VS', 'name' => 'Vệ sinh'],
        ])->map(fn ($d) => Department::create($scope + $d))->keyBy('code');

        $floors = [];
        for ($level = 1; $level <= 5; $level++) {
            $floors[$level] = \App\Models\Floor::create($scope + [
                'code' => sprintf('BF%02d', $level), 'name' => "Tầng {$level}", 'level' => $level,
            ]);
        }

        $apartments = [];
        for ($level = 1; $level <= 5; $level++) {
            for ($unit = 1; $unit <= 4; $unit++) {
                $apartments[] = Apartment::create($scope + [
                    'floor_id' => $floors[$level]->id,
                    'code' => sprintf('B-%02d%02d', $level, $unit),
                    'status' => 'occupied',
                    'area_sqm' => 60 + ($unit * 6),
                    'type' => ['1PN - 1WC', '2PN - 2WC', '3PN - 2WC'][$unit % 3],
                    'ownership_type' => 'Sở hữu lâu dài',
                    'handover_date' => Carbon::parse('2023-03-15'),
                    'management_fee' => 15000,
                ]);
            }
        }

        foreach ($apartments as $i => $apt) {
            // Status variety for the list view: ~15% pending, ~10% locked, rest active.
            $status = $i % 7 === 0 ? 'pending' : ($i % 9 === 0 ? 'inactive' : 'active');
            $resident = \App\Models\Resident::create($scope + [
                'code' => sprintf('CDB-%04d', $i + 1),
                'full_name' => 'Trần Văn '.$firstNames[$i % count($firstNames)],
                'phone' => '08'.str_pad((string) (30000000 + $i), 8, '0', STR_PAD_LEFT),
                'email' => 'cudanb'.($i + 1).'@x2bms.vn',
                'status' => $status,
                'dob' => Carbon::parse('1988-01-01')->addDays($i * 41),
                'gender' => $i % 2 ? 'Nam' : 'Nữ',
                'id_no' => sprintf('0790%08d', 8000000 + $i),
                'id_issued_date' => Carbon::parse('2019-01-01')->addDays($i),
                'id_issued_place' => 'Cục CSQL HC về TTXH',
                'nationality' => 'Việt Nam',
                'marital_status' => $i % 3 === 0 ? 'Độc thân' : 'Đã kết hôn',
                'contact_address' => $apt->code.' - Tòa B, Sunshine Garden, P. An Phú, TP. Thủ Đức, TP. HCM',
                'mailing_address' => $apt->code.' - Tòa B, Sunshine Garden, P. An Phú, TP. Thủ Đức, TP. HCM',
                'join_date' => Carbon::parse('2023-03-20')->addDays($i % 40),
            ]);
            \App\Models\ResidentApartmentRelation::create([
                'tenant_id' => $tenant->id,
                'resident_id' => $resident->id,
                'apartment_id' => $apt->id,
                'role' => $i % 4 === 0 ? 'tenant' : ($i % 11 === 0 ? 'member' : 'owner'),
                'is_primary' => true,
                'start_date' => Carbon::parse('2025-06-01'),
            ]);
            \App\Models\ResidentEmergencyContact::create([
                'tenant_id' => $tenant->id,
                'resident_id' => $resident->id,
                'full_name' => $i % 2 ? 'Lê Thị Mai' : 'Trần Văn Hòa',
                'relationship' => $i % 2 ? 'Vợ' : 'Anh/Em',
                'phone' => '0938'.str_pad((string) (100000 + $i), 6, '0', STR_PAD_LEFT),
                'email' => 'lienheb'.($i + 1).'@gmail.com',
            ]);
        }

        // Billing trend (T5–T7), current = T7 (~95%).
        $current = null;
        foreach ([
            ['2026-05', 'T5/2026', 760_000_000, 720_000_000, false],
            ['2026-06', 'T6/2026', 780_000_000, 745_000_000, false],
            ['2026-07', 'T7/2026', 800_000_000, 760_000_000, true],
        ] as [$code, $label, $billed, $collected, $isCurrent]) {
            $period = BillingPeriod::create($scope + [
                'code' => $code, 'label' => $label, 'period_month' => Carbon::parse($code.'-01'),
                'billed_amount' => $billed, 'collected_amount' => $collected, 'is_current' => $isCurrent,
            ]);
            if ($isCurrent) {
                $current = $period;
            }
        }
        foreach (array_slice($apartments, 0, 6) as $i => $apt) {
            $total = 15_000_000 + ($i * 80_000);
            Statement::create($scope + [
                'billing_period_id' => $current->id, 'apartment_id' => $apt->id,
                'total_amount' => $total, 'paid_amount' => $i < 4 ? $total : (int) ($total * 0.5),
                'status' => $i < 4 ? 'paid' : 'partial',
            ]);
        }
        foreach (array_slice($apartments, 6, 4) as $apt) {
            Debt::create($scope + ['apartment_id' => $apt->id, 'amount' => 5_000_000, 'due_date' => Carbon::parse('2026-07-10'), 'is_overdue' => true]);
        }

        // Feedback (tenant categories already exist) — a handful, some pending.
        $categories = FeedbackCategory::where('tenant_id', $tenant->id)->get();
        foreach ($categories as $c => $cat) {
            for ($n = 1; $n <= 3; $n++) {
                FeedbackRequest::create($scope + [
                    'feedback_category_id' => $cat->id,
                    'title' => "Phản ánh {$cat->name} B#{$n}",
                    'status' => $n === 1 ? FeedbackStatus::New : ($n === 2 ? FeedbackStatus::Resolved : FeedbackStatus::Closed),
                    'priority' => 'normal',
                ]);
            }
        }

        // Work orders for department performance + today table.
        foreach ([
            ['Kiểm tra bơm tăng áp Tòa B', 'KT', WorkOrderStatus::InProgress, 'high'],
            ['Vệ sinh hầm xe Tòa B', 'VS', WorkOrderStatus::Pending, 'normal'],
            ['Tuần tra an ninh tầng 5', 'AN', WorkOrderStatus::Pending, 'normal'],
        ] as $i => [$title, $code, $status, $priority]) {
            WorkOrder::create($scope + [
                'department_id' => $depts[$code]->id, 'code' => sprintf('WOB-%04d', $i + 1),
                'title' => $title, 'status' => $status, 'priority' => $priority, 'due_at' => now()->addDays($i + 1),
            ]);
        }
        $seq = 0;
        foreach (['KT' => [16, 3], 'AN' => [12, 2], 'VS' => [14, 1]] as $code => [$done, $open]) {
            for ($d = 0; $d < $done; $d++) {
                WorkOrder::create($scope + ['department_id' => $depts[$code]->id, 'code' => 'WOB-'.(++$seq + 100), 'title' => "Việc {$code} B#{$d}", 'status' => WorkOrderStatus::Done, 'priority' => 'normal']);
            }
            for ($o = 0; $o < $open; $o++) {
                WorkOrder::create($scope + ['department_id' => $depts[$code]->id, 'code' => 'WOB-'.(++$seq + 200), 'title' => "Việc mở {$code} B#{$o}", 'status' => WorkOrderStatus::Pending, 'priority' => 'normal']);
            }
        }

        for ($i = 1; $i <= 5; $i++) {
            SlaEvent::create($scope + ['type' => $i <= 2 ? 'breach' : 'due_soon', 'status' => 'open', 'description' => "Phản ánh B#{$i} sắp/đã quá hạn SLA"]);
        }
        foreach ([
            ['warning', 'Thang máy B2 báo lỗi cảm biến', 'device'],
            ['info', 'Lịch bảo trì máy phát điện Tòa B', 'device'],
            ['critical', 'Rò rỉ nước tầng kỹ thuật Tòa B', 'meter'],
        ] as [$severity, $title, $source]) {
            IocAlert::create($scope + ['severity' => $severity, 'title' => $title, 'source' => $source, 'status' => 'open']);
        }
        foreach ([
            ['Điều phối vệ sinh hầm xe Tòa B', 'Có phản ánh tồn đọng khu vực B1'],
            ['Nhắc thu 4 căn công nợ Tòa B', 'Tổng ~20 triệu đến hạn'],
        ] as [$title, $detail]) {
            AiSuggestion::create($scope + ['context' => 'operational_dashboard', 'title' => $title, 'detail' => $detail]);
        }

        foreach ([
            ['Đặng Văn Phúc', 'owner', 90, 4],
            ['Lý Thị Hương', 'tenant', 72, 2],
            ['Hồ Văn Đức', 'member', 80, 3],
        ] as $i => [$fullName, $reqRole, $score, $docs]) {
            \App\Models\ResidentApprovalRequest::create($scope + [
                'apartment_id' => $apartments[$i]->id,
                'full_name' => $fullName,
                'phone' => '08'.str_pad((string) (40000000 + $i), 8, '0', STR_PAD_LEFT),
                'email' => 'applicantb'.($i + 1).'@x2bms.vn',
                'requested_role' => $reqRole, 'match_score' => $score, 'document_count' => $docs,
                'status' => 'pending', 'submitted_at' => now()->subDays($i + 1), 'note' => null,
            ]);
        }
    }

    /**
     * Tier 2 — Resident Experience MVP: Notification (3 lớp) + Amenity/Booking +
     * Feedback children + Visitor + Package. Dữ liệu demo tôn trọng phân quyền 3 lớp
     * (platform/tenant/project).
     */
    private function seedTier2(Tenant $tenant, Project $project, Building $building, User $admin): void
    {
        $apts = Apartment::where('building_id', $building->id)->orderBy('id')->take(8)->get();
        $residents = \App\Models\Resident::where('tenant_id', $tenant->id)->orderBy('id')->take(8)->get();
        $staff = User::where('tenant_id', $tenant->id)->where('account_type', 'staff')->orderBy('id')->take(3)->get();
        $handler = $staff->first() ?? $admin;

        // ============ NOTIFICATIONS (3 lớp) ============
        // Platform — toàn hệ thống (superadmin), gửi mọi đối tượng.
        $plat = \App\Models\Notification::create([
            'tenant_id' => null, 'owner_level' => 'platform', 'code' => 'NTF-PLAT-001',
            'type' => 'system', 'title' => 'Nâng cấp hệ thống X2-BMS cuối tuần',
            'summary' => 'Bảo trì 22:00–23:00 Thứ 7. Một số tính năng tạm gián đoạn.',
            'body' => '<p>Hệ thống sẽ bảo trì định kỳ. Vui lòng lưu công việc trước 22:00.</p>',
            'priority' => 'high', 'status' => 'published', 'is_pinned' => true,
            'published_at' => Carbon::parse('2026-06-28 09:00'), 'created_by_id' => $admin->id, 'published_by_id' => $admin->id,
        ]);
        \App\Models\NotificationAudience::create(['notification_id' => $plat->id, 'scope_type' => 'all']);
        foreach (['app', 'email'] as $ch) {
            \App\Models\NotificationChannel::create(['notification_id' => $plat->id, 'channel' => $ch]);
        }

        // Tenant — công ty vận hành, gửi xuống dự án.
        $ten = \App\Models\Notification::create([
            'tenant_id' => $tenant->id, 'owner_level' => 'tenant', 'project_id' => $project->id, 'code' => 'NTF-CO-001',
            'type' => 'billing', 'title' => 'Lịch thu phí quý 3/2026',
            'summary' => 'Thông báo kỳ thu phí và hạn thanh toán tới cư dân toàn dự án.',
            'body' => '<p>Kỳ phí Q3 phát hành 01/07, hạn 10/07. Vui lòng thanh toán đúng hạn.</p>',
            'priority' => 'normal', 'status' => 'published', 'published_at' => Carbon::parse('2026-06-30 08:00'),
            'created_by_id' => $admin->id, 'published_by_id' => $admin->id,
        ]);
        \App\Models\NotificationAudience::create(['notification_id' => $ten->id, 'scope_type' => 'project', 'scope_id' => $project->id]);
        \App\Models\NotificationChannel::create(['notification_id' => $ten->id, 'channel' => 'app']);

        // Project — BQL, gửi trong tòa. (published / scheduled / draft)
        $projNotifs = [
            ['NTF-BQL-001', 'maintenance', 'Tạm ngưng cấp nước tầng 5–8', 'published', 'high', Carbon::parse('2026-06-29 07:00')],
            ['NTF-BQL-002', 'emergency', 'Diễn tập PCCC toàn tòa', 'scheduled', 'urgent', Carbon::parse('2026-07-05 09:00')],
            ['NTF-BQL-003', 'community', 'Hội chợ cộng đồng cuối tháng', 'draft', 'low', null],
        ];
        $published = null;
        foreach ($projNotifs as [$code, $type, $title, $status, $priority, $pubAt]) {
            $n = \App\Models\Notification::create([
                'tenant_id' => $tenant->id, 'owner_level' => 'project', 'project_id' => $project->id, 'building_id' => $building->id,
                'code' => $code, 'type' => $type, 'title' => $title,
                'summary' => $title.'.', 'body' => '<p>'.$title.'. Vui lòng theo dõi hướng dẫn của BQL.</p>',
                'priority' => $priority, 'status' => $status,
                'publish_at' => $status === 'scheduled' ? $pubAt : null,
                'published_at' => $status === 'published' ? $pubAt : null,
                'created_by_id' => $handler->id, 'published_by_id' => $status === 'published' ? $handler->id : null,
            ]);
            \App\Models\NotificationAudience::create(['notification_id' => $n->id, 'scope_type' => 'building', 'scope_id' => $building->id]);
            \App\Models\NotificationChannel::create(['notification_id' => $n->id, 'channel' => 'app']);
            if ($status === 'published') {
                $published = $n;
            }
        }
        // Đọc + nhật ký gửi cho 1 thông báo đã phát hành (per resident).
        if ($published) {
            $recipients = 0;
            foreach ($residents as $i => $res) {
                $recipients++;
                \App\Models\NotificationDeliveryLog::create([
                    'notification_id' => $published->id, 'resident_id' => $res->id, 'channel' => 'app',
                    'status' => 'sent', 'sent_at' => Carbon::parse('2026-06-29 07:05'),
                ]);
                if ($i % 2 === 0) {
                    \App\Models\NotificationRead::create([
                        'notification_id' => $published->id, 'resident_id' => $res->id,
                        'read_at' => Carbon::parse('2026-06-29 08:00')->addMinutes($i * 3),
                    ]);
                }
            }
            $published->update(['recipient_count' => $recipients, 'read_count' => (int) ceil($recipients / 2)]);
        }

        // ============ AMENITIES + BOOKINGS ============
        $amenityDefs = [
            ['GYM', 'Phòng Gym', 'gym', 30, '05:00', '22:00', 0, false],
            ['POOL', 'Hồ bơi', 'pool', 40, '06:00', '21:00', 50000, false],
            ['BBQ', 'Khu BBQ sân thượng', 'bbq', 12, '09:00', '22:00', 200000, true],
            ['HALL', 'Phòng sinh hoạt cộng đồng', 'function_room', 60, '08:00', '22:00', 300000, true],
        ];
        $amenities = [];
        foreach ($amenityDefs as [$code, $name, $type, $cap, $open, $close, $price, $needApprove]) {
            $a = \App\Models\Amenity::create([
                'tenant_id' => $tenant->id, 'project_id' => $project->id, 'building_id' => $building->id,
                'code' => $code, 'name' => $name, 'type' => $type, 'capacity' => $cap,
                'open_time' => $open, 'close_time' => $close, 'price' => $price,
                'requires_approval' => $needApprove, 'status' => 'active',
                'description' => $name.' phục vụ cư dân nội khu.',
            ]);
            foreach ([['08:00', '10:00'], ['18:00', '20:00']] as [$s, $e]) {
                \App\Models\AmenitySlot::create(['amenity_id' => $a->id, 'start_time' => $s, 'end_time' => $e, 'capacity' => (int) ($cap / 2)]);
            }
            $amenities[] = $a;
        }
        $statuses = ['confirmed', 'pending', 'completed', 'cancelled', 'rejected', 'confirmed'];
        foreach ($statuses as $i => $st) {
            $a = $amenities[$i % count($amenities)];
            $res = $residents[$i % max(1, $residents->count())] ?? null;
            $apt = $apts[$i % max(1, $apts->count())] ?? null;
            $date = Carbon::parse('2026-07-02')->addDays($i);
            $bk = \App\Models\AmenityBooking::create([
                'tenant_id' => $tenant->id, 'building_id' => $building->id, 'amenity_id' => $a->id,
                'apartment_id' => $apt?->id, 'resident_id' => $res?->id, 'code' => 'BK-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'booking_date' => $date, 'start_time' => '18:00', 'end_time' => '20:00', 'party_size' => 2 + $i,
                'status' => $st, 'price' => $a->price,
                'approved_by_id' => in_array($st, ['confirmed', 'completed'], true) ? $handler->id : null,
                'approved_at' => in_array($st, ['confirmed', 'completed'], true) ? $date->copy()->subDay() : null,
            ]);
            if (in_array($st, ['confirmed', 'completed'], true)) {
                \App\Models\BookingQrPass::create([
                    'amenity_booking_id' => $bk->id, 'code' => 'QR-BK-'.strtoupper(substr(md5($bk->id.'booking'), 0, 10)),
                    'valid_from' => $date->copy()->setTime(17, 30), 'valid_to' => $date->copy()->setTime(20, 30),
                    'status' => $st === 'completed' ? 'used' : 'active',
                ]);
            }
        }

        // ============ FEEDBACK children (làm giàu vài phản ánh có sẵn) ============
        $reqs = FeedbackRequest::where('tenant_id', $tenant->id)->orderBy('id')->take(6)->get();
        foreach ($reqs as $i => $req) {
            $res = $residents[$i % max(1, $residents->count())] ?? null;
            $apt = $apts[$i % max(1, $apts->count())] ?? null;
            $req->update([
                'project_id' => $project->id, 'resident_id' => $res?->id, 'apartment_id' => $apt?->id,
                'code' => 'FB-'.str_pad((string) ($req->id), 5, '0', STR_PAD_LEFT),
                'description' => 'Chi tiết phản ánh: '.$req->title.'. Mong BQL xử lý sớm.',
                'channel' => ['app', 'web', 'hotline'][$i % 3],
                'assigned_to_id' => $handler->id, 'sla_due_at' => Carbon::parse('2026-07-01')->addDays(2),
            ]);
            \App\Models\FeedbackComment::create([
                'feedback_request_id' => $req->id, 'resident_id' => $res?->id, 'author_name' => $res?->full_name ?? 'Cư dân',
                'body' => 'Sự việc xảy ra từ hôm qua, mong được hỗ trợ.', 'is_internal' => false,
            ]);
            \App\Models\FeedbackComment::create([
                'feedback_request_id' => $req->id, 'user_id' => $handler->id, 'author_name' => $handler->name,
                'body' => 'Đã tiếp nhận, phân công kỹ thuật kiểm tra.', 'is_internal' => true,
            ]);
            \App\Models\FeedbackAssignment::create([
                'feedback_request_id' => $req->id, 'assigned_to_id' => $handler->id, 'assigned_by_id' => $admin->id,
                'status' => 'assigned', 'note' => 'Ưu tiên trong ngày', 'assigned_at' => Carbon::parse('2026-07-01 09:00'),
            ]);
            \App\Models\FeedbackStatusHistory::create([
                'feedback_request_id' => $req->id, 'from_status' => 'new', 'to_status' => 'assigned',
                'changed_by_id' => $admin->id, 'note' => 'Tự động phân công', 'changed_at' => Carbon::parse('2026-07-01 09:00'),
            ]);
            if ($i % 2 === 0) {
                \App\Models\FeedbackAttachment::create([
                    'feedback_request_id' => $req->id, 'path' => 'feedback/demo-'.$req->id.'.jpg',
                    'name' => 'hien-truong-'.$req->id.'.jpg', 'mime' => 'image/jpeg', 'size' => 240000,
                    'uploaded_by_id' => $res?->user_id,
                ]);
            }
        }

        // ============ VISITORS ============
        $visitorDefs = [
            ['Trần Văn Khách', '0909111222', 'approved', 'Thăm người thân'],
            ['Lê Thị Giao Hàng', '0912333444', 'checked_in', 'Giao hàng'],
            ['Phạm Đối Tác', '0987654321', 'pending', 'Họp'],
            ['Nguyễn Sửa Chữa', '0900112233', 'checked_out', 'Sửa điều hòa'],
        ];
        foreach ($visitorDefs as $i => [$name, $phone, $st, $purpose]) {
            $res = $residents[$i % max(1, $residents->count())] ?? null;
            $apt = $apts[$i % max(1, $apts->count())] ?? null;
            $reg = \App\Models\VisitorRegistration::create([
                'tenant_id' => $tenant->id, 'project_id' => $project->id, 'building_id' => $building->id,
                'apartment_id' => $apt?->id, 'resident_id' => $res?->id, 'host_user_id' => null,
                'code' => 'VS-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'visitor_name' => $name, 'visitor_phone' => $phone, 'purpose' => $purpose, 'num_guests' => 1,
                'expected_at' => Carbon::parse('2026-07-02 10:00')->addHours($i),
                'status' => $st, 'approved_by_id' => in_array($st, ['approved', 'checked_in', 'checked_out'], true) ? $handler->id : null,
            ]);
            if (in_array($st, ['approved', 'checked_in', 'checked_out'], true)) {
                \App\Models\VisitorPass::create([
                    'visitor_registration_id' => $reg->id, 'code' => 'QR-VS-'.strtoupper(substr(md5($reg->id.'visitor'), 0, 10)),
                    'valid_from' => $reg->expected_at, 'valid_to' => $reg->expected_at->copy()->addHours(6),
                    'status' => $st === 'checked_out' ? 'used' : 'active',
                    'checked_in_at' => in_array($st, ['checked_in', 'checked_out'], true) ? $reg->expected_at : null,
                    'checked_out_at' => $st === 'checked_out' ? $reg->expected_at->copy()->addHours(2) : null,
                    'gate' => 'Cổng chính',
                ]);
            }
        }

        // ============ PACKAGE DELIVERIES ============
        $carriers = ['GHTK', 'GHN', 'VNPost', 'Shopee Express', 'J&T'];
        $pkgStatus = ['received', 'notified', 'picked_up', 'received', 'notified'];
        foreach ($pkgStatus as $i => $st) {
            $apt = $apts[$i % max(1, $apts->count())] ?? null;
            $res = $residents[$i % max(1, $residents->count())] ?? null;
            \App\Models\PackageDelivery::create([
                'tenant_id' => $tenant->id, 'project_id' => $project->id, 'building_id' => $building->id,
                'apartment_id' => $apt?->id, 'resident_id' => $res?->id,
                'tracking_no' => 'TRK'.str_pad((string) (1000 + $i), 6, '0', STR_PAD_LEFT),
                'carrier' => $carriers[$i % count($carriers)], 'sender' => 'Shop online',
                'description' => 'Kiện hàng '.($i + 1), 'size' => ['small', 'medium', 'large'][$i % 3],
                'locker_no' => 'L'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT), 'status' => $st,
                'received_at' => Carbon::parse('2026-06-30 14:00')->addHours($i), 'received_by_id' => $handler->id,
                'picked_up_at' => $st === 'picked_up' ? Carbon::parse('2026-06-30 19:00')->addHours($i) : null,
                'picked_up_by' => $st === 'picked_up' ? ($res?->full_name ?? 'Cư dân') : null,
            ]);
        }
    }

    /** Batch 08 — Integration Center demo (BATCH_08_SEED_DATA_CATALOG): connections,
     *  credentials, API keys, webhooks, events, retry queue, incidents, security. */
    private function seedBatch08Integration(Tenant $tenant, User $admin): void
    {
        $secret = new \App\Support\Integration\IntegrationSecret();

        // --- categories ---
        $categoryDefs = [
            ['communication', 'Truyền thông', 1], ['payment', 'Thanh toán', 2],
            ['finance', 'Tài chính', 3], ['erp', 'ERP', 4],
            ['ai', 'AI', 5], ['custom', 'Tuỳ chỉnh', 6], ['iot', 'IoT', 7],
        ];
        $catId = [];
        foreach ($categoryDefs as [$code, $name, $sort]) {
            $catId[$code] = \App\Models\IntegrationCategory::create([
                'code' => $code, 'name' => $name, 'is_active' => true, 'sort_order' => $sort,
            ])->id;
        }

        // --- connections + credential + checks ---
        $connections = [
            ['CONN-ZALO-ZNS', 'Zalo ZNS', 'communication', 'production', 'active', 'v3.2', 99.8, 186, 'valid', 'healthy'],
            ['CONN-SMS-BRANDNAME', 'SMS Brandname', 'communication', 'production', 'active', 'v1.1', 99.5, 220, 'valid', 'healthy'],
            ['CONN-EMAIL-SMTP', 'Email SMTP', 'communication', 'production', 'active', 'v1.0', 99.9, 312, 'valid', 'healthy'],
            ['CONN-FCM-PUSH', 'FCM Push', 'communication', 'production', 'active', 'v1.0', 98.3, 284, 'valid', 'warning'],
            ['CONN-VNPAY', 'VNPay Payment Gateway', 'payment', 'production', 'active', 'v2.1', 99.4, 420, 'valid', 'healthy'],
            ['CONN-BANK-STATEMENT', 'Ngân hàng / Sao kê', 'finance', 'production', 'warning', 'v1.3', 96.2, 680, 'valid', 'warning'],
            ['CONN-EINVOICE', 'Hóa đơn điện tử', 'finance', 'production', 'active', 'v2.0', 99.1, 355, 'valid', 'healthy'],
            ['CONN-OPENAI', 'OpenAI', 'ai', 'production', 'active', 'v1', 99.9, 942, 'valid', 'healthy'],
            ['CONN-CLAUDE', 'Claude API', 'ai', 'staging', 'warning', 'v1', 97.1, 1100, 'expiring', 'warning'],
            ['CONN-ODOO', 'Odoo ERP', 'erp', 'staging', 'disabled', 'v16', 92.1, 1256, 'valid', 'incident'],
            ['CONN-BRAVO', 'Bravo ERP', 'erp', 'production', 'active', 'v1.0', 99.0, 611, 'valid', 'healthy'],
            ['CONN-WEBHOOK-GATEWAY', 'Webhook Gateway', 'custom', 'production', 'incident', 'v1.0', 97.8, 198, 'valid', 'warning'],
        ];
        foreach ($connections as $i => [$code, $name, $cat, $env, $status, $ver, $rate, $lat, $credStatus, $sla]) {
            $conn = \App\Models\IntegrationConnection::create([
                'code' => $code, 'name' => $name, 'category_id' => $catId[$cat] ?? null,
                'provider_code' => strtolower(str_replace('CONN-', '', $code)),
                'environment' => $env, 'status' => $status, 'api_version' => $ver,
                'base_url' => 'https://api.'.strtolower(str_replace(['CONN-', '-'], ['', ''], $code)).'.example',
                'owner_user_id' => $admin->id, 'timeout_seconds' => 30,
                'retry_policy' => 'exponential_5_attempts', 'idempotency_enabled' => true,
                'last_checked_at' => Carbon::parse('2026-07-07 10:00')->subMinutes($i * 3),
                'success_rate_24h' => $rate, 'avg_latency_ms' => $lat, 'sla_status' => $sla,
            ]);

            $plain = $secret->generateApiSecret();
            \App\Models\IntegrationCredential::create([
                'connection_id' => $conn->id, 'credential_type' => 'api_key',
                'encrypted_payload' => $secret->encrypt($plain), 'masked_summary' => $secret->mask($plain),
                'status' => $credStatus,
                'expires_at' => $credStatus === 'expiring' ? Carbon::parse('2026-07-20') : Carbon::parse('2026-12-31'),
                'created_by' => $admin->id,
            ]);

            foreach ([['success', 200], ['success', 200], [$status === 'incident' ? 'failed' : 'success', $status === 'incident' ? 500 : 200]] as $j => [$cs, $http]) {
                \App\Models\IntegrationConnectionCheck::create([
                    'connection_id' => $conn->id, 'status' => $cs, 'latency_ms' => $lat + $j * 10,
                    'http_status' => $http, 'message' => $cs === 'success' ? 'OK' : 'Connection timeout',
                    'checked_at' => Carbon::parse('2026-07-07 10:00')->subHours($j), 'checked_by' => $admin->id,
                    'created_at' => Carbon::parse('2026-07-07 10:00')->subHours($j),
                ]);
            }

            if ($code === 'CONN-VNPAY') {
                \App\Models\IntegrationMapping::create([
                    'connection_id' => $conn->id, 'mapping_type' => 'status',
                    'source_event' => 'vnpay.payment.success', 'target_event' => 'payment.paid',
                    'mapping_json' => ['00' => 'paid', '01' => 'pending', '02' => 'failed'],
                    'version' => 2, 'status' => 'active', 'created_by' => $admin->id, 'updated_by' => $admin->id,
                ]);
            }
        }

        // --- API keys + scopes ---
        $apiKeys = [
            ['Mobile Resident App', 'clt_8f3d7a9c', ['resident:read', 'messaging:read'], 'production', 'active', '2026-09-30', 600, true, true],
            ['Web Admin Portal', 'clt_a7c1e2b4', ['admin:full', 'device:manage'], 'production', 'active', '2026-11-15', 1200, true, true],
            ['Webhook Relay', 'clt_5b8e3d11', ['events:read', 'webhooks:write'], 'production', 'active', '2026-08-01', 1000, true, false],
            ['AI Simulator', 'clt_d2f94a66', ['device:read', 'telemetry:write'], 'staging', 'expiring', '2026-07-10', 300, false, false],
        ];
        foreach ($apiKeys as $i => [$name, $clientId, $scopes, $env, $status, $exp, $rl, $hmac, $ipReq]) {
            $plain = $secret->generateApiSecret();
            $key = \App\Models\IntegrationApiKey::create([
                'name' => $name, 'client_id' => $clientId, 'secret_hash' => $secret->hash($plain),
                'environment' => $env, 'status' => $status, 'expires_at' => Carbon::parse($exp),
                'last_used_at' => Carbon::parse('2026-07-07 10:00')->subMinutes($i * 20 + 12),
                'owner_user_id' => $admin->id, 'rate_limit_per_minute' => $rl,
                'require_hmac' => $hmac, 'require_ip_allowlist' => $ipReq,
                'allowed_ips_json' => $ipReq ? ['203.0.113.10/32', '103.56.12.0/24'] : null,
                'metadata_json' => ['secret_masked' => $secret->mask($plain)],
            ]);
            foreach ($scopes as $sc) {
                [$res, $lvl] = array_pad(explode(':', $sc), 2, 'read');
                \App\Models\IntegrationApiKeyScope::create([
                    'api_key_id' => $key->id, 'scope_code' => $sc,
                    'scope_name' => ucfirst($res).' '.$lvl, 'permission_level' => $lvl,
                ]);
            }
        }

        // --- webhook event groups ---
        $groupId = [];
        foreach (['payment', 'notification', 'work_order', 'billing', 'resident', 'access_control', 'maintenance', 'energy', 'parking', 'logistics', 'feedback', 'system'] as $gc) {
            $groupId[$gc] = \App\Models\WebhookEventGroup::create([
                'code' => $gc, 'name' => ucwords(str_replace('_', ' ', $gc)), 'is_active' => true,
            ])->id;
        }

        // --- webhook endpoints + deliveries ---
        $webhooks = [
            ['WH-PAYMENT-STATUS', '/api/v1/payment-status', 'https://api.partner.com/webhook/payment-status', 'payment', 'HMAC', 'active', 99.8, 'Partner A'],
            ['WH-PUSH', '/api/v1/push', 'https://push.example.com/webhook', 'notification', 'HMAC', 'active', 99.5, 'NotifyOne'],
            ['WH-WORK-ORDER', '/api/v1/work-order', 'https://bms.partner.com/callback/work-order', 'work_order', 'HMAC', 'active', 98.7, 'BMS Partner'],
            ['WH-INVOICE-PAID', '/api/v1/invoice-paid', 'https://billing.partner.com/webhook/invoice-paid', 'billing', 'HMAC', 'warning', 94.1, 'Billing Ltd.'],
            ['WH-FEEDBACK', '/api/v1/feedback-submitted', 'https://survey.app.com/webhook/feedback', 'feedback', 'none', 'disabled', null, 'Survey App'],
        ];
        foreach ($webhooks as $i => [$code, $epName, $url, $grp, $sig, $status, $rate, $owner]) {
            $wh = \App\Models\WebhookEndpoint::create([
                'code' => $code, 'endpoint_name' => $epName, 'url' => $url,
                'event_group_id' => $groupId[$grp] ?? null, 'method' => 'POST',
                'signature_type' => $sig, 'signing_secret_hash' => $sig === 'HMAC' ? $secret->hash($secret->generateSigningSecret()) : null,
                'status' => $status, 'success_rate' => $rate, 'retry_policy' => 'exponential_5_attempts',
                'owner_name' => $owner,
                'last_delivery_at' => $status === 'disabled' ? null : Carbon::parse('2026-07-06 10:20')->subMinutes($i * 3),
            ]);
            if ($status !== 'disabled') {
                foreach ([['success', 200], ['success', 200], [$status === 'warning' ? 'failed' : 'success', $status === 'warning' ? 500 : 200]] as $j => [$cs, $http]) {
                    \App\Models\WebhookDeliveryAttempt::create([
                        'webhook_endpoint_id' => $wh->id, 'event_id' => 'evt_'.strtoupper(\Illuminate\Support\Str::random(20)),
                        'correlation_id' => 'corr_'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(12)),
                        'payload_hash' => hash('sha256', $code.$j), 'http_status' => $http,
                        'duration_ms' => 120 + $j * 40, 'status' => $cs, 'attempt_no' => $j + 1,
                        'response_body' => $cs === 'success' ? '{"ok":true}' : '{"error":"Internal Server Error"}',
                        'error_message' => $cs === 'failed' ? 'HTTP 500 Internal Server Error' : null,
                        'delivered_at' => Carbon::parse('2026-07-06 10:20')->subMinutes($j * 5),
                        'created_at' => Carbon::parse('2026-07-06 10:20')->subMinutes($j * 5),
                    ]);
                }
            }
        }

        // --- integration events (monitor) ---
        $events = [
            ['evt_01HV8B7ZQW5B4F82YQ6KM3N2KJ', 'VNPay', 'payment.paid', 'success', 142, 0, 'Giao dịch 241028993320 thành công'],
            ['evt_01HV8Z10NV3RD2X6WQ3R6B9D', 'Webhook Gateway', 'webhook.received', 'failed', 2891, 3, 'HTTP 500 Internal Server Error'],
            ['evt_01HV8Z1M9L2D', 'Zalo ZNS', 'zns.sent', 'warning', 531, 2, 'Số điện thoại không hợp lệ'],
        ];
        $extraSources = ['SMS Brandname', 'Email SMTP', 'FCM Push', 'OpenAI', 'Bravo ERP', 'VNPay', 'Hóa đơn điện tử'];
        $extraTypes = ['sms.sent', 'email.sent', 'push.sent', 'ai.completion', 'erp.sync', 'payment.refunded', 'einvoice.issued'];
        foreach ($extraSources as $k => $src) {
            $events[] = [
                'evt_'.strtoupper(\Illuminate\Support\Str::random(20)), $src, $extraTypes[$k],
                $k % 5 === 0 ? 'warning' : 'success', 100 + $k * 37, $k % 5 === 0 ? 1 : 0, 'OK',
            ];
        }
        $failedEventId = null;
        foreach ($events as $i => [$eid, $src, $type, $status, $dur, $retry, $msg]) {
            \App\Models\IntegrationEvent::create([
                'event_id' => $eid, 'correlation_id' => 'corr_'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(12)),
                'source' => $src, 'event_type' => $type, 'tenant_id' => $tenant->id, 'status' => $status,
                'duration_ms' => $dur, 'retry_count' => $retry, 'payload_hash' => hash('sha256', $eid),
                'message' => $msg, 'created_at' => Carbon::parse('2026-07-07 10:00')->subMinutes($i * 7),
            ]);
            if ($status === 'failed') {
                $failedEventId = $eid;
            }
        }

        // --- retry queue ---
        $whGateway = \App\Models\WebhookEndpoint::where('code', 'WH-INVOICE-PAID')->value('id');
        foreach ([
            [$failedEventId, 'retrying', 2, 'HTTP 500 Internal Server Error'],
            ['evt_'.strtoupper(\Illuminate\Support\Str::random(20)), 'pending', 0, null],
            ['evt_'.strtoupper(\Illuminate\Support\Str::random(20)), 'dead_letter', 5, 'Max attempts exceeded'],
        ] as $i => [$eid, $status, $attempt, $err]) {
            \App\Models\IntegrationRetryJob::create([
                'event_id' => $eid, 'webhook_endpoint_id' => $whGateway, 'source' => 'Webhook Gateway',
                'reason' => 'delivery_failed', 'status' => $status, 'attempt_no' => $attempt, 'max_attempts' => 5,
                'next_retry_at' => $status === 'pending' || $status === 'retrying' ? Carbon::parse('2026-07-07 10:30')->addMinutes($i * 5) : null,
                'last_error' => $err,
            ]);
        }

        // --- incidents ---
        foreach ([
            ['INC-2026-0007', 'Odoo ERP đồng bộ thất bại', 'high', 'investigating', 'Odoo ERP', '2026-07-07 08:30', null],
            ['INC-2026-0006', 'Webhook Gateway latency cao', 'medium', 'resolved', 'Webhook Gateway', '2026-07-06 22:10', '2026-07-06 23:40'],
        ] as [$code, $title, $sev, $status, $src, $start, $end]) {
            \App\Models\IntegrationIncident::create([
                'code' => $code, 'title' => $title, 'severity' => $sev, 'status' => $status, 'source' => $src,
                'started_at' => Carbon::parse($start), 'resolved_at' => $end ? Carbon::parse($end) : null,
                'owner_user_id' => $admin->id, 'summary' => $title,
                'root_cause' => $status === 'resolved' ? 'Nhà cung cấp quá tải tạm thời' : null,
            ]);
        }

        // --- security policies ---
        $policies = [
            ['secret_rotation_policy', ['rotation_days' => 90, 'expiration_notice_days' => 7], true],
            ['ip_allowlist_management', ['enabled' => true], true],
            ['hmac_signature_enforcement', ['algorithm' => 'HMAC SHA256', 'signature_header' => 'X-Request-Signature', 'clock_skew_minutes' => 5], true],
            ['oauth_callback_policy', ['policy' => 'allow_registered_urls_only'], true],
            ['rate_limiting_defaults', ['default' => 1000, 'window' => '1 minute', 'burst' => 200], true],
            ['audit_retention_days', ['days' => 180], true],
            ['webhook_replay_protection', ['enabled' => true, 'nonce_ttl_minutes' => 10], true],
            ['emergency_disable_switch', ['enabled' => false], true],
        ];
        foreach ($policies as [$key, $value, $enabled]) {
            \App\Models\IntegrationSecurityPolicy::create([
                'policy_key' => $key, 'policy_value_json' => $value, 'is_enabled' => $enabled, 'updated_by' => $admin->id,
            ]);
        }

        // --- IP allowlist + rate limit ---
        foreach (['203.0.113.10/32', '203.0.113.11/32', '203.0.113.20/32', '103.56.12.0/24'] as $ip) {
            \App\Models\IntegrationIpAllowlist::create([
                'scope_type' => 'global', 'ip_or_cidr' => $ip, 'description' => 'Văn phòng HQ', 'created_by' => $admin->id,
            ]);
        }
        \App\Models\IntegrationRateLimit::create([
            'scope_type' => 'global', 'limit_per_minute' => 1000, 'burst_limit' => 200, 'window_seconds' => 60, 'is_enabled' => true,
        ]);

        // --- a couple of audit entries so the audit view is not empty ---
        $vnpay = \App\Models\IntegrationConnection::where('code', 'CONN-VNPAY')->first();
        \App\Models\IntegrationAuditLog::create([
            'actor_id' => $admin->id, 'connection_id' => $vnpay?->id, 'entity_type' => 'IntegrationConnection',
            'entity_id' => $vnpay?->id, 'action' => 'connection.tested', 'after_json' => ['status' => 'active'],
            'ip_address' => '203.0.113.10', 'created_at' => Carbon::parse('2026-07-07 09:30'),
        ]);
    }

    /** Batch 10 — Support Center demo. Con số tái hiện đúng ảnh WEB-UX-30 (dashboard +
     *  report) qua support_reports snapshot + phân bố ticket có kiểm soát. */
    private function seedBatch10Support(Tenant $tenant, User $admin): void
    {
        $tenant2 = Tenant::where('id', '!=', $tenant->id)->first() ?? $tenant;

        // SLA policies.
        foreach ([['critical', 15, 240], ['high', 30, 480], ['medium', 60, 960], ['low', 120, 2880]] as [$p, $resp, $reso]) {
            \App\Models\SupportSlaPolicy::create(['code' => 'SLA-'.strtoupper($p), 'name' => 'SLA '.$p, 'priority' => $p, 'response_minutes' => $resp, 'resolution_minutes' => $reso]);
        }

        // Teams (member counts đúng catalog).
        $teamId = [];
        foreach ([['L1', 'L1 Support', 'L1', 12, 60], ['L2', 'L2 Engineering', 'L2', 8, 120], ['DATA_FIX', 'Data Fix Team', 'L3', 5, 240], ['ACCOUNT', 'Account Manager', 'account', 4, 120]] as [$code, $name, $level, $members, $sla]) {
            $team = \App\Models\SupportTeam::create(['code' => $code, 'name' => $name, 'level' => $level, 'member_count' => $members, 'sla_target_response_minutes' => $sla]);
            $teamId[$code] = $team->id;
            for ($m = 1; $m <= $members; $m++) {
                \App\Models\SupportTeamMember::create(['support_team_id' => $team->id, 'member_name' => $name.' #'.$m, 'role' => $level, 'is_on_call' => $m === 1, 'open_tickets' => random_int(0, 8)]);
            }
        }

        // Tenant support profiles + contacts + entitlements.
        foreach ([[$tenant, 'VNPAY Headquarters', '24/7 P1', 88.4, 4.6], [$tenant2, 'Sunshine City', 'Business Hours', 91.2, 4.7]] as [$tn, $label, $plan, $health, $csat]) {
            \App\Models\TenantSupportProfile::create(['tenant_id' => $tn->id, 'support_plan' => $plan, 'tier' => 'Enterprise', 'health_score' => $health, 'csat' => $csat, 'account_manager_id' => $admin->id, 'vip_notes' => 'Khách hàng chiến lược — ưu tiên P1.']);
            \App\Models\TenantSupportContact::create(['tenant_id' => $tn->id, 'name' => 'Nguyễn Quản Trị', 'email' => 'it@'.strtolower(str_replace(' ', '', $label)).'.vn', 'phone' => '0900000000', 'role' => 'IT Manager', 'is_primary' => true]);
            foreach (['priority_support' => '24/7', 'dedicated_am' => 'Yes', 'data_fix' => 'Included'] as $c => $v) {
                \App\Models\SupportEntitlement::create(['tenant_id' => $tn->id, 'code' => $c, 'name' => ucwords(str_replace('_', ' ', $c)), 'value' => $v]);
            }
        }

        // --- 4 named tickets (đúng catalog) with timeline ---
        $named = [
            ['TKT-2025-0612', 'Lỗi hiển thị biểu đồ năng lượng', 'Reports', 'critical', 'escalated', 'breached', -192, 'Lê Hoàng Nam'],
            ['TKT-2025-0608', 'Sự cố API Integration với HVAC', 'API Gateway', 'high', 'escalated', 'breached', -25, 'Trần Minh Quân'],
            ['TKT-2025-0601', 'Webhook /push timeout', 'Webhook', 'high', 'in_progress', 'breached', -15, 'Nguyễn Thu Hà'],
            ['TKT-2025-0607', 'Sai dữ liệu công tơ điện tầng 12', 'Data Fix', 'medium', 'waiting_customer', 'paused_waiting_customer', 70, 'Võ Thị Mai Linh'],
        ];
        foreach ($named as $i => [$no, $subject, $module, $priority, $status, $slaState, $slaMin, $owner]) {
            $t = \App\Models\SupportTicket::create([
                'ticket_no' => $no, 'tenant_id' => $i % 2 ? $tenant2->id : $tenant->id, 'subject' => $subject,
                'description' => '<p>'.$subject.'.</p>', 'module' => $module, 'priority' => $priority, 'status' => $status,
                'environment' => 'production', 'sla_state' => $slaState, 'sla_due_at' => Carbon::parse('2026-07-07 10:00')->addMinutes($slaMin),
                'owner_id' => $admin->id, 'team_id' => $teamId['L2'], 'requester_name' => $owner, 'requester_contact' => 'kh@tenant.vn',
                'first_response_at' => Carbon::parse('2026-07-07 08:30'),
            ]);
            \App\Models\SupportTicketMessage::create(['support_ticket_id' => $t->id, 'author_id' => $admin->id, 'author_name' => 'Support', 'type' => 'customer', 'body' => '<p>'.$subject.'</p>']);
            \App\Models\SupportTicketMessage::create(['support_ticket_id' => $t->id, 'author_id' => $admin->id, 'author_name' => 'Support', 'type' => 'internal', 'body' => '<p>Đã tiếp nhận, đang điều tra.</p>']);
            \App\Models\SupportTicketStatusLog::create(['support_ticket_id' => $t->id, 'from_status' => 'new', 'to_status' => $status, 'changed_by' => $admin->id, 'created_at' => now()]);
        }

        // --- filler tickets to reproduce exact priority distribution (Critical 12 / High 46 / Medium 132 / Low 120 = 310) ---
        // Named đã có: 1 critical, 2 high, 1 medium → còn: 11 crit, 44 high, 131 medium, 120 low = 306.
        $fillerPlan = ['critical' => 11, 'high' => 44, 'medium' => 131, 'low' => 120];
        $escalatedRemaining = 26;  // named có 2 → tổng 28
        $nearBreachRemaining = 37;
        $seq = 500;
        $rows = [];
        foreach ($fillerPlan as $priority => $count) {
            for ($k = 0; $k < $count; $k++) {
                $status = 'open';
                $slaState = 'within_sla';
                if ($escalatedRemaining > 0 && in_array($priority, ['critical', 'high'], true)) {
                    $status = 'escalated';
                    $escalatedRemaining--;
                } elseif ($nearBreachRemaining > 0) {
                    $status = 'in_progress';
                    $slaState = 'near_breach';
                    $nearBreachRemaining--;
                } elseif ($k % 3 === 0) {
                    $status = 'resolved';
                    $slaState = 'resolved';
                }
                $rows[] = [
                    'ticket_no' => 'TKT-2025-'.($seq++), 'tenant_id' => $tenant->id,
                    'subject' => 'Yêu cầu hỗ trợ #'.$seq, 'module' => 'General', 'priority' => $priority,
                    'status' => $status, 'environment' => 'production', 'sla_state' => $slaState,
                    'owner_id' => $admin->id, 'csat_score' => $status === 'resolved' ? 4.6 : null,
                    'resolved_at' => $status === 'resolved' ? Carbon::parse('2026-07-06 12:00') : null,
                    'created_at' => now(), 'updated_at' => now(),
                ];
            }
        }
        foreach (array_chunk($rows, 100) as $chunk) {
            \App\Models\SupportTicket::insert($chunk);
        }

        // Escalation events (2 active — đúng catalog).
        foreach (['TKT-2025-0612' => 'Chưa xử lý sau 30 phút', 'TKT-2025-0608' => 'Khách hàng phản hồi tiêu cực'] as $no => $reason) {
            $tk = \App\Models\SupportTicket::where('ticket_no', $no)->first();
            \App\Models\SupportEscalation::create(['support_ticket_id' => $tk->id, 'from_level' => 'L1', 'to_level' => 'L2', 'reason' => $reason, 'status' => 'active', 'escalated_by' => $admin->id]);
        }

        // --- Data correction requests (đúng catalog) ---
        $dcrDefs = [
            ['DCR-2026-0612', 'Khách hàng', 312, 'critical', 'pending_approval'],
            ['DCR-2026-0611', 'Hóa đơn', 48, 'high', 'approved'],
            ['DCR-2026-0610', 'Hợp đồng', 23, 'medium', 'executed'],
        ];
        foreach ($dcrDefs as $i => [$code, $type, $records, $risk, $status]) {
            $dcr = \App\Models\DataCorrectionRequest::create([
                'code' => $code, 'tenant_id' => $tenant->id, 'data_type' => $type, 'target_entity' => 'residents',
                'affected_records' => $records, 'risk' => $risk, 'status' => $status,
                'reason' => '<p>Đối soát và sửa dữ liệu '.$type.' sai lệch.</p>', 'rollback_plan' => '<p>Khôi phục từ snapshot.</p>',
                'requested_by' => $admin->id, 'approver_id' => $admin->id,
                'approved_at' => in_array($status, ['approved', 'executed'], true) ? Carbon::parse('2026-07-06 09:00') : null,
                'execution_window_at' => Carbon::parse('2026-07-08 22:00'),
            ]);
            for ($r = 0; $r < min($records, 5); $r++) {
                \App\Models\DataCorrectionAffectedRecord::create(['data_correction_request_id' => $dcr->id, 'entity' => 'residents', 'record_id' => (string) ($r + 1), 'identifier' => 'REC-'.($r + 1)]);
            }
            if (in_array($status, ['approved', 'executed'], true)) {
                \App\Models\DataFixSnapshot::create(['data_correction_request_id' => $dcr->id, 'snapshot_json' => ['sample' => 'before'], 'record_count' => $records, 'created_by' => $admin->id, 'created_at' => now()]);
                \App\Models\DataFixDiffItem::create(['data_correction_request_id' => $dcr->id, 'entity' => 'residents', 'record_id' => '1', 'field' => 'id_no', 'before_value' => '0123', 'after_value' => '079xxxxx']);
                \App\Models\DataFixApproval::create(['data_correction_request_id' => $dcr->id, 'approver_id' => $admin->id, 'decision' => 'approved', 'reason' => 'Đã kiểm chứng', 'approved_at' => Carbon::parse('2026-07-06 09:00')]);
            }
            if ($status === 'executed') {
                \App\Models\DataFixExecution::create(['data_correction_request_id' => $dcr->id, 'executed_by' => $admin->id, 'status' => 'executed', 'affected_count' => $records, 'executed_at' => Carbon::parse('2026-07-06 22:30'), 'log' => 'OK']);
            }
        }

        // --- KB categories + articles (rating/views đúng catalog) ---
        $kbCat = [];
        foreach (['Onboarding & Tenant Setup', 'Integration', 'Data Correction'] as $i => $cn) {
            $kbCat[$cn] = \App\Models\SupportKbCategory::create(['code' => 'KBC-'.($i + 1), 'name' => $cn, 'sort_order' => $i + 1])->id;
        }
        foreach ([
            ['KB-SUP-001', 'SOP: Tạo tenant mới và cấu hình gói dịch vụ', 'Onboarding & Tenant Setup', 4.8, 1240],
            ['KB-SUP-002', 'Runbook: Xử lý lỗi không gửi được email SMTP', 'Integration', 4.7, 952],
            ['KB-SUP-003', 'SOP: Reset webhook khi nhận lỗi 5xx', 'Integration', 4.6, 812],
            ['KB-SUP-004', 'Data Fix: Đối soát và sửa dữ liệu billing sai lệch', 'Data Correction', 4.9, 743],
        ] as [$code, $title, $cat, $rating, $views]) {
            $art = \App\Models\SupportKbArticle::create([
                'code' => $code, 'title' => $title, 'category_id' => $kbCat[$cat] ?? null,
                'body' => '<h2>'.$title.'</h2><p>Nội dung hướng dẫn chi tiết.</p>', 'status' => 'published',
                'rating' => $rating, 'views' => $views, 'author_id' => $admin->id, 'published_at' => Carbon::parse('2026-06-01'),
            ]);
            \App\Models\SupportKbArticleVersion::create(['support_kb_article_id' => $art->id, 'version' => 1, 'body' => $art->body, 'editor_id' => $admin->id, 'created_at' => now()]);
        }

        // --- Report snapshots (exact numbers) ---
        \App\Models\SupportReport::create([
            'code' => 'RPT-2026-06', 'period' => '2026-06', 'type' => 'resolution', 'generated_by' => $admin->id,
            'metrics_json' => ['tickets_resolved' => 1248, 'mttr' => '14h 36m', 'sla_compliance' => 96.8, 'data_fixes' => 312, 'rollbacks' => 24, 'csat' => 4.7],
        ]);
        \App\Models\SupportReport::create([
            'code' => 'DASH-CURRENT', 'period' => '2026-07', 'type' => 'dashboard_snapshot', 'generated_by' => $admin->id,
            'metrics_json' => ['sla_compliance' => 88.4, 'breach_rate' => 11.6, 'open_escalations' => 28, 'near_breach' => 37, 'csat' => 4.6, 'data_corrections_open' => 1],
        ]);

        \App\Models\SupportAuditLog::create(['actor_id' => $admin->id, 'tenant_id' => $tenant->id, 'entity_type' => 'DataCorrectionRequest', 'entity_id' => '1', 'action' => 'data_correction.created', 'ip_address' => '10.0.0.1', 'created_at' => now()]);
    }
}
