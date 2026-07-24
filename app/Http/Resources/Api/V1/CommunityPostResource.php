<?php

namespace App\Http\Resources\Api\V1;

use App\Support\DemoImage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @property \App\Models\CommunityPost $resource
 * Bài đăng cộng đồng (tab Cộng đồng — CD-CM-01). Ảnh từ cột `image_paths` (json);
 * nếu rỗng → 1 ảnh demo theo chủ đề (DemoImage) để feed luôn giàu hình ảnh.
 */
class CommunityPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $author = $this->whenLoaded('author');
        $role = null;
        $verified = false;
        if ($this->author) {
            $rel = $this->author->apartmentRelations->first();
            $role = $rel?->role ?? $this->author->relationship_to_head;
            $verified = $this->author->user_id !== null;
        }

        $images = collect($this->image_paths ?? [])
            ->map(fn ($p) => str_starts_with((string) $p, 'http') ? $p : Storage::disk('public')->url($p))
            ->values()
            ->all();
        if (empty($images)) {
            $images = [DemoImage::url('apartment,community,neighbor', $this->id)];
        }

        return [
            'id' => (string) $this->id,
            'author' => [
                'name' => $this->author?->full_name,
                'role' => $role,
                'avatar_url' => $this->author?->avatar_url,
                'verified' => $verified,
            ],
            'body' => $this->body,
            'likes' => (int) $this->like_count,
            'comments' => (int) $this->comment_count,
            'pinned' => (bool) $this->is_pinned,
            'important' => (bool) $this->is_important,
            'image_urls' => $images,
            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }
}
