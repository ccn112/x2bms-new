<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\FeedbackCategoryResource;
use App\Http\Resources\Api\V1\FeedbackRequestResource;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\FeedbackCategory;
use App\Models\FeedbackRequest;
use App\Services\Resident\ResidentContextService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Phản ánh / yêu cầu dịch vụ (feedback_requests, canonical C3). Cư dân gửi phản ánh
 * gắn với căn của mình; BQL xử lý. Scope theo `resident_id ∈` resident của user
 * HOẶC `apartment_id ∈` căn của user. status enum: new|assigned|in_progress|resolved|closed.
 */
class FeedbackController extends ApiController
{
    public function __construct(private readonly ResidentContextService $context) {}

    /** GET /resident/feedback-categories — danh mục phản ánh của tenant user. */
    public function categories(Request $request): JsonResponse
    {
        $tenantIds = $this->context->tenantIds($request->user(), $request->header('X-Context-Id'));
        if (empty($tenantIds)) {
            return ApiResponse::success([]);
        }

        $categories = FeedbackCategory::query()
            ->whereIn('tenant_id', $tenantIds)
            ->orderBy('id')
            ->get();

        return ApiResponse::success(FeedbackCategoryResource::collection($categories)->resolve($request));
    }

    /** GET /resident/feedback?cursor= — phản ánh của user, mới nhất trước. */
    public function index(Request $request): JsonResponse
    {
        $apartmentIds = $this->context->apartmentIds($request->user(), $request->header('X-Context-Id'));
        $residentIds = $request->user()->residentMemberships()->pluck('id')->all();

        if (empty($apartmentIds) && empty($residentIds)) {
            return ApiResponse::paginated([], null);
        }

        $perPage = min((int) $request->integer('per_page', 20), 50);

        $paginator = FeedbackRequest::query()
            ->with('category')
            ->where(function ($q) use ($apartmentIds, $residentIds) {
                if (! empty($residentIds)) {
                    $q->orWhereIn('resident_id', $residentIds);
                }
                if (! empty($apartmentIds)) {
                    $q->orWhereIn('apartment_id', $apartmentIds);
                }
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate($perPage);

        $items = FeedbackRequestResource::collection($paginator->getCollection())->resolve($request);

        return ApiResponse::paginated($items, $paginator->nextCursor()?->encode());
    }

    /** POST /resident/feedback — tạo phản ánh gắn căn của user. */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'feedback_category_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'priority' => ['nullable', 'string', 'in:low,normal,high,urgent'],
        ]);

        $contextId = $request->header('X-Context-Id');
        $apartmentIds = $this->context->apartmentIds($request->user(), $contextId);
        if (empty($apartmentIds)) {
            return ApiResponse::error('no_apartment', 'Tài khoản chưa gắn căn hộ.', 403);
        }

        $apartment = Apartment::query()->find($apartmentIds[0]);
        if ($apartment === null || $apartment->building_id === null) {
            return ApiResponse::error('no_apartment', 'Không tìm thấy căn hộ.', 403);
        }

        $projectId = Building::query()->whereKey($apartment->building_id)->value('project_id');
        $residentId = $request->user()->residentMemberships()->value('id');

        $categoryId = null;
        if (! empty($validated['feedback_category_id'])) {
            $categoryId = FeedbackCategory::query()
                ->where('tenant_id', $apartment->tenant_id)
                ->where('id', $validated['feedback_category_id'])
                ->value('id');
        }

        $feedback = FeedbackRequest::create([
            'tenant_id' => $apartment->tenant_id,
            'building_id' => $apartment->building_id,
            'project_id' => $projectId,
            'apartment_id' => $apartment->id,
            'resident_id' => $residentId,
            'user_id' => $request->user()->id,
            'feedback_category_id' => $categoryId,
            'code' => 'PA'.Str::upper(Str::random(8)),
            'title' => $validated['title'],
            'description' => $validated['description'],
            'priority' => $validated['priority'] ?? 'normal',
            'channel' => 'app',
            'status' => 'new',
        ]);

        $feedback->load('category');

