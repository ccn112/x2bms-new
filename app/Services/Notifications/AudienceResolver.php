<?php

namespace App\Services\Notifications;

use App\Models\Building;
use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\ResidentApartmentRelation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Giải rule đối tượng nhận → tập người nhận đã dedupe (spec 07). Chạy ở console/job
 * nên PHẢI scope tenant TƯỜNG MINH (không dựa global scope) — đây là ranh giới
 * MUST_NOT_LEAK: chiến dịch tòa A không bao giờ resolve ra cư dân tenant khác.
 *
 * Dedupe theo cư dân (1 người = 1 recipient); `audience_reasons` giữ mọi căn/vai trò
 * đưa họ vào tập (audit). Delivery per-kênh vẫn ở notification_delivery_logs (ADR-002).
 *
 * Chiều đã hỗ trợ (deterministic, có cột thật): scope building/apartment/project +
 * building_codes; include/exclude relationship_roles, resident_status, relationship_active,
 * resident_ids/user_ids. Chiều capability (has_app/zalo/age…) để phase sau, KHÔNG bịa.
 */
class AudienceResolver
{
    /** Map token DSL → giá trị cột residents.status. */
    private const STATUS_MAP = [
        'verified' => 'active', 'active' => 'active', 'pending' => 'pending', 'inactive' => 'inactive',
    ];

    public function __construct(private readonly AudienceRuleValidator $validator) {}

    /**
     * Đếm ước tính (không ghi DB) — cho wizard live.
     *
     * @return array{residents:int,apartments:int}
     */
    public function estimate(Notification $notification, ?array $rule = null): array
    {
        $q = $this->buildRelationQuery($notification, $rule);

        return [
            'residents' => (clone $q)->distinct()->count('residents.id'),
            'apartments' => (clone $q)->distinct()->count('resident_apartment_relations.apartment_id'),
        ];
    }

