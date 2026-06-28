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
        $tenant = Tenant::create(['code' => 'T-X2-DEMO', 'name' => 'X2-BMS Demo Tenant']);
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => 'SUNSHINE-GARDEN', 'name' => 'Sunshine Garden']);
        $building = Building::create([
            'tenant_id' => $tenant->id,
            'project_id' => $project->id,
            'code' => 'SG-A',
            'name' => 'Sunshine Garden - Tòa A',
            'apartment_count' => 120,
        ]);

        $scope = ['tenant_id' => $tenant->id, 'building_id' => $building->id];

        // --- Admin user (login for Filament + dashboard) ---
        $admin = User::create([
            'tenant_id' => $tenant->id,
            'building_id' => $building->id,
            'name' => 'Nguyễn Minh Anh',
            'title' => 'Trưởng BQL',
            'is_platform_admin' => true,
            'email' => 'x2bms@x2bms.vn',
            'password' => Hash::make('Bms@2026!'),
            'email_verified_at' => now(),
        ]);

        // RBAC: super_admin (Shield grants all abilities via Gate::before) + operational roles.
        $superAdmin = \Spatie\Permission\Models\Role::findOrCreate('super_admin', 'web');
        $admin->assignRole($superAdmin);
        foreach (['bql_manager', 'accountant', 'technician', 'security', 'resident_service'] as $role) {
            \Spatie\Permission\Models\Role::findOrCreate($role, 'web');
        }

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
    }
}
