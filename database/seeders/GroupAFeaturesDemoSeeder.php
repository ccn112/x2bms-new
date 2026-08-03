<?php

namespace Database\Seeders;

use App\Models\Apartment;
use App\Models\Building;
use App\Models\BillingPeriod;
use App\Models\FeeType;
use App\Models\LiabilityPeriod;
use App\Models\Notification;
use App\Models\NotificationAudience;
use App\Models\NotificationChannel;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\Statement;
use App\Models\StatementLine;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Demo NHÓM A (A1–A4) cho hai tài khoản test — để kiểm bằng mắt trên máy thật:
 *
 *  A1 — Cờ non-selectable:
 *    · Kỳ chịu-trách-nhiệm role=former_owner phủ 01→04/2026 (D11/D12).
 *    · Một dòng "Phí quản lý" CÒN NỢ kỳ 03/2026 → rơi vào kỳ chủ cũ → khoá tick,
 *      hiện "Nợ của chủ cũ" trong màn Công nợ theo dịch vụ (tương phản với các
 *      tháng 05/06/07 của DebtByServiceDemoSeeder vẫn trả được).
 *    · Một dòng "Phí quản lý" ĐÃ TRẢ ĐỦ trên bảng kê 07/2026 → chi tiết bảng kê
 *      đánh dấu "Đã thanh toán" (reason=paid).
 *
 *  A3 — requires_ack: một thông báo KHẨN (PCCC) yêu cầu xác nhận đã tiếp nhận
 *    → màn chi tiết hiện nút "Tôi đã tiếp nhận".
 *
 *  A4 — tách nguồn: một thông báo source='interaction' (giả lập bình luận) → CHỈ
 *    hiện ở Hộp thư hợp nhất (chuông), KHÔNG vào màn "Thông báo BQL".
 *
 * Idempotent (firstOrCreate theo code/khoá). PHỤ THUỘC: chạy SAU
 * CommunityTestResidentsSeeder + DebtByServiceDemoSeeder (để hai TK đã có căn hộ).
 */
class GroupAFeaturesDemoSeeder extends Seeder
{
    private const ACCOUNTS = ['test.cudan1@x2bms.vn', 'test.cudan2@x2bms.vn'];

    /** Kỳ nợ thuộc CHỦ CŨ (nằm trong former_owner 01→04/2026). */
    private const FORMER_PERIOD = '2026-03';

    public function run(): void
    {
        foreach (self::ACCOUNTS as $email) {
            $ctx = $this->resolveContext($email);
            if ($ctx === null) {
                $this->command?->warn("Bỏ qua {$email}: chưa có căn hộ. Chạy CommunityTestResidentsSeeder + DebtByServiceDemoSeeder trước.");

                continue;
            }
            [$apartment, $building, $resident, $tenantId, $projectId] = $ctx;

            $this->seedFormerOwnerDebt($apartment, $building, $tenantId);
            $this->seedPaidLine($apartment, $building, $tenantId);
            $this->seedAckNotification($apartment, $tenantId, $projectId);
            $this->seedInteractionNotification($apartment, $tenantId, $projectId);

            $this->command?->info("Nhóm A demo cho {$email} (căn #{$apartment->id}): A1 nợ chủ cũ + đã trả · A3 ack · A4 interaction.");
        }
    }