    /**
     * Resolve + ghi snapshot người nhận (idempotent: xóa rồi ghi lại). Trả số cư dân.
     */
    public function resolve(Notification $notification, ?array $rule = null): int
    {
        $rows = $this->buildRelationQuery($notification, $rule)
            ->get([
                'residents.id as resident_id',
                'residents.user_id as user_id',
                'apartments.building_id as building_id',
                'resident_apartment_relations.apartment_id as apartment_id',
                'resident_apartment_relations.role as role',
                'apartments.code as apartment_code',
            ]);

        // Dedupe theo cư dân; gom lý do (căn + vai trò).
        $byResident = [];
        foreach ($rows as $r) {
            $rid = (int) $r->resident_id;
            if (! isset($byResident[$rid])) {
                $byResident[$rid] = [
                    'resident_id' => $rid,
                    'user_id' => $r->user_id ? (int) $r->user_id : null,
                    'building_id' => $r->building_id ? (int) $r->building_id : null,
                    'apartment_id' => $r->apartment_id ? (int) $r->apartment_id : null,
                    'role' => $r->role,
                    'reasons' => [],
                ];
            }
            $byResident[$rid]['reasons'][] = [
                'apartment_id' => $r->apartment_id ? (int) $r->apartment_id : null,
                'apartment_code' => $r->apartment_code,
                'role' => $r->role,
            ];
        }

        $channelsPlanned = $notification->relationLoaded('channels')
            ? $notification->channels->where('enabled', true)->pluck('channel')->values()->all()
            : $notification->channels()->where('enabled', true)->pluck('channel')->values()->all();

        DB::transaction(function () use ($notification, $byResident, $channelsPlanned) {
            NotificationRecipient::where('notification_id', $notification->id)->delete();

            foreach (array_chunk($byResident, 500) as $chunk) {
                $now = now();
                $insert = array_map(fn (array $rec) => [
                    'notification_id' => $notification->id,
                    'tenant_id' => $notification->tenant_id,
                    'project_id' => $notification->project_id,
                    'building_id' => $rec['building_id'],
                    'apartment_id' => $rec['apartment_id'],
                    'user_id' => $rec['user_id'],
                    'resident_id' => $rec['resident_id'],
                    'role' => $rec['role'],
                    'audience_reasons' => json_encode($rec['reasons'], JSON_UNESCAPED_UNICODE),
                    'channels_planned' => json_encode($channelsPlanned, JSON_UNESCAPED_UNICODE),
                    'status' => 'resolved',
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $chunk);
                NotificationRecipient::insert($insert);
            }

            $notification->forceFill([
                'recipient_count' => count($byResident),
                'audience_snapshot_hash' => hash('sha256', json_encode(array_keys($byResident))),
            ])->save();
        });

        return count($byResident);
    }

    /**
     * Query cơ sở: relations × residents × apartments, scope tenant tường minh + rule.
     * withoutGlobalScopes an toàn vì re-apply scope tenant/project ngay sau (ADR-001).
     */
    private function buildRelationQuery(Notification $notification, ?array $rule): Builder
    {
        $rule = $this->validator->normalize($rule ?? (array) ($notification->audience_rule ?? []));
        $scope = $rule['scope'];

        $q = ResidentApartmentRelation::withoutGlobalScopes()
            ->join('residents', 'residents.id', '=', 'resident_apartment_relations.resident_id')
            ->join('apartments', 'apartments.id', '=', 'resident_apartment_relations.apartment_id')
            ->join('buildings', 'buildings.id', '=', 'apartments.building_id')
            ->whereNull('residents.deleted_at')
            ->whereNull('resident_apartment_relations.deleted_at') // relationship_active mặc định
            ->whereNull('buildings.deleted_at');

        // TENANT/PROJECT SCOPE tường minh (MUST_NOT_LEAK). Project suy từ buildings
        // (apartments không có project_id — project ở tầng tòa).
        if ($notification->tenant_id !== null) {
            $q->where('residents.tenant_id', $notification->tenant_id);
        }
        if ($notification->project_id !== null) {
            $q->where('buildings.project_id', $notification->project_id);
        }

        $this->applyScope($q, $scope, $notification);

        foreach ($rule['include'] as $cond) {
            $this->applyCondition($q, $cond, false);
        }
        foreach ($rule['exclude'] as $cond) {
            $this->applyCondition($q, $cond, true);
        }

        return $q;
    }

    private function applyScope(Builder $q, array $scope, Notification $notification): void
    {
        if (! empty($scope['building_ids'])) {
            $q->whereIn('apartments.building_id', (array) $scope['building_ids']);
        }
        if (! empty($scope['building_codes'])) {
            $ids = Building::withoutGlobalScopes()
                ->when($notification->tenant_id !== null, fn ($b) => $b->where('tenant_id', $notification->tenant_id))
                ->whereIn('code', (array) $scope['building_codes'])->pluck('id');
            $q->whereIn('apartments.building_id', $ids);
        }
        if (! empty($scope['floor_ids'])) {
            $q->whereIn('apartments.floor_id', (array) $scope['floor_ids']);
        }
        if (! empty($scope['apartment_ids'])) {
            $q->whereIn('resident_apartment_relations.apartment_id', (array) $scope['apartment_ids']);
        }
        if (! empty($scope['resident_ids'])) {
            $q->whereIn('residents.id', (array) $scope['resident_ids']);
        }
        if (! empty($scope['user_ids'])) {
            $q->whereIn('residents.user_id', (array) $scope['user_ids']);
        }
    }

    private function applyCondition(Builder $q, array $cond, bool $exclude): void
    {
        $field = $cond['field'] ?? null;
        $value = $cond['value'] ?? null;

        [$col, $mapped] = match ($field) {
            'relationship_roles', 'relationship_role' => ['resident_apartment_relations.role', (array) $value],
            'resident_status' => ['residents.status', array_values(array_filter(array_map(
                fn ($v) => self::STATUS_MAP[$v] ?? null, (array) $value
            )))],
            'resident_ids' => ['residents.id', (array) $value],
            'user_ids' => ['residents.user_id', (array) $value],
            default => [null, null],
        };

        if ($col === null) {
            // relationship_active + chiều capability (has_app/zalo/age…) chưa hỗ trợ ở
            // phase này → bỏ qua an toàn, KHÔNG bịa dữ liệu (spec 07 §4). Mặc định đã lọc
            // quan hệ đang hiệu lực (deleted_at null).
            return;
        }
        if (empty($mapped)) {
            return;
        }

        $exclude ? $q->whereNotIn($col, $mapped) : $q->whereIn($col, $mapped);
    }
}
