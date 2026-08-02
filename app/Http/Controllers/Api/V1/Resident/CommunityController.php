<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\CommunityGroupResource;
use App\Http\Resources\Api\V1\CommunityPostResource;
use App\Http\Resources\Api\V1\EventResource;
use App\Http\Resources\Api\V1\PollResource;
use App\Models\CommunityGroup;
use App\Models\CommunityGroupMember;
use App\Models\CommunityPost;
use App\Models\Event;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\PollVote;
use App\Services\Resident\ResidentContextService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Tab Cộng đồng (CD-CM-*). Mọi dữ liệu scope theo DỰ ÁN của cư dân
 * (`project_id ∈ projectIds`) — cư dân tenant_id=NULL nên KHÔNG dựa tenant scope.
 * Xem docs/contracts/RESIDENT_API_DOMAIN.md §3.
 */
class CommunityController extends ApiController
{
    public function __construct(
        private readonly ResidentContextService $context,
        private readonly \App\Services\Resident\CommunityModerationService $moderation,
        private readonly \App\Services\Community\MembershipService $membership,
    ) {
    }

    /** @return array<int> */
    private function projectIds(Request $request): array
    {
        return $this->context->projectIds($request->user(), $request->header('X-Context-Id'));
    }

    /**
     * Người này có được xem nhóm đó không.
     *
     * Kiểm ở server chứ không tin `group_id` client gửi lên: đoán id nhóm là
     * việc dễ nhất trên đời, mà nhóm cư dân của dự án khác là nội dung nội bộ.
     */
    private function canSeeGroup(Request $request, int $groupId): bool
    {
        $group = CommunityGroup::withoutGlobalScopes()
            ->where('status', 'active')
            ->find($groupId);

        if ($group === null) {
            return false;
        }
        // Nhóm toàn hệ thống: ai đăng nhập cũng xem được.
        if ($group->kind === 'platform') {
            return true;
        }

        return in_array($group->project_id, $this->projectIds($request), true);
    }

    /** @return array<int> resident ids của user (cho registered/voted). */
    private function residentIds(Request $request): array
    {
        return $request->user()->residentMemberships()->pluck('id')->all();
    }

    /** GET /resident/community/posts?cursor= — pinned trước, mới nhất trước. */
    public function posts(Request $request): JsonResponse
    {
        $projectIds = $this->projectIds($request);
        $groupId = $request->integer('group_id') ?: null;

        // Nhóm `platform` KHÔNG gắn dự án — lọc theo projectIds sẽ loại sạch bài
        // của nó. Khi client hỏi đích danh một nhóm thì nhóm là phạm vi, không
        // cần (và không được) lọc thêm theo dự án.
        if ($groupId === null && empty($projectIds)) {
            return ApiResponse::paginated([], null);
        }

        if ($groupId !== null && ! $this->canSeeGroup($request, $groupId)) {
            return ApiResponse::error('forbidden', 'Bạn không xem được nhóm này.', 403);
        }

        $perPage = min((int) $request->integer('per_page', 15), 50);
        $user = $request->user();

        // Tab UI -> danh sách content_type. Ánh xạ ở SERVER: 'Thông báo BQL' gom
        // cả announcement lẫn news, app không việc gì phải biết chuyện đó.
        $types = \App\Enums\CommunityContentType::forTab((string) $request->string('tab', 'all'));

        $paginator = CommunityPost::withoutGlobalScopes()
            ->with(['author.apartmentRelations.apartment', 'attachments'])
            // Đếm bình luận THẬT (bảng community_comments GĐ7, chỉ visible) — KHÔNG
            // dùng withCount('comments') polymorphic cũ (luôn 0 với bài GĐ7).
            ->withCount(['communityComments as comments_count' => fn ($q) => $q->where('status', 'visible')])
            ->when($types !== null, fn ($q) => $q->whereIn('content_type', $types))
            ->when($groupId !== null,
                fn ($q) => $q->where('community_group_id', $groupId),
                fn ($q) => $q->whereIn('project_id', $projectIds))
            // Bài ẩn không vào feed của người khác; tác giả vẫn thấy bài mình
            // (kèm banner lý do) để không tưởng app lỗi rồi đăng lại.
            ->where(function ($q) use ($user) {
                $q->where('status', 'published')
                    ->orWhere(fn ($q2) => $q2->where('status', 'hidden')
                        ->where('author_user_id', $user?->id));
            })
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate($perPage);

        // Cảm xúc + quyền gộp MỘT lượt cho cả trang (tránh N+1 trên feed).
        $posts = $paginator->getCollection();
        $tallies = $this->moderation->tallyMany($posts->pluck('id')->all(), $user);
        foreach ($posts as $p) {
            $p->post_meta = [
                'reactions' => $tallies[$p->id] ?? ['summary' => [], 'total' => 0, 'mine' => null],
                'can' => $this->moderation->abilities($user, $p),
            ];
        }

        $items = CommunityPostResource::collection($posts)->resolve($request);

        return ApiResponse::paginated($items, $paginator->nextCursor()?->encode());
    }