    /** A1 — dòng nợ chủ cũ + kỳ liability former_owner phủ nó. */
    private function seedFormerOwnerDebt(Apartment $apartment, Building $building, int $tenantId): void
    {
        LiabilityPeriod::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenantId, 'apartment_id' => $apartment->id, 'role' => 'former_owner'],
            [
                'liable_from' => '2026-01-01',
                'liable_to' => '2026-04-30',   // đóng → chủ cũ chỉ chịu tới hết 04/2026
                'scope' => null,               // mọi family
                'source' => 'demo',
            ],
        );

        $fee = $this->managementFee($tenantId);
        $start = Carbon::parse(self::FORMER_PERIOD.'-01')->startOfMonth();
        $statement = $this->publishedStatement($apartment, $building, $tenantId, self::FORMER_PERIOD);

        StatementLine::withoutGlobalScopes()->firstOrCreate(
            [
                'statement_id' => $statement->id,
                'fee_type_id' => $fee->id,
                'service_period_start' => $start->toDateString(),
            ],
            [
                'fee_type' => 'Phí quản lý',
                'fee_category' => 'management',
                'service_period_end' => $start->copy()->endOfMonth()->toDateString(),
                'due_date' => $start->copy()->endOfMonth()->toDateString(),
                'amount' => 500_000,
                'paid_amount' => 0,   // còn nợ → vào cây, nhưng bị khoá vì chủ cũ
            ],
        );
    }

    /** A1 — dòng ĐÃ TRẢ ĐỦ trên bảng kê 07/2026 → chi tiết đánh dấu "Đã thanh toán". */
    private function seedPaidLine(Apartment $apartment, Building $building, int $tenantId): void
    {
        $fee = $this->managementFee($tenantId);
        $start = Carbon::parse('2026-07-01')->startOfMonth();
        $statement = $this->publishedStatement($apartment, $building, $tenantId, '2026-07');

        StatementLine::withoutGlobalScopes()->firstOrCreate(
            [
                'statement_id' => $statement->id,
                'fee_type_id' => $fee->id,
                'service_period_start' => $start->toDateString(),
            ],
            [
                'fee_type' => 'Phí quản lý',
                'fee_category' => 'management',
                'service_period_end' => $start->copy()->endOfMonth()->toDateString(),
                'amount' => 500_000,
                'paid_amount' => 500_000,   // đã trả đủ → reason=paid ở chi tiết
            ],
        );
    }

    /** A3 — thông báo khẩn requires_ack. */
    private function seedAckNotification(Apartment $apartment, int $tenantId, ?int $projectId): void
    {
        $this->publishNotification(
            code: 'DEMO-A-ACK-'.$apartment->id,
            tenantId: $tenantId,
            projectId: $projectId,
            apartmentId: $apartment->id,
            type: 'emergency',
            source: 'bql',
            title: 'Diễn tập PCCC toà nhà — 08:00 Chủ nhật',
            summary: 'Đề nghị cư dân xác nhận đã tiếp nhận thông báo diễn tập.',
            requiresAck: true,
            channels: ['app', 'push'],
        );
    }

    /** A4 — item tương tác (source=interaction) → chỉ vào chuông, không vào màn BQL. */
    private function seedInteractionNotification(Apartment $apartment, int $tenantId, ?int $projectId): void
    {
        $this->publishNotification(
            code: 'DEMO-A-INTERACTION-'.$apartment->id,
            tenantId: $tenantId,
            projectId: $projectId,
            apartmentId: $apartment->id,
            type: 'community',
            source: 'interaction',
            title: 'Có người bình luận bài viết của bạn',
            summary: 'Nhấn để xem bình luận mới trong cộng đồng.',
            requiresAck: false,
            channels: ['app'],
        );
    }

    // ── helpers ────────────────────────────────────────────────────────────────

    private function managementFee(int $tenantId): FeeType
    {
        return FeeType::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenantId, 'code' => 'QLDV'],
            ['name' => 'Phí quản lý', 'category' => 'management', 'unit' => 'per_sqm',
                'is_recurring' => true, 'status' => 'active', 'payment_priority' => 100],
        );
    }

    private function publishedStatement(Apartment $apartment, Building $building, int $tenantId, string $ym): Statement
    {
        $start = Carbon::parse($ym.'-01')->startOfMonth();
        $period = BillingPeriod::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenantId, 'building_id' => $building->id, 'code' => $ym],
            ['label' => 'Tháng '.$start->format('n/Y'), 'period_month' => $start->toDateString()],
        );

        $statement = Statement::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenantId, 'building_id' => $building->id,
                'apartment_id' => $apartment->id, 'billing_period_id' => $period->id],
            ['total_amount' => 0, 'paid_amount' => 0, 'status' => 'issued',
                'approval_status' => Statement::APPROVAL_PUBLISHED,
                'published_at' => $start->copy()->addDays(2)->setTime(9, 0)],
        );
        if ($statement->approval_status !== Statement::APPROVAL_PUBLISHED || $statement->published_at === null) {
            $statement->forceFill([
                'approval_status' => Statement::APPROVAL_PUBLISHED,
                'published_at' => $start->copy()->addDays(2)->setTime(9, 0),
            ])->saveQuietly();
        }

        return $statement;
    }

    private function publishNotification(
        string $code, int $tenantId, ?int $projectId, int $apartmentId,
        string $type, string $source, string $title, string $summary,
        bool $requiresAck, array $channels,
    ): void {
        $n = Notification::withoutGlobalScopes()->firstOrCreate(
            ['code' => $code],
            [
                'tenant_id' => $tenantId, 'project_id' => $projectId, 'owner_level' => 'project',
                'source' => $source, 'type' => $type, 'category' => $type,
                'title' => $title, 'summary' => $summary, 'priority' => $requiresAck ? 'high' : 'normal',
                'status' => 'published', 'published_at' => now(), 'requires_ack' => $requiresAck,
            ],
        );

        NotificationAudience::withoutGlobalScopes()->firstOrCreate(
            ['notification_id' => $n->id, 'scope_type' => 'apartment', 'scope_id' => $apartmentId],
        );
        foreach ($channels as $ch) {
            NotificationChannel::withoutGlobalScopes()->firstOrCreate(
                ['notification_id' => $n->id, 'channel' => $ch],
            );
        }
    }

    /** @return array{0:Apartment,1:Building,2:?Resident,3:int,4:?int}|null */
    private function resolveContext(string $email): ?array
    {
        $user = User::where('email', $email)->first();
        if ($user === null) {
            return null;
        }
        $relation = ResidentApartmentRelation::withoutGlobalScopes()
            ->whereIn('resident_id', Resident::withoutGlobalScopes()->where('user_id', $user->id)->pluck('id'))
            ->whereNotNull('apartment_id')
            ->orderByDesc('is_primary')->orderBy('id')->first();
        if ($relation === null) {
            return null;
        }

        $apartment = Apartment::withoutGlobalScopes()->find($relation->apartment_id);
        $building = Building::withoutGlobalScopes()->find($apartment->building_id);
        $resident = Resident::withoutGlobalScopes()->find($relation->resident_id);

        return [$apartment, $building, $resident, (int) $apartment->tenant_id, $building?->project_id];
    }
}
