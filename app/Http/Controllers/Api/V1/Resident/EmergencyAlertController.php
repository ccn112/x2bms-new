<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\EmergencyAlertResource;
use App\Services\Resident\EmergencyAlertService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/v1/resident/emergency-alerts{,/{id}} — cảnh báo khẩn cấp cư dân
 * (CD-HOME-04). Bảng `emergency_alerts` đã có sẵn (Tier 2) + Filament resource
 * cho BQL soạn; đây chỉ là lớp đọc phía cư dân.
 *
 * KHÔNG dùng route model binding: cư dân có tenant_id = NULL nên binding mặc
 * định sẽ tìm cả bản ghi ngoài phạm vi dự án của họ. Phải resolve qua query đã
 * scope tường minh.
 */
class EmergencyAlertController extends ApiController
{
    public function __construct(private readonly EmergencyAlertService $alerts)
    {
    }

    /** GET /resident/emergency-alerts — cảnh báo ĐANG hiệu lực, nặng trước. */
    public function index(Request $request): JsonResponse
    {
        $items = $this->alerts
            ->activeQuery($request->user(), $request->header('X-Context-Id'))
            ->with(['building:id,name', 'project:id,name'])
            ->orderByRaw("FIELD(severity, 'critical', 'warning', 'info')")
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        return ApiResponse::success(
            EmergencyAlertResource::collection($items)->resolve($request)
        );
    }

    /**
     * GET /resident/emergency-alerts/{id} — chi tiết, KỂ CẢ đã `resolved`: cư
     * dân mở từ push cũ vẫn phải đọc được nội dung và thấy nó đã kết thúc, thay
     * vì nhận 404 không hiểu chuyện gì.
     */
    public function show(Request $request, string $alert): JsonResponse
    {
        $model = $this->alerts
            ->scopedQuery($request->user(), $request->header('X-Context-Id'))
            ->with(['building:id,name', 'project:id,name'])
            ->find((int) $alert);

        if ($model === null) {
            return ApiResponse::error('not_found', 'Không tìm thấy cảnh báo.', 404);
        }

        $model->contacts = $this->alerts->contactsForProject($model->project_id);

        return ApiResponse::success(
            EmergencyAlertResource::make($model)->resolve($request)
        );
    }
}
