<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Bootstrap endpoints (docs §4.3 + Flutter foundation bootstrap_response.schema.json).
 * The response tells the app which of the 4 experience modes to render and which
 * contexts (apartment relations / staff scopes) the person may switch into.
 */
class BootstrapController extends ApiController
{
    /** GET /api/v1/public/bootstrap — no auth. Branding + enabled modules + min version. */
    public function public(Request $request): JsonResponse
    {
        // Không auth → BelongsToTenant/BelongsToProject đều no-op → hiển thị showcase.
        $projects = \App\Models\Project::query()
            ->whereNotIn('status', ['archived', 'inactive'])
            ->orderByDesc('id')
            ->limit(12)
            ->get();

        $featuredProjects = $projects->map(fn ($p) => [
            'id' => (string) $p->id,
            'slug' => $p->code ?: (string) $p->id,
            'name' => $p->name,
            'location' => collect([$p->district, $p->city])->filter()->implode(', ') ?: ($p->address ?? ''),
            // Trạng thái BÁN HÀNG (chip trên khuôn M01-PUB-02) — KHÔNG phải
            // `$p->status` (trạng thái vận hành SaaS: active/trial/suspended).
            'status' => self::salesStatus($p),
            'image' => \App\Support\DemoImage::url('building,residential,skyline', $p->id, 1200, 700),
            'summary' => $p->description,
        ])->all();

        // Nội dung công khai = thông báo cấp nền tảng (platform) đã publish (an toàn đa tenant).
        $content = \App\Models\Notification::query()
            ->where('owner_level', 'platform')
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(6)
            ->get()
            ->map(fn ($n) => [
                'id' => (string) $n->id,
                'slug' => $n->code ?: (string) $n->id,
                'type' => $n->type,
                'title' => $n->title,
                'published_at' => optional($n->published_at ?? $n->created_at)->toIso8601String(),
                'body' => $n->body ?? $n->summary,
                // Thẻ tin/sự kiện ở app cần ẢNH và MÔ TẢ NGẮN. Thiếu hai trường
                // này app phải tự sinh ảnh placeholder theo slug và cắt tạm phần
                // đầu body làm mô tả — nhìn nghèo và không đúng ý người đăng.
                'summary' => $n->summary,
                'image' => $n->cover_path
                    ? (str_starts_with($n->cover_path, 'http')
                        ? $n->cover_path
                        : \Illuminate\Support\Facades\Storage::disk('public')->url($n->cover_path))
                    : \App\Support\DemoImage::url(
                        $n->type === 'event' ? 'community,event' : 'building,residential',
                        $n->id, 800, 500),
            ])->all();

        return ApiResponse::success([
            'experience_mode' => 'public',
            'branding' => $this->defaultBranding(),
            'city' => ['name' => $projects->first()->city ?? 'TP. Hồ Chí Minh'],
            'enabled_modules' => ['projects', 'content', 'community', 'offers'],
            'featured_projects' => $featuredProjects,
            'content' => $content,
            'minimum_app_version' => config('mobile.min_app_version'),
        ]);
    }

