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
        $apartmentIds = $this->context->apartmentIds($request->user(), $request->header('X-Context-Id'));
        $residentIds = $request->user()->residentMemberships()->pluck('id')->all();

        $owns = in_array($feedback->apartment_id, $apartmentIds, true)
            || ($feedback->resident_id !== null && in_array($feedback->resident_id, $residentIds, true))
            || $feedback->user_id === $request->user()->id;
        if (! $owns) {
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
}
