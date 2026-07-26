<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\PlatformContent $resource
 *
 * Bài viết cho cư dân. `scope` (publish_scope) cho app biết tầng quản lý để hiện
 * nhãn (SuperAdmin / Công ty / BQL). `body` là nội dung đầy đủ (list mới cần
 * summary; chi tiết dùng body).
 */
class PlatformArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'summary' => $this->summary,
            'body' => $this->body,
            'content_type' => $this->content_type,   // policy | guide | news
            'scope' => $this->publish_scope,          // platform | tenant | building | …
            'category' => $this->whenLoaded('category', fn () => $this->category?->name),
            'cover_url' => $this->cover_image,
            'published_at' => $this->published_at?->toIso8601String(),
        ];
    }
}
