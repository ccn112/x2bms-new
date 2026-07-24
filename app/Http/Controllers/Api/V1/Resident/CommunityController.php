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
    public function __construct(private readonly ResidentContextService $context)
    {
    }

    /** @return array<int> */
    private function projectIds(Request $request): array
    {
        return $this->context->projectIds($request->user(), $request->header('X-Context-Id'));
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
        if (empty($projectIds)) {
            return ApiResponse::paginated([], null);
        }

        $perPage = min((int) $request->integer('per_page', 15), 50);

        $paginator = CommunityPost::query()
            ->with('author.apartmentRelations')
            ->whereIn('project_id', $projectIds)
            ->where('status', 'published')
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate($perPage);

        $items = CommunityPostResource::collection($paginator->getCollection())->resolve($request);

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

        $paginator = Event::query()
            ->whereIn('project_id', $projectIds)
            ->where('status', 'published')
            ->orderBy('starts_at')
            ->orderByDesc('id')
            ->cursorPaginate($perPage);

        $registeredEventIds = empty($residentIds) ? [] : DB::table('event_registrations')
            ->whereIn('resident_id', $residentIds)
            ->whereIn('event_id', $paginator->getCollection()->pluck('id'))
            ->pluck('event_id')
            ->all();

        $paginator->getCollection()->each(function ($e) use ($registeredEventIds) {
            $e->registered = in_array($e->id, $registeredEventIds, true);
        });

        $items = EventResource::collection($paginator->getCollection())->resolve($request);

        return ApiResponse::paginated($items, $paginator->nextCursor()?->encode());
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
        $projectIds = $this->projectIds($request);
        if (empty($projectIds)) {
            return ApiResponse::success([]);
        }

        $groups = CommunityGroup::query()
            ->whereIn('project_id', $projectIds)
            ->where('status', 'active')
            ->orderByDesc('member_count')
            ->orderBy('name')
            ->get();

        $residentIds = $this->residentIds($request);
        $joinedIds = empty($residentIds) ? [] : CommunityGroupMember::query()
            ->whereIn('resident_id', $residentIds)
            ->whereIn('community_group_id', $groups->pluck('id'))
            ->pluck('community_group_id')
            ->all();

        $groups->each(fn ($g) => $g->joined = in_array($g->id, $joinedIds, true));

        return ApiResponse::success(CommunityGroupResource::collection($groups)->resolve($request));
    }

    /** POST /resident/community/groups/{group}/join */
    public function joinGroup(Request $request, CommunityGroup $group): JsonResponse
    {
        if (! in_array($group->project_id, $this->projectIds($request), true)) {
            return ApiResponse::error('not_found', 'Không tìm thấy nhóm.', 404);
        }

        $residentId = $request->user()->residentMemberships()->value('id');
        if ($residentId === null) {
            return ApiResponse::error('no_resident', 'Tài khoản chưa gắn cư dân.', 403);
        }

        $created = false;
        DB::transaction(function () use ($group, $residentId, &$created) {
            $member = CommunityGroupMember::firstOrCreate(
                ['community_group_id' => $group->id, 'resident_id' => $residentId],
                ['role' => 'member', 'joined_at' => now()],
            );
            if ($member->wasRecentlyCreated) {
                $group->increment('member_count');
                $created = true;
            }
        });

        $group->refresh();
        $group->joined = true;

        return ApiResponse::success(CommunityGroupResource::make($group)->resolve($request));
    }

    /** DELETE /resident/community/groups/{group}/join — rời nhóm. */
    public function leaveGroup(Request $request, CommunityGroup $group): JsonResponse
    {
        $residentId = $request->user()->residentMemberships()->value('id');
        if ($residentId === null) {
            return ApiResponse::error('no_resident', 'Tài khoản chưa gắn cư dân.', 403);
        }

        DB::transaction(function () use ($group, $residentId) {
            $deleted = CommunityGroupMember::query()
                ->where('community_group_id', $group->id)
                ->where('resident_id', $residentId)
                ->delete();
            if ($deleted > 0 && $group->member_count > 0) {
                $group->decrement('member_count');
            }
        });

        $group->refresh();
        $group->joined = false;

        return ApiResponse::success(CommunityGroupResource::make($group)->resolve($request));
    }
}
