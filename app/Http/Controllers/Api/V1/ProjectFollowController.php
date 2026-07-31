<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Project;
use App\Models\UserProjectFollow;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * "Quan tâm dự án" — Giai đoạn 4 Community Domain (2026-07-31).
 *
 * KHÔNG dùng `ability:resident` — chốt: mọi tài khoản đã đăng nhập (kể cả
 * tier `member`, chưa có quan hệ căn hộ nào) đều follow được. Đây đúng là lý
 * do kênh `project_interest_channel` tồn tại: cho người CHƯA phải cư dân.
 *
 * Follow KHÔNG cấp quyền, KHÔNG cho vào nhóm nào — chỉ là tín hiệu ưu tiên
 * hiển thị trong feed (`docs/COMMUNITY_DB_MAPPING.md` §4).
 */
class ProjectFollowController extends ApiController
{
    /** GET /api/v1/me/project-follows */
    public function index(Request $request): JsonResponse
    {
        $follows = UserProjectFollow::query()
            ->where('user_id', $request->user()->id)
            ->with('project')
            ->orderByDesc('followed_at')
            ->get();

        return ApiResponse::success(
            $follows->map(fn (UserProjectFollow $f) => [
                'project_id' => (string) $f->project_id,
                'project_name' => $f->project?->name,
                'followed_at' => $f->followed_at?->toIso8601String(),
            ])
        );
    }

    /** POST /api/v1/me/project-follows — {project_id}. Idempotent. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['project_id' => ['required', 'integer', 'exists:projects,id']]);

        $follow = UserProjectFollow::firstOrCreate(
            ['user_id' => $request->user()->id, 'project_id' => $data['project_id']],
            ['followed_at' => now()],
        );

        return ApiResponse::success(['followed' => true, 'followed_at' => $follow->followed_at?->toIso8601String()], status: 201);
    }

    /** DELETE /api/v1/me/project-follows/{project} */
    public function destroy(Request $request, int $project): JsonResponse
    {
        UserProjectFollow::query()
            ->where('user_id', $request->user()->id)
            ->where('project_id', $project)
            ->delete();

        return ApiResponse::success(['followed' => false]);
    }
}
