<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Controllers\Api\V1\Resident\Concerns\LabelsCommentAuthor;
use App\Http\Resources\Api\V1\CommentResource;
use App\Http\Resources\Api\V1\CommunityPostResource;
use App\Models\AuditLog;
use App\Models\CommunityPost;
use App\Models\CommunityPostReaction;
use App\Models\CommunityPostReport;
use App\Services\Resident\CommunityModerationService;
use App\Services\Resident\ResidentContextService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Lớp GHI của tab Cộng đồng (CD-CM-01) — đăng bài, cảm xúc, bình luận, báo cáo
 * và kiểm duyệt ngay trên app. Hợp đồng: `x2mobile/docs/API_REQUIREMENTS_COMMUNITY_WRITE_20260727.md`.
 * Thiết kế nghiệp vụ: `docs/COMMUNITY_WRITE_MODERATION_DESIGN.md`.
 *
 * KHÔNG duyệt trước: cư dân đăng là `published` ngay, hậu kiểm qua report.
 * Đọc feed vẫn nằm ở `CommunityController@posts`.
 */
class CommunityPostController extends ApiController
{
    use LabelsCommentAuthor;

    public function __construct(
        private readonly ResidentContextService $context,
        private readonly CommunityModerationService $moderation,
    ) {}

    // ── Bài viết ───────────────────────────────────────────────────────────────