    /** GET /resident/community/events?cursor= — sắp diễn ra trước. */
    public function events(Request $request): JsonResponse
    {
        $projectIds = $this->projectIds($request);
        if (empty($projectIds)) {
            return ApiResponse::paginated([], null);
        }

        $residentIds = $this->residentIds($request);
        $perPage = min((int) $request->integer('per_page', 15), 50);

        // Vòng đời sự kiện theo schema: upcoming|ongoing|finished|cancelled.
        // Cư dân thấy hai trạng thái đầu — `finished`/`cancelled` không còn để
        // đăng ký hay tham gia nữa.
        //
        // Trước 2026-07-30 chỗ này lọc `status = 'published'`, một giá trị KHÔNG
        // thuộc tập trên. Nó lọt vào qua seeder chép quy ước của bảng nội dung.
        // Trong khi đó form Filament tạo sự kiện mặc định `upcoming` → mọi sự
        // kiện Ban quản lý tạo qua web đều không cư dân nào xem được.
        $paginator = Event::query()
            ->whereIn('project_id', $projectIds)
            ->whereIn('status', Event::RESIDENT_VISIBLE_STATUSES)
            ->orderBy('starts_at')
            ->orderByDesc('id')
            ->cursorPaginate($perPage);

        // Trạng thái đăng ký của CHÍNH người đang xem, gộp một lượt cho cả trang.
        // Lấy cả `status` chứ không chỉ "có dòng hay không": app cần phân biệt
        // đã đăng ký / đã check-in / đã huỷ để chọn đúng nút.
        $myRegistrations = empty($residentIds) ? [] : DB::table('event_registrations')
            ->whereIn('resident_id', $residentIds)
            ->whereIn('event_id', $paginator->getCollection()->pluck('id'))
            ->pluck('status', 'event_id')
            ->all();

        $paginator->getCollection()->each(function ($e) use ($myRegistrations) {
            $status = $myRegistrations[$e->id] ?? null;
            $e->registration_status = $status;
            $e->registered = $status === 'registered' || $status === 'attended';
            $e->can_check_in = $status === 'registered' && $this->isCheckInWindow($e);
        });

        $items = EventResource::collection($paginator->getCollection())->resolve($request);

        return ApiResponse::paginated($items, $paginator->nextCursor()?->encode());
    }

    /**
     * Cửa sổ check-in: từ 2 GIỜ TRƯỚC giờ bắt đầu tới hết giờ kết thúc (không có
     * `ends_at` thì tính 4 giờ sau giờ bắt đầu).
     *
     * Mở sớm 2 giờ vì cư dân tới trước giờ diễn ra là chuyện thường; đóng theo
     * `ends_at` để không ai check-in một sự kiện đã tan.
     */
    private function isCheckInWindow(Event $e): bool
    {
        if ($e->starts_at === null) {
            return false;
        }
        $from = $e->starts_at->copy()->subHours(2);
        $to = $e->ends_at ?? $e->starts_at->copy()->addHours(4);

        return now()->betweenIncluded($from, $to);
    }

