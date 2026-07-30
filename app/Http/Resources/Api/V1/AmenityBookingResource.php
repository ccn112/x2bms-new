<?php

namespace App\Http\Resources\Api\V1;

use App\Models\AmenityBooking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property AmenityBooking $resource
 *                                    Lượt đặt tiện ích của cư dân. Tiền là chuỗi decimal.
 */
class AmenityBookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'code' => $this->code,
            'amenity' => $this->whenLoaded('amenity', fn () => [
                'id' => (string) $this->amenity->id,
                'name' => $this->amenity->name,
            ]),
            'booking_date' => optional($this->booking_date)->toDateString(),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'party_size' => (int) $this->party_size,
            'status' => $this->status,
            'price' => $this->price === null ? null : (string) $this->price,
            'note' => $this->note,
            // Mốc từng bước cho dòng thời gian phiếu (BookingDetailScreen) — chỉ
            // trả khi backend THẬT SỰ có dữ liệu, không suy đoán:
            // - requested_at: lúc tạo phiếu, luôn có.
            // - decided_at: dùng chung cho lúc BQL xác nhận HOẶC từ chối (một
            //   quyết định, một thời điểm) — cột `approved_at` có sẵn.
            // - cancelled_at: hành động của CƯ DÂN, khác chủ thể với decided_at.
            // - completed_at: lấy từ vé QR (`qrPass.used_at`) — thời điểm THỰC
            //   SỰ dùng tiện ích, không phải lúc tạo hay lúc được duyệt.
            'requested_at' => $this->created_at?->toIso8601String(),
            'decided_at' => $this->approved_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'completed_at' => $this->whenLoaded('qrPass', fn () => $this->qrPass?->used_at?->toIso8601String()),
            // Số bình luận trao đổi với BQL — cần controller `withCount('comments')`,
            // cùng cơ chế `reply_count` ở CommentResource.
            'comment_count' => $this->comments_count === null ? 0 : (int) $this->comments_count,
        ];
    }
}
