<?php

namespace App\Models\Concerns;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Gắn khả năng đính kèm (polymorphic) — dùng cho bình luận và các phiếu tương tác
 * (đăng ký khách, chuyển đồ, đặt tiện ích…). Song song với [HasComments].
 */
trait HasAttachments
{
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')->orderBy('order_column');
    }

    /**
     * Link các attachment mới upload (attachable null, thuộc $userId) vào model này.
     * @param  array<int|string>  $attachmentIds
     */
    public function linkAttachments(array $attachmentIds, ?int $userId): void
    {
        $ids = array_filter(array_map('intval', $attachmentIds));
        if (empty($ids)) {
            return;
        }
        Attachment::query()
            ->whereIn('id', $ids)
            ->whereNull('attachable_id')
            ->when($userId !== null, fn ($q) => $q->where('uploaded_by', $userId))
            ->update([
                'attachable_type' => $this->getMorphClass(),
                'attachable_id' => $this->getKey(),
            ]);
    }
}