    /** POST /resident/community/posts */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:5000'],
            'attachment_ids' => ['nullable', 'array', 'max:10'],
            'attachment_ids.*' => ['integer'],
            'community_group_id' => ['nullable', 'integer'],
        ]);

        $body = trim((string) ($data['body'] ?? ''));
        $attachmentIds = $data['attachment_ids'] ?? [];
        if ($body === '' && empty($attachmentIds)) {
            return ApiResponse::error(
                'validation_failed',
                'Bài viết cần có nội dung hoặc ảnh.',
                422,
                ['body' => ['Nhập nội dung hoặc chọn ít nhất một ảnh.']],
            );
        }

        $user = $request->user();
        $projectIds = $this->context->projectIds($user, $request->header('X-Context-Id'));
        if (empty($projectIds)) {
            return ApiResponse::error('no_context', 'Chưa xác định được dự án của bạn.', 422);
        }

        $resident = $user->residentMemberships()->first();
        $author = $this->commentAuthor($request, $this->context);

        $post = new CommunityPost([
            'project_id' => $projectIds[0],
            'community_group_id' => $data['community_group_id'] ?? null,
            'author_resident_id' => $resident?->id,
            'author_user_id' => $user->id,
            'author_kind' => $author['is_staff'] ? 'staff' : 'resident',
            'body' => $body,
            'status' => 'published', // hậu kiểm — không chờ BQL duyệt
        ]);
        // tenant_id: cư dân có tenant_id = NULL nên lấy theo dự án.
        $post->tenant_id = $user->tenant_id
            ?? DB::table('projects')->where('id', $projectIds[0])->value('tenant_id');
        $post->save();

        $post->linkAttachments($attachmentIds, $user->id);

        return $this->respondWithPost($request, $post->fresh(), 201);
    }

    /** GET /resident/community/posts/{post} */
    public function show(Request $request, int $post): JsonResponse
    {
        $model = $this->findVisible($request, $post);

        return $model
            ? $this->respondWithPost($request, $model)
            : ApiResponse::error('not_found', 'Bài viết không còn khả dụng.', 404);
    }

    /** DELETE /resident/community/posts/{post} — tác giả tự xóa (soft). */
    public function destroy(Request $request, int $post): JsonResponse
    {
        $model = $this->findInScope($request, $post);
        if (! $model) {
            return ApiResponse::error('not_found', 'Bài viết không còn khả dụng.', 404);
        }

        $user = $request->user();
        if (! $this->moderation->isAuthor($user, $model) && ! $this->moderation->canModerate($user, $model)) {
            return ApiResponse::error('forbidden', 'Bạn không có quyền xóa bài này.', 403);
        }

        $model->delete();

        return response()->json(null, 204);
    }

    // ── Cảm xúc ────────────────────────────────────────────────────────────────

    /** POST /resident/community/posts/{post}/reactions — upsert theo (bài, người). */
    public function react(Request $request, int $post): JsonResponse
    {
        $data = $request->validate([
            'emoji' => ['required', 'string', 'in:'.implode(',', CommunityPostReaction::CODES)],
        ]);

        $model = $this->findVisible($request, $post);
        if (! $model) {
            return ApiResponse::error('not_found', 'Bài viết không còn khả dụng.', 404);
        }
        if ($locked = $this->rejectIfLocked($model)) {
            return $locked;
        }

        CommunityPostReaction::updateOrCreate(
            ['community_post_id' => $model->id, 'user_id' => $request->user()->id],
            ['emoji' => $data['emoji']],
        );

        return $this->respondWithTally($model, $request);
    }

    /** DELETE /resident/community/posts/{post}/reactions — bỏ cảm xúc. */
    public function unreact(Request $request, int $post): JsonResponse
    {
        $model = $this->findVisible($request, $post);
        if (! $model) {
            return ApiResponse::error('not_found', 'Bài viết không còn khả dụng.', 404);
        }
        if ($locked = $this->rejectIfLocked($model)) {
            return $locked;
        }

        CommunityPostReaction::query()
            ->where('community_post_id', $model->id)
            ->where('user_id', $request->user()->id)
            ->delete();

        return $this->respondWithTally($model, $request);
    }

    // ── Bình luận (module polymorphic dùng chung) ──────────────────────────────

    /**
     * GET /resident/community/posts/{post}/comments
     *   ?per_page=20&cursor=<id>&sort=newest|oldest&parent_id=<id>
     *
     * Phân trang bằng **cursor theo id** (keyset), không phải offset.
     *
     * Bản trước là phân trang GIẢ: `->orderBy('id')->limit(500)->get()` rồi trả
     * `ApiResponse::paginated(..., null)` — cursor luôn null nên client không có
     * cách nào đi tiếp, bài quá 500 bình luận thì phần còn lại KHÔNG TỒN TẠI với
     * người dùng, mà mỗi lần mở vẫn nạp 500 dòng kèm user + attachment.
     *
     * Vì sao keyset chứ không `OFFSET`: ở bài có hàng chục nghìn bình luận,
     * `OFFSET 20000` buộc MySQL đếm qua 20k dòng mỗi trang; keyset dùng thẳng
     * index `(commentable_type, commentable_id, id)` nên trang thứ 1000 nhanh
     * bằng trang đầu. Keyset cũng không bị lệch trang khi có người vừa bình luận
     * (offset thì chèn một dòng là cả trang sau trượt).
     *
     * `parent_id` để tải trả lời của MỘT bình luận theo trang riêng — trước đây
     * mọi cấp trộn trong một mẻ 500 dòng.
     */
    public function comments(Request $request, int $post): JsonResponse
    {
        $model = $this->findVisible($request, $post);
        if (! $model) {
            return ApiResponse::error('not_found', 'Bài viết không còn khả dụng.', 404);
        }

        $data = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'cursor' => ['nullable', 'integer', 'min:0'],
            'sort' => ['nullable', 'in:newest,oldest'],
            'parent_id' => ['nullable', 'integer'],
        ]);

        $perPage = (int) ($data['per_page'] ?? 20);
        $sort = $data['sort'] ?? 'newest';
        $cursor = $data['cursor'] ?? null;
        $newest = $sort === 'newest';

        $query = $model->comments()
            ->with(['user:id,name,avatar_path', 'attachments'])
            // Đếm trả lời bằng subquery thay vì nạp cả cây: app vẽ "Xem N trả
            // lời" rồi mới gọi tiếp với `parent_id` khi người dùng bấm.
            ->withCount('replies');

        // `parent_id` không truyền → chỉ bình luận GỐC. Trước đây trả lời trộn
        // lẫn bình luận gốc nên app phải tự gom, và số dòng phình theo cấp.
        if (array_key_exists('parent_id', $data) && $data['parent_id'] !== null) {
            $query->where('parent_id', $data['parent_id']);
        } else {
            $query->whereNull('parent_id');
        }

        if ($cursor !== null) {
            // Trang sau = "id nhỏ hơn cursor" khi sắp mới nhất trước, và ngược lại.
            $query->where('id', $newest ? '<' : '>', $cursor);
        }

        // Lấy thừa 1 dòng để biết CÒN trang sau hay không, không phải COUNT(*)
        // toàn bảng.
        $rows = $query->orderBy('id', $newest ? 'desc' : 'asc')
            ->limit($perPage + 1)
            ->get();

        $hasMore = $rows->count() > $perPage;
        $items = $hasMore ? $rows->take($perPage) : $rows;
        $nextCursor = $hasMore ? (string) $items->last()->id : null;

        $user = $request->user();
        $items->each(function ($c) use ($user): void {
            $c->is_mine = $user?->id !== null && $c->user_id === $user->id;
        });

        return ApiResponse::paginated(
            CommentResource::collection($items->values())->resolve($request),
            $nextCursor,
        );
    }

    /** POST /resident/community/posts/{post}/comments */
    public function storeComment(Request $request, int $post): JsonResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'parent_id' => ['nullable', 'integer'],
            'attachment_ids' => ['nullable', 'array', 'max:10'],
            'attachment_ids.*' => ['integer'],
        ]);

        $model = $this->findVisible($request, $post);
        if (! $model) {
            return ApiResponse::error('not_found', 'Bài viết không còn khả dụng.', 404);
        }
        if ($locked = $this->rejectIfLocked($model)) {
            return $locked;
        }

        // Chỉ 1 cấp lồng — reply-của-reply gộp về bình luận cha.
        $parentId = null;
        if (! empty($data['parent_id'])) {
            $parent = $model->comments()->whereKey($data['parent_id'])->first();
            $parentId = $parent?->parent_id ?? $parent?->id;
        }

        $user = $request->user();
        $author = $this->commentAuthor($request, $this->context);

        $comment = $model->comments()->create([
            'parent_id' => $parentId,
            'user_id' => $user->id,
            'author_name' => $author['name'],
            'author_subtitle' => $author['subtitle'],
            'is_staff' => $author['is_staff'],
            'body' => trim($data['body']),
        ]);
        $comment->linkAttachments($data['attachment_ids'] ?? [], $user->id);
        $comment->setRelation('user', $user);
        $comment->load('attachments');
        $comment->is_mine = true;

        $model->increment('comment_count');

        return ApiResponse::success(CommentResource::make($comment)->resolve($request), [], 201);
    }

    // ── Báo cáo & kiểm duyệt ───────────────────────────────────────────────────

    /** POST /resident/community/posts/{post}/report */
    public function report(Request $request, int $post): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'in:'.implode(',', CommunityPostReport::REASONS)],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $model = $this->findVisible($request, $post);
        if (! $model) {
            return ApiResponse::error('not_found', 'Bài viết không còn khả dụng.', 404);
        }
        // Khớp với cờ `can.report` trả xuống app: không tự báo cáo bài mình.
        // Nếu chỉ ẩn nút ở client mà server vẫn nhận thì cờ kia thành trang trí.
        if ($this->moderation->isAuthor($request->user(), $model)) {
            return ApiResponse::error('forbidden', 'Không thể báo cáo bài của chính bạn.', 403);
        }

        // Idempotent: bấm báo cáo lần hai không phải là lỗi.
        $existing = CommunityPostReport::query()
            ->where('community_post_id', $model->id)
            ->where('reported_by_user_id', $request->user()->id)
            ->first();

        if (! $existing) {
            CommunityPostReport::create([
                'community_post_id' => $model->id,
                'reported_by_user_id' => $request->user()->id,
                'reason' => $data['reason'],
                'note' => $data['note'] ?? null,
                'status' => 'open',
            ]);
            $model->increment('report_count');
        }

        return ApiResponse::success(['reported' => true]);
    }

    /**
     * POST /resident/community/posts/{post}/moderate — BQL khóa/ẩn/xóa.
     * Route nằm ở nhóm `ability:resident,staff` để nhân sự KHÔNG phải cư dân
     * vẫn gọi được; quyền thật kiểm ở đây theo phạm vi dự án.
     */
    public function moderate(Request $request, int $post): JsonResponse
    {
        $data = $request->validate([
            'action' => ['required', 'string', 'in:hide,unhide,lock,unlock,delete,restore'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        // Kiểm duyệt phải thấy cả bài đã ẩn / đã xóa mềm (để bỏ ẩn / khôi phục).
        $model = CommunityPost::withTrashed()->find($post);
        if (! $model) {
            return ApiResponse::error('not_found', 'Bài viết không tồn tại.', 404);
        }

        $user = $request->user();
        if (! $this->moderation->canModerate($user, $model)) {
            return ApiResponse::error('forbidden', 'Bạn không có quyền kiểm duyệt bài này.', 403);
        }

        $action = $data['action'];
        $reason = trim((string) ($data['reason'] ?? ''));
        if (in_array($action, ['hide', 'lock', 'delete'], true) && $reason === '') {
            return ApiResponse::error(
                'validation_failed',
                'Cần nhập lý do — cư dân sẽ nhìn thấy lý do này.',
                422,
                ['reason' => ['Bắt buộc nhập lý do.']],
            );
        }

        $now = now();
        match ($action) {
            'hide' => $model->forceFill([
                'status' => 'hidden',
                'moderated_at' => $now,
                'moderated_by_user_id' => $user->id,
                'moderation_reason' => $reason,
            ])->save(),
            'unhide' => $model->forceFill([
                'status' => 'published',
                'moderated_at' => $now,
                'moderated_by_user_id' => $user->id,
                'moderation_reason' => null,
            ])->save(),
            'lock' => $model->forceFill([
                'locked_at' => $now,
                'locked_by_user_id' => $user->id,
                'moderation_reason' => $reason,
            ])->save(),
            'unlock' => $model->forceFill([
                'locked_at' => null,
                'locked_by_user_id' => null,
            ])->save(),
            'delete' => $this->softDeleteWithReason($model, $user->id, $reason, $now),
            'restore' => $model->restore(),
            default => null,
        };

        $this->auditModeration($model, $user->id, $action, $reason);

        return $this->respondWithPost($request, $model->fresh() ?? $model);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function softDeleteWithReason(CommunityPost $post, int $userId, string $reason, $now): void
    {
        $post->forceFill([
            'moderated_at' => $now,
            'moderated_by_user_id' => $userId,
            'moderation_reason' => $reason,
        ])->save();
        $post->delete();
    }

    /**
     * Kiểm duyệt là hành động có thể bị khiếu nại → phải truy vết được ai làm.
     * Ghi mềm: thiếu bảng audit thì cũng không được làm hỏng request.
     */
    private function auditModeration(CommunityPost $post, int $userId, string $action, string $reason): void
    {
        try {
            AuditLog::create([
                'tenant_id' => $post->tenant_id,
                'user_id' => $userId,
                'auditable_type' => $post->getMorphClass(),
                'auditable_id' => $post->id,
                'event' => 'community.moderate.'.$action,
                'new_values' => ['action' => $action, 'reason' => $reason],
            ]);
        } catch (\Throwable) {
            // bỏ qua — không chặn nghiệp vụ vì log
        }
    }

    /** Bài trong phạm vi dự án của người xem (kể cả đã ẩn — lọc ở tầng trên). */
    private function findInScope(Request $request, int $id): ?CommunityPost
    {
        $projectIds = $this->context->projectIds($request->user(), $request->header('X-Context-Id'));
        if (empty($projectIds)) {
            return null;
        }

        return CommunityPost::query()->whereIn('project_id', $projectIds)->find($id);
    }

    /**
     * Bài người xem ĐƯỢC PHÉP thấy. Bài ẩn chỉ tác giả và BQL thấy — người khác
     * nhận 404 chứ không phải 403, để không lộ là bài có tồn tại.
     */
    private function findVisible(Request $request, int $id): ?CommunityPost
    {
        $post = $this->findInScope($request, $id);
        if (! $post) {
            return null;
        }
        if (! $post->isHidden()) {
            return $post;
        }
        $user = $request->user();

        return ($this->moderation->isAuthor($user, $post) || $this->moderation->canModerate($user, $post))
            ? $post
            : null;
    }

    private function rejectIfLocked(CommunityPost $post): ?JsonResponse
    {
        return $post->isLocked()
            ? ApiResponse::error('locked', 'Ban quản lý đã khóa tương tác bài này.', 423)
            : null;
    }

    private function respondWithTally(CommunityPost $post, Request $request): JsonResponse
    {
        $tally = $this->moderation->tally($post, $request->user());
        $this->moderation->syncLikeCount($post, $tally['total']);

        return ApiResponse::success([
            'summary' => (object) $tally['summary'],
            'total' => $tally['total'],
            'mine' => $tally['mine'],
        ]);
    }

    /** Trả 1 bài kèm `reactions` + `can` (bơm qua additional, xem Resource). */
    private function respondWithPost(Request $request, CommunityPost $post, int $status = 200): JsonResponse
    {
        $post->loadMissing(['author.apartmentRelations.apartment', 'attachments']);
        $post->loadCount('comments');
        $user = $request->user();

        $post->post_meta = [
            'reactions' => $this->moderation->tally($post, $user),
            'can' => $this->moderation->abilities($user, $post),
        ];

        $payload = CommunityPostResource::make($post)->resolve($request);

        return ApiResponse::success($payload, [], $status);
    }
}
