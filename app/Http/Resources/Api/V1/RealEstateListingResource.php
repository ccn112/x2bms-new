<?php

namespace App\Http\Resources\Api\V1;

use App\Models\RealEstateListing;
use App\Support\DemoImage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property RealEstateListing $resource
 *                                       Tin BĐS nội khu (tab Chợ — mục BĐS, tách riêng khỏi market/*). `type` = sale|rent.
 *                                       `image_url` = ảnh demo theo chủ đề (DemoImage).
 *
 * `my_interest`/`my_inquiry_kinds`/`can` do controller gắn qua
 * `$listing->listing_meta` (xem `AttachesListingMeta`) — phụ thuộc người xem
 * hiện tại, gộp một lượt cho cả trang để tránh N+1.
 */
class RealEstateListingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $meta = $this->resource->listing_meta ?? [];
        $can = $meta['can'] ?? ['interest' => false, 'inquire' => false, 'withdraw' => false];

        return [
            'id' => (string) $this->id,
            'code' => $this->code,
            'type' => $this->type,
            'title' => $this->title,
            'price' => $this->price === null ? null : (string) $this->price,
            'area' => $this->area === null ? null : (string) $this->area,
            'bedrooms' => $this->bedrooms === null ? null : (int) $this->bedrooms,
            'owner' => $this->owner?->full_name,
            'apartment' => $this->apartment?->code,
            'image_url' => DemoImage::url('apartment,interior,realestate', $this->id),
            'published_at' => optional($this->published_at)->toIso8601String(),

            // Vòng đời giao dịch (active|pending|sold|rented|expired) — ĐỘC LẬP
            // với vòng đời duyệt bên dưới. Xem comment ở migration.
            'status' => $this->status,

            // Vòng đời DUYỆT (chốt 2026-07-30): pending|approved|rejected. Chủ
            // tin phải thấy được cả `rejection_reason` để biết vì sao — giống
            // nguyên tắc `moderation_reason` của bài cộng đồng.
            'approval_status' => $this->approval_status,
            'rejection_reason' => $this->rejection_reason,

            // Đếm sẵn (denormalized) — KHÔNG COUNT(*) khi vẽ thẻ, xem lý do ở
            // migration.
            'interest_count' => (int) $this->interest_count,
            'inquiry_count' => (int) $this->inquiry_count,

            'my_interest' => (bool) ($meta['my_interest'] ?? false),
            'my_inquiry_kinds' => $meta['my_inquiry_kinds'] ?? [],

            // Quyền do SERVER quyết — app chỉ vẽ nút theo đây, không tự suy từ
            // approval_status/status ở client.
            'can' => (object) $can,
        ];
    }
}