        return ApiResponse::success(FeedbackRequestResource::make($feedback)->resolve($request), [], 201);
    }

    /** GET /resident/feedback/{feedback} — chi tiết + timeline (bình luận công khai + lịch sử trạng thái). */
    public function show(Request $request, FeedbackRequest $feedback): JsonResponse
    {
        if (! $this->owns($request, $feedback)) {
            return ApiResponse::error('not_found', 'Không tìm thấy phản ánh.', 404);
        }

        $feedback->load(['category', 'comments' => fn ($q) => $q->where('is_internal', false)->orderBy('created_at'), 'statusHistories' => fn ($q) => $q->orderBy('created_at')]);

        $timeline = [];
        foreach ($feedback->comments as $c) {
            $timeline[] = [
                'type' => 'comment',
                'author' => $c->author_name,
                'body' => $c->body,
                'at' => optional($c->created_at)->toIso8601String(),
            ];
        }
        foreach ($feedback->statusHistories as $h) {
            $timeline[] = [
                'type' => 'status',
                'from_status' => $h->from_status,
                'to_status' => $h->to_status,
                'note' => $h->note,
                'at' => optional($h->changed_at ?? $h->created_at)->toIso8601String(),
            ];
        }

        $feedback->timeline = $timeline;

        return ApiResponse::success(FeedbackRequestResource::make($feedback)->resolve($request));
    }

    /**
     * PUT /resident/feedback/{feedback} — cư dân sửa phản ánh CỦA MÌNH, chỉ khi
     * BQL chưa tiếp nhận (status vẫn `new`). Sau khi BQL xử lý thì khoá sửa để
     * không đổi nội dung phía sau lưng người đang xử lý.
     */
    public function update(Request $request, FeedbackRequest $feedback): JsonResponse
    {
        if (! $this->owns($request, $feedback)) {
            return ApiResponse::error('not_found', 'Không tìm thấy phản ánh.', 404);
        }
        if ($this->statusValue($feedback) !== 'new') {
            return ApiResponse::error('not_editable',
                'Chỉ sửa được phản ánh khi Ban quản lý chưa tiếp nhận.', 422);
        }

        $validated = $request->validate([
            'feedback_category_id' => ['nullable', 'integer'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string', 'max:5000'],
            'priority' => ['sometimes', 'nullable', 'string', 'in:low,normal,high,urgent'],
        ]);

        $update = [];
        foreach (['title', 'description'] as $f) {
            if (array_key_exists($f, $validated)) {
                $update[$f] = $validated[$f];
            }
        }
        if (array_key_exists('priority', $validated) && $validated['priority'] !== null) {
            $update['priority'] = $validated['priority'];
        }
        if (array_key_exists('feedback_category_id', $validated)) {
            $update['feedback_category_id'] = $validated['feedback_category_id'] === null
                ? null
                : FeedbackCategory::query()
                    ->where('tenant_id', $feedback->tenant_id)
                    ->where('id', $validated['feedback_category_id'])
                    ->value('id');
        }

        $feedback->update($update);
        $feedback->load('category');

        return ApiResponse::success(FeedbackRequestResource::make($feedback)->resolve($request));
    }

    /** GET /resident/feedback/{feedback}/comments — bình luận công khai (2 chiều cư dân ↔ BQL). */
    public function comments(Request $request, FeedbackRequest $feedback): JsonResponse
    {
        if (! $this->owns($request, $feedback)) {
            return ApiResponse::error('not_found', 'Không tìm thấy phản ánh.', 404);
        }

        $userId = $request->user()->id;
        $comments = $feedback->comments()
            ->where('is_internal', false)
            ->orderBy('created_at')->orderBy('id')
            ->get()
            ->map(fn ($c) => $this->commentPayload($c, $userId))
            ->all();

        return ApiResponse::success($comments);
    }

    /** POST /resident/feedback/{feedback}/comments — cư dân trả lời trên phản ánh của mình. */
    public function storeComment(Request $request, FeedbackRequest $feedback): JsonResponse
    {
        if (! $this->owns($request, $feedback)) {
            return ApiResponse::error('not_found', 'Không tìm thấy phản ánh.', 404);
        }

        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);
        $user = $request->user();

        $comment = $feedback->comments()->create([
            'user_id' => $user->id,
            'resident_id' => $user->residentMemberships()->value('id'),
            'author_name' => $user->name,
            'body' => trim($data['body']),
            'is_internal' => false,
        ]);

        return ApiResponse::success($this->commentPayload($comment, $user->id), [], 201);
    }

    /** Sở hữu = căn hộ HOẶC resident HOẶC người tạo (khớp index/show). */
    private function owns(Request $request, FeedbackRequest $feedback): bool
    {
        $apartmentIds = $this->context->apartmentIds($request->user(), $request->header('X-Context-Id'));
        $residentIds = $request->user()->residentMemberships()->pluck('id')->all();

        return in_array($feedback->apartment_id, $apartmentIds, true)
            || ($feedback->resident_id !== null && in_array($feedback->resident_id, $residentIds, true))
            || $feedback->user_id === $request->user()->id;
    }

    private function statusValue(FeedbackRequest $feedback): string
    {
        return $feedback->status instanceof \BackedEnum
            ? $feedback->status->value
            : (string) $feedback->status;
    }

    /** @return array<string,mixed> */
    private function commentPayload(\App\Models\FeedbackComment $c, int $userId): array
    {
        return [
            'id' => (string) $c->id,
            'author' => $c->author_name,
            // Bình luận của cư dân mang resident_id; BQL trả lời thì không → nhãn.
            'is_staff' => $c->resident_id === null,
            'is_mine' => $c->user_id === $userId,
            'body' => $c->body,
            'at' => optional($c->created_at)->toIso8601String(),
        ];
    }
}
