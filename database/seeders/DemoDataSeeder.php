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
        $this->seedSecondaryBuilding($tenant, $project, $firstNames);
        $this->seedSecondProject($tenant, $firstNames);
        $this->seedCrossCompanyResident($tenant, $apartments[10]);
        $this->seedApprovalQueueRuns($tenant, $project, $admin);
        $this->seedAiEngine($tenant, $project, $building, $admin);
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
                \App\Models\KnowledgeArticle::create([
                    'tenant_id' => $tenant->id,
                    'knowledge_category_id' => $cat->id,
                    'title' => $title, 'slug' => \Illuminate\Support\Str::slug($title),
                    'excerpt' => $title.' — hướng dẫn chi tiết cho cư dân và BQL.',
                    'body' => '<p>Nội dung hướng dẫn cho "'.$title.'".</p>',
                    'status' => $status,
                    'views' => 120 + ($idx * 137 % 4200),
                    'helpful_count' => 30 + ($idx * 17 % 280),
                    'not_helpful_count' => $idx * 3 % 24,
                    'author_id' => $admin->id,
                    'published_at' => $status === 'published' ? Carbon::parse('2026-05-01')->addDays($idx * 3) : null,
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
        $types = [
            ['QL', 'Phí quản lý', 'management', 'per_sqm', 16_500, 'Đồng/m²/tháng'],
            ['OTO', 'Phí gửi ô tô', 'parking', 'per_vehicle', 1_200_000, 'Đồng/xe/tháng'],
            ['XEMAY', 'Phí gửi xe máy', 'parking', 'per_vehicle', 120_000, 'Đồng/xe/tháng'],
            ['NUOC', 'Phí nước sinh hoạt', 'utility', 'per_m3', 15_000, 'Đồng/m³'],
            ['RAC', 'Phí vệ sinh', 'service', 'fixed', 50_000, 'Đồng/tháng'],
        ];

        $managementType = null;
        $managementRate = null;
        foreach ($types as [$code, $name, $cat, $unit, $amount, $note]) {
            $feeType = \App\Models\FeeType::create([
                'tenant_id' => $tenant->id,
                'code' => $code,
                'name' => $name,
                'category' => $cat,
                'unit' => $unit,
                'is_recurring' => true,
                'status' => 'active',
                'note' => $note,
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
}
