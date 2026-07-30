<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Comment $resource
 *                             Bình luận dùng chung (thông báo/cộng đồng/phản ánh/ticket). `is_mine` set kèm
 *                             (transient) bởi controller. Nhân sự BQL hiển thị "Ban quản lý · dự án", KHÔNG
 *                             lộ tên/ảnh cá nhân.
 */
class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $staff = (bool) $this->is_staff;

        return [
            'id' => (string) $this->id,
            'parent_id' => $this->parent_id ? (string) $this->parent_id : null,
            'body' => $this->body,
            'is_staff' => $staff,
            'author' => [
                'name' => $this->author_name ?? $this->user?->name ?? 'Cư dân',
                'subtitle' => $this->author_subtitle,
                'avatar_url' => $staff ? null : $this->user?->avatar_url,
            ],
            'is_mine' => (bool) ($this->is_mine ?? false),
            // Số trả lời — chỉ có khi controller `withCount('replies')`. App dùng
            // để vẽ "Xem N trả lời" mà KHÔNG phải nạp sẵn cây trả lời: ở bài có
            // hàng nghìn bình luận, nạp hết mọi cấp là chỗ vỡ đầu tiên.
            'reply_count' => $this->replies_count === null
                ? null
                : (int) $this->replies_count,
            'created_at' => $this->created_at?->toIso8601String(),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
        ];
    }
}
