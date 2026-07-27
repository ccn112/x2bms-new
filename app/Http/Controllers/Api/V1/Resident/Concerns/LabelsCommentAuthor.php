<?php

namespace App\Http\Controllers\Api\V1\Resident\Concerns;

use App\Models\Apartment;
use App\Services\Resident\ResidentContextService;
use Illuminate\Http\Request;

/**
 * Gắn nhãn tác giả cho bình luận/bài viết cư dân.
 *
 * Cư dân → tên thật + mã căn hộ. Nhân sự → nhãn chung "Ban quản lý", KHÔNG lộ
 * tên/ảnh cá nhân (chốt ở module bình luận 2026-07-25).
 *
 * NOTE: `NotificationController` và `SlipCommentController` đang có bản sao của
 * logic này viết tay. Nên gộp về đây khi có dịp đụng vào hai file đó — lần này
 * không sửa để tránh kéo theo rủi ro ngoài phạm vi slice cộng đồng.
 */
trait LabelsCommentAuthor
{
    /**
     * @return array{name:string,subtitle:?string,is_staff:bool}
     */
    protected function commentAuthor(
        Request $request,
        ResidentContextService $context,
        ?string $staffSubtitle = null,
    ): array {
        $user = $request->user();
        $isStaff = ! $user->hasResidentMembership() && $user->isStaffOperator();

        if ($isStaff) {
            return ['name' => 'Ban quản lý', 'subtitle' => $staffSubtitle, 'is_staff' => true];
        }

        $apartmentIds = $context->apartmentIds($user, $request->header('X-Context-Id'));

        return [
            'name' => $user->name,
            'subtitle' => $apartmentIds
                ? Apartment::query()->whereKey($apartmentIds[0])->value('code')
                : null,
            'is_staff' => false,
        ];
    }
}
