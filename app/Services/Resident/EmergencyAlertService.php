<?php

namespace App\Services\Resident;

use App\Models\BqlTeam;
use App\Models\EmergencyAlert;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Cảnh báo khẩn cấp cho cư dân (CD-HOME-04).
 *
 * Cư dân có tenant_id = NULL nên global scope tenant là no-op — MỌI truy vấn ở
 * đây phải scope tường minh qua [ResidentContextService] (xem
 * docs/ARCHITECTURE_X2_PLATFORM_V1.md §5).
 */
class EmergencyAlertService
{
    public function __construct(private readonly ResidentContextService $context)
    {
    }

    /**
     * Cảnh báo cư dân được phép thấy: đúng dự án của họ, và hoặc là cảnh báo
     * toàn dự án (building_id null) hoặc đúng toà họ ở.
     */
    public function scopedQuery(User $user, ?string $contextId): Builder
    {
        $projectIds = $this->context->projectIds($user, $contextId);
        $buildingIds = $this->context->buildingIds($user, $contextId);

        return EmergencyAlert::query()
            ->when(
                $projectIds !== [],
                fn (Builder $q) => $q->whereIn('project_id', $projectIds),
                // Không xác định được dự án → không trả gì. Trả hết là rò rỉ
                // cảnh báo của dự án khác.
                fn (Builder $q) => $q->whereRaw('1 = 0'),
            )
            ->where(fn (Builder $q) => $q
                ->whereNull('building_id')
                ->orWhereIn('building_id', $buildingIds));
    }

    /**
     * Cảnh báo ĐANG hiệu lực: status=active, đã tới giờ bắt đầu, chưa hết hạn.
     *
     * `starts_at` null = hiệu lực ngay (BQL không điền giờ), `ends_at` null =
     * chưa có hạn kết thúc — đúng nghĩa "đang xảy ra, chưa biết bao giờ xong".
     */
    public function activeQuery(User $user, ?string $contextId): Builder
    {
        return $this->scopedQuery($user, $contextId)
            ->where('status', 'active')
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    /**
     * Cảnh báo nặng nhất đang hiệu lực (băng đỏ Trang chủ). critical > warning >
     * info; cùng mức thì cái mới nhất.
     */
    public function current(User $user, ?string $contextId): ?EmergencyAlert
    {
        return $this->activeQuery($user, $contextId)
            ->with(['building:id,name', 'project:id,name'])
            ->orderByRaw("FIELD(severity, 'critical', 'warning', 'info')")
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Kênh liên hệ khẩn của dự án chứa cảnh báo.
     *
     * Nguồn DUY NHẤT hiện có trong schema là `bql_teams` (hotline + email của
     * BQL dự án). Bản đồ nghiệp vụ còn muốn số bảo vệ / kỹ thuật riêng —
     * schema chưa có chỗ chứa, KHÔNG bịa thêm cột. Trong lúc chờ, số an ninh
     * được thay bằng hành động SOS (`POST resident/sos`) mà app đã có.
     *
     * @return array<int,array<string,string>>
     */
    public function contactsForProject(?int $projectId): array
    {
        if ($projectId === null) {
            return [];
        }

        /** @var Collection<int,BqlTeam> $teams */
        $teams = BqlTeam::withoutGlobalScopes()
            ->where('project_id', $projectId)
            ->where('status', 'active')
            ->get(['id', 'name', 'hotline', 'email']);

        $contacts = [];
        foreach ($teams as $team) {
            if (filled($team->hotline)) {
                $contacts[] = [
                    'role' => 'bql',
                    'label' => $team->name ?: 'Ban quản lý',
                    'phone' => $team->hotline,
                ];
            }
            if (filled($team->email)) {
                $contacts[] = [
                    'role' => 'bql_email',
                    'label' => $team->name ?: 'Ban quản lý',
                    'email' => $team->email,
                ];
            }
        }

        return $contacts;
    }
}