    /** GET /api/v1/me/bootstrap — auth:sanctum. Resolves the person's contexts + mode. */
    public function me(
        Request $request,
        \App\Services\Resident\ResidentNotificationService $notifications,
        \App\Services\Community\MembershipService $membership,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        // Auto-enroll X2Living (Giai đoạn 3 Community Domain, COM-002) — MỌI
        // tài khoản đã đăng nhập là thành viên `platform_community`, kể cả
        // tier `member` thuần chưa gắn căn hộ nào. `bootstrap` là điểm hội tụ
        // duy nhất mọi phiên app đều gọi ngay sau đăng nhập (mọi luồng đăng ký
        // — OTP/social/mật khẩu — đều dẫn tới đây), nên chọn làm nơi enroll
        // thay vì thêm hook riêng ở từng luồng đăng ký. Idempotent — gọi lại
        // mỗi lần mở app không tạo trùng (xem `MembershipService::grant()`).
        $membership->enrollPlatformCommunity($user);

        // Nạp sẵn căn hộ → toà → dự án: bảng chọn căn hộ ở app cần NHÃN đọc
        // được, không phải mỗi id. Thiếu nhãn thì app phải gọi thêm một vòng
        // cho từng căn, hoặc tệ hơn là hiển thị dữ liệu bịa (bản cũ đúng như
        // vậy — bottom sheet ghi tên ba dự án không có thật).
        $residentContexts = $user->residentMemberships()
            ->with(['apartmentRelations.apartment.building.project'])
            ->get()
            ->flatMap(fn ($resident) => $resident->apartmentRelations->map(function ($rel) {
                $apartment = $rel->apartment;
                $building = $apartment?->building;

                return [
                    'context_id' => 'apartment:'.$rel->id,
                    'type' => 'resident',
                    'apartment_id' => $rel->apartment_id,
                    'role' => $rel->role,
                    'role_label' => match ($rel->role) {
                        'owner' => 'Chủ sở hữu',
                        'tenant' => 'Người thuê',
                        'member' => 'Thành viên hộ',
                        default => 'Cư dân',
                    },
                    'is_primary' => (bool) $rel->is_primary,
                    'apartment_code' => $apartment?->code,
                    'building_name' => $building?->name,
                    'project_id' => $building?->project_id,
                    'project_name' => $building?->project?->name,
                ];
            }))
            ->values()
            ->all();

        $staffContexts = $user->roleScopes()
            ->get()
            ->map(fn ($scope) => [
                'context_id' => 'scope:'.$scope->id,
                'type' => 'staff',
                'scope_type' => $scope->scope_type,
                'tenant_id' => $scope->tenant_id,
                'project_id' => $scope->project_id,
                'building_id' => $scope->building_id,
            ])
            ->all();

        $contexts = array_merge($residentContexts, $staffContexts);

        return ApiResponse::success([
            'experience_mode' => $this->resolveExperienceMode($user, $residentContexts),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'gender' => $user->gender,
                'nationality' => $user->nationality,
                'kyc_status' => $user->kyc_status,
                'avatar_url' => $user->avatar_url,
                'abilities' => $user->tokenAbilities(),
            ],
            'available_contexts' => $contexts,
            'branding' => $this->defaultBranding(),
            'enabled_modules' => ['projects', 'content', 'community', 'offers', 'billing', 'feedback', 'amenities', 'notifications'],
            'minimum_app_version' => config('mobile.min_app_version'),
            'unread_notification_count' => empty($residentContexts)
                ? 0
                : $notifications->unreadCount($user, $request->header('X-Context-Id')),
        ]);
    }

    private function resolveExperienceMode(User $user, array $residentContexts): string
    {
        if (! empty($residentContexts)) {
            return 'verified_resident';
        }
        // A pending application (ResidentApprovalRequest) would flip this to resident_applicant
        // once that slice is wired; for now an authenticated person with no active relation = member.
        return 'member';
    }

    /**
     * Trạng thái bán hàng cho chip công khai. Ưu tiên cột `sales_status`; chưa
     * đặt thì suy ra từ `handover_date` để dự án cũ vẫn có nhãn đúng:
     * đã qua ngày bàn giao → đã bàn giao · còn ≤ 12 tháng → sắp bàn giao ·
     * còn lại → đang mở bán.
     */
    private static function salesStatus(\App\Models\Project $p): string
    {
        if (! empty($p->sales_status)) {
            return $p->sales_status;
        }
        $handover = $p->handover_date ? \Illuminate\Support\Carbon::parse($p->handover_date) : null;
        if ($handover === null) {
            return 'open_for_sale';
        }

        return match (true) {
            $handover->isPast() => 'handed_over',
            $handover->diffInMonths(now()) <= 12 => 'handover_soon',
            default => 'open_for_sale',
        };
    }

    private function defaultBranding(): array
    {
        return [
            'theme_id' => 'navy',
            'app_name' => 'X2 Resident',
        ];
    }
}
