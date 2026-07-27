<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\Attachment $resource
 *
 * Kèm biến thể + kích thước thật (đề xuất
 * `x2mobile/docs/IMAGE_PIPELINE_PROPOSAL_20260727.md`).
 *
 * `width`/`height` để app dựng khung ảnh TRƯỚC khi tải xong — thiếu nó thì
 * layout nhảy khi cuộn feed. Ảnh upload trước pipeline chưa có biến thể:
 * `thumb_url`/`feed_url` tự rơi về ảnh gốc nên app không phải xử lý null.
 */
class AttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isImage = $this->isImage();

        return [
            'id' => (string) $this->id,
            'url' => $this->public_url,
            'file_name' => $this->file_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'is_image' => $isImage,

            'thumb_url' => $isImage ? $this->variantUrl('thumb') : null,
            'feed_url' => $isImage ? $this->variantUrl('feed') : null,
            'width' => $this->width,
            'height' => $this->height,
        ];
    }
}