    /**
     * Sự kiện cư dân được tương tác: phải thuộc dự án của ngữ cảnh đang chọn.
     *
     * Kiểm ở server chứ không tin id client gửi lên — đoán id sự kiện là việc dễ
     * nhất trên đời, mà sự kiện của dự án khác là nội dung nội bộ (và có thể
     * thuộc tenant khác).
     */
    private function findEventInScope(Request $request, Event $event): ?Event
    {
        $projectIds = $this->projectIds($request);

        return in_array($event->project_id, $projectIds, true) ? $event : null;
    }

    /** Resident id dùng để ghi đăng ký — căn đang chọn, không phải căn bất kỳ. */
    private function actingResidentId(Request $request): ?int
    {
        $residentIds = $this->residentIds($request);

        return $residentIds[0] ?? null;
    }

    /**
     * POST /resident/community/events/{event}/register — đăng ký tham gia.
     *
     * Idempotent: đã đăng ký rồi thì trả về trạng thái hiện tại chứ không tạo
     * dòng thứ hai (bấm hai lần vì mạng chậm là chuyện thường).
     */
    public function registerEvent(Request $request, Event $event): JsonResponse
    {
        $e = $this->findEventInScope($request, $event);
        if ($e === null) {
            return ApiResponse::error('forbidden', 'Bạn không xem được sự kiện này.', 403);
        }
        if (! in_array($e->status, Event::RESIDENT_VISIBLE_STATUSES, true)) {
            return ApiResponse::error('event_closed', 'Sự kiện không còn nhận đăng ký.', 422);
        }

        $residentId = $this->actingResidentId($request);
        if ($residentId === null) {
            return ApiResponse::error('no_resident', 'Tài khoản chưa gắn căn hộ.', 422);
        }

        $existing = DB::table('event_registrations')
            ->where('event_id', $e->id)->where('resident_id', $residentId)->first();

        if ($existing && $existing->status !== 'cancelled') {
            return $this->eventPayload($request, $e->fresh(), $existing->status);
        }

        // Hết chỗ thì chặn — nhưng CHỈ khi capacity có giá trị (null = không giới hạn).
        if ($e->capacity !== null && (int) $e->registered_count >= (int) $e->capacity) {
            return ApiResponse::error('event_full', 'Sự kiện đã hết chỗ.', 422);
        }

        DB::transaction(function () use ($e, $residentId, $existing) {
            if ($existing) {
                DB::table('event_registrations')
                    ->where('id', $existing->id)
                    ->update(['status' => 'registered', 'updated_at' => now()]);
            } else {
                DB::table('event_registrations')->insert([
                    'event_id' => $e->id,
                    'resident_id' => $residentId,
                    'guests' => 0,
                    'status' => 'registered',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            // increment nguyên tử, không đọc-rồi-ghi: hai người đăng ký cùng lúc
            // mà cộng ở PHP thì mất một lượt.
            Event::withoutGlobalScopes()->where('id', $e->id)->increment('registered_count');
        });

        return $this->eventPayload($request, $e->fresh(), 'registered');
    }

    /** DELETE /resident/community/events/{event}/register — huỷ đăng ký. */
    public function cancelEventRegistration(Request $request, Event $event): JsonResponse
    {
        $e = $this->findEventInScope($request, $event);
        if ($e === null) {
            return ApiResponse::error('forbidden', 'Bạn không xem được sự kiện này.', 403);
        }

        $residentId = $this->actingResidentId($request);
        $existing = $residentId === null ? null : DB::table('event_registrations')
            ->where('event_id', $e->id)->where('resident_id', $residentId)->first();

        if ($existing === null || $existing->status === 'cancelled') {
            return ApiResponse::error('not_registered', 'Bạn chưa đăng ký sự kiện này.', 422);
        }
        // Đã check-in thì không huỷ được: người ta đã tới dự rồi.
        if ($existing->status === 'attended') {
            return ApiResponse::error('already_attended', 'Bạn đã check-in, không huỷ được.', 422);
        }

        DB::transaction(function () use ($e, $existing) {
            DB::table('event_registrations')
                ->where('id', $existing->id)
                ->update(['status' => 'cancelled', 'updated_at' => now()]);
            // Sàn 0: dữ liệu cũ có thể lệch, trừ xuống âm thì hiển thị vô nghĩa.
            Event::withoutGlobalScopes()->where('id', $e->id)
                ->where('registered_count', '>', 0)->decrement('registered_count');
        });

        return $this->eventPayload($request, $e->fresh(), 'cancelled');
    }

    /** POST /resident/community/events/{event}/check-in — điểm danh tại sự kiện. */
    public function checkInEvent(Request $request, Event $event): JsonResponse
    {
        $e = $this->findEventInScope($request, $event);
        if ($e === null) {
            return ApiResponse::error('forbidden', 'Bạn không xem được sự kiện này.', 403);
        }

        $residentId = $this->actingResidentId($request);
        $existing = $residentId === null ? null : DB::table('event_registrations')
            ->where('event_id', $e->id)->where('resident_id', $residentId)->first();

        if ($existing === null || $existing->status === 'cancelled') {
            return ApiResponse::error('not_registered', 'Bạn cần đăng ký trước khi check-in.', 422);
        }
        if ($existing->status === 'attended') {
            return $this->eventPayload($request, $e, 'attended');
        }
        if (! $this->isCheckInWindow($e)) {
            return ApiResponse::error('check_in_closed',
                'Chỉ check-in được trong khoảng từ 2 giờ trước tới khi sự kiện kết thúc.', 422);
        }

        DB::table('event_registrations')
            ->where('id', $existing->id)
            ->update(['status' => 'attended', 'updated_at' => now()]);

        return $this->eventPayload($request, $e, 'attended');
    }

    /** Trả sự kiện đã gắn trạng thái đăng ký — app thay thẳng item trong danh sách. */
    private function eventPayload(Request $request, Event $e, ?string $status): JsonResponse
    {
        $e->registration_status = $status;
        $e->registered = $status === 'registered' || $status === 'attended';
        $e->can_check_in = $status === 'registered' && $this->isCheckInWindow($e);

        return ApiResponse::success(EventResource::make($e)->resolve($request));
    }

    /** GET /resident/community/polls — poll đang mở + trạng thái đã vote của user. */
    public function polls(Request $request): JsonResponse
    {
        $projectIds = $this->projectIds($request);
        if (empty($projectIds)) {
            return ApiResponse::success([]);
        }

        $residentIds = $this->residentIds($request);

        $polls = Poll::query()
            ->with('options')
            ->whereIn('project_id', $projectIds)
            ->where('status', 'open')
            ->orderByDesc('id')
            ->get();

        $myVotes = empty($residentIds) ? collect() : PollVote::query()
            ->whereIn('resident_id', $residentIds)
            ->whereIn('poll_id', $polls->pluck('id'))
            ->get()
            ->keyBy('poll_id');

        $polls->each(function ($p) use ($myVotes) {
            $vote = $myVotes->get($p->id);
            $p->voted = $vote !== null;
            $p->voted_option_id = $vote?->poll_option_id;
        });

        return ApiResponse::success(PollResource::collection($polls)->resolve($request));
    }

    /** POST /resident/community/polls/{poll}/vote {option_id} — 1 vote / poll / resident. */
    public function vote(Request $request, Poll $poll): JsonResponse
    {
        $validated = $request->validate([
            'option_id' => ['required', 'integer'],
        ]);

        if ($poll->status !== 'open') {
            return ApiResponse::error('poll_closed', 'Khảo sát đã đóng.', 422);
        }

        $option = PollOption::query()
            ->where('poll_id', $poll->id)
            ->find($validated['option_id']);
        if ($option === null) {
            return ApiResponse::error('invalid_option', 'Lựa chọn không thuộc khảo sát này.', 422);
        }

        $residentId = $request->user()->residentMemberships()->value('id');
        if ($residentId === null) {
            return ApiResponse::error('no_resident', 'Tài khoản chưa gắn cư dân.', 403);
        }

        $already = PollVote::query()
            ->where('poll_id', $poll->id)
            ->where('resident_id', $residentId)
            ->exists();
        if ($already) {
            return ApiResponse::error('already_voted', 'Bạn đã bình chọn khảo sát này.', 409);
        }

        DB::transaction(function () use ($poll, $option, $residentId) {
            PollVote::create([
                'poll_id' => $poll->id,
                'poll_option_id' => $option->id,
                'resident_id' => $residentId,
            ]);
            $option->increment('vote_count');
            $poll->increment('vote_count');
        });

        $poll->load('options');
        $poll->voted = true;
        $poll->voted_option_id = $option->id;

        return ApiResponse::success(PollResource::make($poll)->resolve($request));
    }

    /** GET /resident/community/groups — nhóm cộng đồng của dự án (+ đã tham gia?). */
    public function groups(Request $request): JsonResponse
    {
        return ApiResponse::success(
            CommunityGroupResource::collection($this->residentGroups($request))->resolve($request)
        );
    }

    /**
     * Danh sách nhóm cộng đồng của cư dân (bậc thang RỘNG→HẸP) kèm cờ `joined`.
     * Dùng chung cho [groups] và [bootstrap] (GĐ5) — một nguồn truy vấn.
     *
     * Bậc thang (chốt 29/07): cả hệ thống → dự án quan tâm → dự án đang ở → nhóm
     * riêng. `platform` không gắn dự án nên trả kể cả khi chưa gắn căn hộ.
     *
     * @return \Illuminate\Support\Collection<int, CommunityGroup>
     */
    private function residentGroups(Request $request): \Illuminate\Support\Collection
    {
        $projectIds = $this->projectIds($request);

        $groups = CommunityGroup::withoutGlobalScopes()
            ->where('status', 'active')
            ->where(function ($q) use ($projectIds) {
                $q->where('kind', 'platform');
                if ($projectIds !== []) {
                    $q->orWhere(fn ($w) => $w
                        ->whereIn('project_id', $projectIds)
                        ->whereIn('kind', ['project_interest', 'project_resident', 'private']));
                }
            })
            // CASE WHEN thay vì FIELD() (MySQL-only, vỡ trên SQLite của test suite).
            ->orderByRaw("CASE kind WHEN 'platform' THEN 0 WHEN 'project_interest' THEN 1 WHEN 'project_resident' THEN 2 WHEN 'private' THEN 3 ELSE 4 END")
            ->orderByDesc('is_default')
            ->orderByDesc('member_count')
            ->orderBy('name')
            ->get();

        // "Đã tham gia" phải loại membership đã `left_at` (GĐ3, 2026-08-01): từ
        // khi `leaveGroup()` không xoá cứng nữa (`MembershipService::revokeManualJoin()`
        // chỉ đánh dấu `left_at`), thiếu điều kiện này thì rời nhóm xong vẫn `joined=true`.
        $residentIds = $this->residentIds($request);
        $joinedIds = empty($residentIds) ? [] : CommunityGroupMember::query()
            ->whereIn('resident_id', $residentIds)
            ->whereIn('community_group_id', $groups->pluck('id'))
            ->whereNull('left_at')
            ->pluck('community_group_id')
            ->all();

        $groups->each(fn ($g) => $g->joined = in_array($g->id, $joinedIds, true));

        return $groups;
    }

    /**
     * GET resident/community/bootstrap — GĐ5. Gom MỌI thứ cần khi MỞ tab Cộng
     * đồng vào một call (tier, phạm vi feed, tabs, nhóm, dự án theo dõi, quyền
     * soạn), thay vì app gọi rời 3-4 endpoint. Theo hợp đồng
     * `handoff/x2mobile/…_COMMUNITY_DOMAIN_HANDOFF_20260729/docs/07_API_CONTRACT.md §3`.
     */
    public function bootstrap(Request $request): JsonResponse
    {
        $user = $request->user();

        // Tier: có quan hệ căn hộ (residentIds) ⇒ verified_resident; còn lại
        // (member thuần, chưa gắn căn) ⇒ member. Không đoán từ token — dựa dữ liệu.
        $tier = empty($this->residentIds($request)) ? 'member' : 'verified_resident';

        $follows = \App\Models\UserProjectFollow::query()
            ->where('user_id', $user->id)
            ->with('project')
            ->orderByDesc('followed_at')
            ->get()
            ->map(fn ($f) => [
                'project_id' => (string) $f->project_id,
                'project_name' => $f->project?->name,
                'followed_at' => $f->followed_at?->toIso8601String(),
            ])->all();

        return ApiResponse::success([
            'identity_tier' => $tier,
            // v1: ngữ cảnh căn hộ đang chọn nằm ở `me/bootstrap`; ở đây để null,
            // app đã có context riêng. Có thể làm giàu sau nếu cần.
            'current_context' => null,
            'default_feed_scope' => 'for_you',
            'available_feed_scopes' => ['for_you', 'latest', 'x2living', 'following_projects'],
            'tabs' => \App\Enums\CommunityContentType::tabs(),
            'groups' => CommunityGroupResource::collection($this->residentGroups($request))->resolve($request),
            'project_follows' => $follows,
            'composer' => ['enabled' => true, 'allowed_types' => ['status', 'link_share']],
            'capabilities' => (object) [],
        ]);
    }

    /**
     * POST /resident/community/groups/{group}/join — tham gia thủ công
     * (`manual_join` grant, Giai đoạn 3). Đi qua `MembershipService` thay vì
     * tự `firstOrCreate`/`delete` để bất biến "còn active grant thì còn
     * membership" luôn đúng cho MỌI đường vào, không chỉ đường quan hệ căn hộ.
     */
    public function joinGroup(Request $request, CommunityGroup $group): JsonResponse
    {
        if (! in_array($group->project_id, $this->projectIds($request), true)) {
            return ApiResponse::error('not_found', 'Không tìm thấy nhóm.', 404);
        }

        $resident = $request->user()->residentMemberships()->first();
        if ($resident === null) {
            return ApiResponse::error('no_resident', 'Tài khoản chưa gắn cư dân.', 403);
        }

        $this->membership->grantManualJoin($group, $resident);

        $group->refresh();
        $group->joined = true;

        return ApiResponse::success(CommunityGroupResource::make($group)->resolve($request));
    }

    /**
     * DELETE /resident/community/groups/{group}/join — rời nhóm.
     *
     * Chỉ thu hồi grant loại `manual_join`. Một nhóm `is_default` (vd
     * `official_resident_group` cấp qua quan hệ căn hộ) không có grant
     * `manual_join` nào để thu hồi — gọi vào đây là no-op (client đúng chuẩn
     * đã ẩn nút rời cho nhóm `is_default` qua `capabilities.can_leave`, đây là
     * lớp phòng thủ phía server cho request gọi thẳng).
     */
    public function leaveGroup(Request $request, CommunityGroup $group): JsonResponse
    {
        $resident = $request->user()->residentMemberships()->first();
        if ($resident === null) {
            return ApiResponse::error('no_resident', 'Tài khoản chưa gắn cư dân.', 403);
        }

        $this->membership->revokeManualJoin($group, $resident);

        $group->refresh();
        $group->joined = false;

        return ApiResponse::success(CommunityGroupResource::make($group)->resolve($request));
    }
}
