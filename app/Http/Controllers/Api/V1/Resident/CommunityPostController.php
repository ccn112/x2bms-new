<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Actions\Community\ModerateCommunityPostAction;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Controllers\Api\V1\Resident\Concerns\LabelsCommentAuthor;
use App\Http\Resources\Api\V1\CommentResource;
use App\Http\Resources\Api\V1\CommunityPostResource;
use App\Models\CommunityComment;
use App\Models\CommunityCommentReaction;
use App\Models\CommunityPost;
use App\Models\CommunityPostReaction;
use App\Models\CommunityPostReport;
use App\Services\Resident\CommunityModerationService;
use App\Services\Resident\ResidentContextService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

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

        $reaction = CommunityPostReaction::updateOrCreate(
            ['community_post_id' => $model->id, 'user_id' => $request->user()->id],
            ['emoji' => $data['emoji']],
        );
        $this->notifyReaction(
            $model->author_user_id, $request->user(), $reaction->wasRecentlyCreated,
            $request->user()->name.' đã bày tỏ cảm xúc về bài của bạn',
            mb_substr(strip_tags((string) $model->body), 0, 80),
            ['type' => 'community_reaction', 'post_id' => (string) $model->id],
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

    /**
     * POST /resident/community/posts/{post}/share — đếm lượt chia sẻ (GĐ7).
     *
     * "Chia sẻ" ở app là copy link; trước không lưu nên feed không hiện số. Ghi
     * nhận mỗi lần chia sẻ để hiện + bump realtime. Bài khóa vẫn chia sẻ được
     * (chỉ là chia sẻ link đọc), nên KHÔNG chặn theo lock.
     */
    public function sharePost(Request $request, int $post): JsonResponse
    {
        $model = $this->findVisible($request, $post);
        if (! $model) {
            return ApiResponse::error('not_found', 'Bài viết không còn khả dụng.', 404);
        }

        $model->increment('share_count');

        return ApiResponse::success(['shares' => (int) $model->share_count]);
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

        // GĐ7 — bảng chuyên dụng community_comments (không polymorphic).
        $query = CommunityComment::query()
            ->where('community_post_id', $model->id)
            ->where('status', 'visible')
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
        // Cảm xúc CỦA NGƯỜI XEM cho các bình luận đang hiện — một query gộp thay
        // vì N truy vấn (GĐ7).
        $myReactions = $user === null ? collect() : CommunityCommentReaction::query()
            ->where('user_id', $user->id)
            ->whereIn('community_comment_id', $items->pluck('id'))
            ->pluck('emoji', 'community_comment_id');
        $items->each(function ($c) use ($user, $myReactions): void {
            $c->is_mine = $user?->id !== null && $c->user_id === $user->id;
            $c->my_reaction = $myReactions[$c->id] ?? null;
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
            // @mention (GĐ7): id cư dân được nhắc trong bình luận — app render/link.
            'mentioned_user_ids' => ['nullable', 'array', 'max:20'],
            'mentioned_user_ids.*' => ['integer'],
        ]);

        $model = $this->findVisible($request, $post);
        if (! $model) {
            return ApiResponse::error('not_found', 'Bài viết không còn khả dụng.', 404);
        }
        if ($locked = $this->rejectIfLocked($model)) {
            return $locked;
        }

        // Nhắc tên: chỉ giữ id có thật (chống bịa tên người không tồn tại).
        $mentions = empty($data['mentioned_user_ids']) ? null : \App\Models\User::query()
            ->whereIn('id', $data['mentioned_user_ids'])
            ->get(['id', 'name'])
            ->map(fn ($u) => ['user_id' => (string) $u->id, 'name' => $u->name])
            ->all();

        // Chỉ 1 cấp lồng — reply-của-reply gộp về bình luận cha.
        $parentId = null;
        $parent = null;
        if (! empty($data['parent_id'])) {
            $parent = CommunityComment::where('community_post_id', $model->id)
                ->whereKey($data['parent_id'])->first();
            $parentId = $parent?->parent_id ?? $parent?->id;
        }

        $user = $request->user();
        $author = $this->commentAuthor($request, $this->context);

        $comment = CommunityComment::create([
            'community_post_id' => $model->id,
            'tenant_id' => $model->tenant_id,
            'project_id' => $model->project_id,
            'parent_id' => $parentId,
            'user_id' => $user->id,
            'author_name' => $author['name'],
            'author_subtitle' => $author['subtitle'],
            'author_kind' => $author['is_staff'] ? 'staff' : 'resident',
            'is_staff' => $author['is_staff'],
            'body' => trim($data['body']),
            'mentions' => $mentions,
        ]);
        $comment->linkAttachments($data['attachment_ids'] ?? [], $user->id);
        $comment->setRelation('user', $user);
        $comment->load('attachments');
        $comment->is_mine = true;

        $model->increment('comment_count');

        // Đẩy push cho các bên liên quan (kênh cộng đồng; PushService tự bỏ ai
        // đã tắt kênh). KHÔNG tự báo mình; mỗi người chỉ 1 push (mention ưu tiên
        // > trả lời > bình luận-bài). Data mang post_id/comment_id để app mở
        // đúng bài khi bấm vào thông báo.
        $this->notifyComment($model, $comment, $parent, $author, $user, $data);

        return ApiResponse::success(CommentResource::make($comment)->resolve($request), [], 201);
    }

    /**
     * Đẩy push khi có bình luận mới: người được @mention, chủ bình luận CHA (được
     * trả lời), và tác giả BÀI (có bình luận mới). Mỗi người tối đa 1 push, ưu
     * tiên mention > trả lời > bình luận-bài; không tự báo mình.
     */
    private function notifyComment(
        CommunityPost $model,
        CommunityComment $comment,
        ?CommunityComment $parent,
        array $author,
        \App\Models\User $actor,
        array $data,
    ): void {
        $push = app(\App\Services\Push\PushService::class);
        $snippet = mb_substr(trim($data['body']), 0, 80);
        $baseData = [
            'post_id' => (string) $model->id,
            'comment_id' => (string) $comment->id,
        ];
        $done = [$actor->id]; // không tự báo mình

        // Thu thập đích theo thứ tự ưu tiên, mỗi user chỉ lấy lần đầu.
        $targets = []; // uid => [type, title]
        foreach ((array) ($data['mentioned_user_ids'] ?? []) as $mid) {
            $mid = (int) $mid;
            if ($mid > 0 && ! in_array($mid, $done, true) && ! isset($targets[$mid])) {
                $targets[$mid] = ['community_mention', $author['name'].' đã nhắc bạn trong một bình luận'];
            }
        }
        if ($parent && $parent->user_id && ! in_array((int) $parent->user_id, $done, true)
            && ! isset($targets[(int) $parent->user_id])) {
            $targets[(int) $parent->user_id] = ['community_reply', $author['name'].' đã trả lời bình luận của bạn'];
        }
        if ($model->author_user_id && ! in_array((int) $model->author_user_id, $done, true)
            && ! isset($targets[(int) $model->author_user_id])) {
            $targets[(int) $model->author_user_id] = ['community_comment', $author['name'].' đã bình luận về bài của bạn'];
        }
        if (empty($targets)) {
            return;
        }

        $users = \App\Models\User::query()->whereIn('id', array_keys($targets))->get()->keyBy('id');
        foreach ($targets as $uid => [$type, $title]) {
            $u = $users->get($uid);
            if (! $u) {
                continue;
            }
            $push->toUser($u, $title, $snippet, ['type' => $type] + $baseData,
                \App\Enums\NotificationChannel::Community);
        }
    }

    /**
     * Báo cho chủ bài/bình luận khi được thả cảm xúc — chỉ khi cảm xúc MỚI (đổi
     * loại không báo lại), không tự báo mình.
     */
    private function notifyReaction(?int $targetUserId, \App\Models\User $actor, bool $isNew, string $title, string $body, array $data): void
    {
        if (! $isNew || ! $targetUserId || (int) $targetUserId === $actor->id) {
            return;
        }
        $u = \App\Models\User::find($targetUserId);
        if (! $u) {
            return;
        }
        app(\App\Services\Push\PushService::class)
            ->toUser($u, $title, $body, $data, \App\Enums\NotificationChannel::Community);
    }

    /** POST /resident/community/posts/{post}/comments/{comment}/reactions — GĐ7. */
    public function reactComment(Request $request, int $post, int $comment): JsonResponse
    {
        $data = $request->validate([
            'emoji' => ['required', 'string', 'in:'.implode(',', CommunityPostReaction::CODES)],
        ]);
        $c = $this->findVisibleComment($request, $post, $comment);
        if (! $c) {
            return ApiResponse::error('not_found', 'Bình luận không còn khả dụng.', 404);
        }

        $reaction = CommunityCommentReaction::updateOrCreate(
            ['community_comment_id' => $c->id, 'user_id' => $request->user()->id],
            ['emoji' => $data['emoji']],
        );
        $this->notifyReaction(
            $c->user_id, $request->user(), $reaction->wasRecentlyCreated,
            $request->user()->name.' đã bày tỏ cảm xúc về bình luận của bạn',
            mb_substr((string) $c->body, 0, 80),
            ['type' => 'community_reaction', 'post_id' => (string) $post, 'comment_id' => (string) $c->id],
        );

        return $this->commentTally($c, $request);
    }

    /** DELETE /resident/community/posts/{post}/comments/{comment}/reactions — GĐ7. */
    public function unreactComment(Request $request, int $post, int $comment): JsonResponse
    {
        $c = $this->findVisibleComment($request, $post, $comment);
        if (! $c) {
            return ApiResponse::error('not_found', 'Bình luận không còn khả dụng.', 404);
        }

        CommunityCommentReaction::query()
            ->where('community_comment_id', $c->id)
            ->where('user_id', $request->user()->id)
            ->delete();

        return $this->commentTally($c, $request);
    }

    /**
     * POST /resident/community/posts/{post}/comments/{comment}/moderate — GĐ7.
     * BQL ẩn/xoá/khôi phục bình luận (cột `status`). Quyền = kiểm duyệt được BÀI.
     */
    public function moderateComment(Request $request, int $post, int $comment): JsonResponse
    {
        $data = $request->validate([
            'action' => ['required', 'string', 'in:hide,unhide,delete,restore'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $model = CommunityPost::withTrashed()->find($post);
        if (! $model) {
            return ApiResponse::error('not_found', 'Bài viết không tồn tại.', 404);
        }
        if (! $this->moderation->canModerate($request->user(), $model)) {
            return ApiResponse::error('forbidden', 'Bạn không có quyền kiểm duyệt.', 403);
        }

        // Thấy cả bình luận đã ẩn/xoá để bỏ ẩn/khôi phục.
        $c = CommunityComment::query()
            ->where('community_post_id', $model->id)->whereKey($comment)->first();
        if (! $c) {
            return ApiResponse::error('not_found', 'Bình luận không tồn tại.', 404);
        }

        $c->update([
            'status' => match ($data['action']) {
                'hide' => 'hidden',
                'delete' => 'deleted',
                default => 'visible', // unhide | restore
            },
        ]);

        return ApiResponse::success(['comment_id' => (string) $c->id, 'status' => $c->status]);
    }

    /** Bình luận NHÌN THẤY được (thuộc bài trong phạm vi + status visible). */
    private function findVisibleComment(Request $request, int $post, int $comment): ?CommunityComment
    {
        $model = $this->findVisible($request, $post);
        if (! $model) {
            return null;
        }

        return CommunityComment::query()
            ->where('community_post_id', $model->id)
            ->where('status', 'visible')
            ->whereKey($comment)
            ->first();
    }

    /** Đếm lại cảm xúc của một bình luận + trả tally (mine + summary theo emoji). */
    private function commentTally(CommunityComment $c, Request $request): JsonResponse
    {
        $summary = CommunityCommentReaction::query()
            ->where('community_comment_id', $c->id)
            ->selectRaw('emoji, COUNT(*) as n')
            ->groupBy('emoji')
            ->pluck('n', 'emoji');
        $total = (int) $summary->sum();

        $c->forceFill(['reaction_count' => $total])->saveQuietly();

        $mine = CommunityCommentReaction::query()
            ->where('community_comment_id', $c->id)
            ->where('user_id', $request->user()->id)
            ->value('emoji');

        return ApiResponse::success([
            'comment_id' => (string) $c->id,
            'reaction_count' => $total,
            'summary' => $summary,
            'mine' => $mine,
        ]);
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
            'action' => ['required', 'string', 'in:'.implode(',', ModerateCommunityPostAction::ACTIONS)],
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

        try {
            $model = app(ModerateCommunityPostAction::class)->execute($model, $data['action'], $data['reason'] ?? null, $user);
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error('validation_failed', $e->getMessage(), 422, ['reason' => [$e->getMessage()]]);
        }

        return $this->respondWithPost($request, $model);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

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
        // Đếm bình luận THẬT (community_comments visible), không phải polymorphic cũ.
        $post->loadCount(['communityComments as comments_count' => fn ($q) => $q->where('status', 'visible')]);
        $user = $request->user();

        $post->post_meta = [
            'reactions' => $this->moderation->tally($post, $user),
            'can' => $this->moderation->abilities($user, $post),
        ];

        $payload = CommunityPostResource::make($post)->resolve($request);

        return ApiResponse::success($payload, [], $status);
    }
}
