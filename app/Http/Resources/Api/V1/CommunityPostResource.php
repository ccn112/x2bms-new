<?php

namespace App\Http\Resources\Api\V1;

use App\Support\DemoImage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @property \App\Models\CommunityPost $resource
 * Bài đăng cộng đồng (tab Cộng đồng — CD-CM-01).
 *
 * Ảnh: cột `image_paths` (bài seeder) hoặc attachment polymorphic (bài cư dân
 * đăng từ app). Bài SEEDER không ảnh thì mượn 1 ảnh demo cho feed đỡ trống; bài
 * NGƯỜI THẬT đăng chay bằng chữ thì trả mảng RỖNG — nếu không, bài text sẽ tự
 * mọc một tấm ảnh chẳng liên quan.
 *
 * `reactions`/`can` do controller bơm vào qua `additional('post_meta')`: chúng
 * phụ thuộc user hiện tại và được gộp nhiều bài một lượt để tránh N+1.
 */
class CommunityPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $role = null;
        $verified = false;
        if ($this->author) {
            $rel = $this->author->apartmentRelations->first();
            $role = $rel?->role ?? $this->author->relationship_to_head;
            $verified = $this->author->user_id !== null;
        }

        $meta = $this->postMeta();
        $tally = $meta['reactions'] ?? ['summary' => [], 'total' => 0, 'mine' => null];

        return [
            'id' => (string) $this->id,
            'author' => [
                // user_id để app cho @mention TÁC GIẢ BÀI trong bình luận. Ẩn với
                // bài của BQL (nhân sự không phải cư dân để nhắc).
                'user_id' => ($this->author_kind === 'staff' || $this->author_user_id === null)
                    ? null : (string) $this->author_user_id,
                'name' => $this->authorName(),
                'role' => $role,
                'avatar_url' => $this->authorAvatar(),
                'verified' => $verified,
                'kind' => $this->author_kind ?: 'resident',
                'subtitle' => $this->authorSubtitle(),
            ],
            'body' => $this->body,
            // Loại nội dung + entity gốc. App lọc tab bằng tham số `tab` phía
            // server, nhưng vẫn cần hai trường này để VẼ đúng: bài `event_ref`
            // phải ra thẻ sự kiện có nút đăng ký, không phải một đoạn chữ trơn.
            // `source` chỉ là con trỏ (type+id) — feed không giữ bản sao nội
            // dung, tránh hai nguồn sự thật rồi lệch nhau.
            'content_type' => $this->content_type ?: 'status',
            'source' => $this->source_type === null ? null : [
                'type' => $this->source_type,
                'id' => (string) $this->source_id,
            ],
            // `likes` = TỔNG mọi cảm xúc (không riêng 'like') để code cũ đọc cột
            // này không vỡ khi chuyển sang hệ reaction nhiều loại.
            'likes' => (int) $tally['total'],
            'comments' => (int) ($this->comments_count ?? $this->comment_count ?? 0),
            'shares' => (int) ($this->share_count ?? 0),
            'pinned' => (bool) $this->is_pinned,
            'important' => (bool) $this->is_important,
            // `image_urls` giữ nguyên cho app bản cũ; `images` là bản giàu có
            // biến thể + kích thước để dựng layout không nhảy khung.
            'image_urls' => $this->imageUrls(),
            'images' => $this->images(),
            'created_at' => optional($this->created_at)->toIso8601String(),

            'reactions' => [
                'summary' => (object) $tally['summary'],
                'total' => (int) $tally['total'],
                'mine' => $tally['mine'],
            ],
            // Người đã thả cảm xúc (cư dân) — app gộp vào gợi ý @mention cùng
            // người đã bình luận + tác giả. [{user_id, name}], có thể rỗng.
            'reactors' => array_values($meta['reactor_people'] ?? []),
            'locked' => $this->locked_at !== null,
            'hidden' => $this->status === 'hidden',
            'moderation_reason' => $this->moderation_reason,
            'can' => (object) ($meta['can'] ?? []),
        ];
    }

    /**
     * Meta do controller gắn thẳng lên model (`$post->post_meta = [...]`).
     *
     * KHÔNG dùng `->additional()`: với `Resource::collection()` thì additional
     * chỉ nằm ở lớp bọc collection, từng item con không nhìn thấy — feed sẽ trả
     * `can: {}` cho mọi bài. Gắn lên model là cách `is_mine` của module bình
     * luận đang làm.
     *
     * @return array<string,mixed>
     */
    private function postMeta(): array
    {
        return $this->resource->post_meta ?? [];
    }

    /** Nhân sự BQL đăng thì KHÔNG lộ danh tính cá nhân — giống module bình luận. */
    private function authorName(): ?string
    {
        if ($this->author_kind === 'staff') {
            return 'Ban quản lý';
        }

        return $this->author?->full_name ?? $this->authorUser?->name;
    }

    private function authorSubtitle(): ?string
    {
        if ($this->author_kind === 'staff') {
            return null;
        }

        return $this->author?->apartmentRelations->first()?->apartment?->code;
    }

    private function authorAvatar(): ?string
    {
        return $this->author_kind === 'staff' ? null : $this->author?->avatar_url;
    }

    /**
     * Ảnh dạng đầy đủ: mỗi phần tử có `url`/`thumb_url`/`feed_url`/`width`/`height`.
     * Bài seeder (chỉ có `image_paths`, không attachment) thì các biến thể trỏ
     * cùng một URL và kích thước để null — app tự rơi về khung mặc định.
     *
     * @return array<int,array<string,mixed>>
     */
    private function images(): array
    {
        if ($this->relationLoaded('attachments')) {
            $rows = $this->attachments
                ->filter(fn ($a) => str_starts_with((string) $a->mime_type, 'image/'))
                ->map(fn ($a) => [
                    'url' => $a->public_url,
                    'thumb_url' => $a->variantUrl('thumb'),
                    'feed_url' => $a->variantUrl('feed'),
                    'width' => $a->width,
                    'height' => $a->height,
                ])
                ->values()
                ->all();
            if (! empty($rows)) {
                return $rows;
            }
        }

        return array_map(fn (string $u) => [
            'url' => $u,
            'thumb_url' => $u,
            'feed_url' => $u,
            'width' => null,
            'height' => null,
        ], $this->imageUrls());
    }

    /** @return array<int,string> */
    private function imageUrls(): array
    {
        $images = collect($this->image_paths ?? [])
            ->map(fn ($p) => str_starts_with((string) $p, 'http') ? $p : Storage::disk('public')->url($p))
            ->values()
            ->all();

        if (empty($images) && $this->relationLoaded('attachments')) {
            $images = $this->attachments
                ->filter(fn ($a) => str_starts_with((string) $a->mime_type, 'image/'))
                ->map(fn ($a) => Storage::disk($a->disk ?: 'public')->url($a->path))
                ->values()
                ->all();
        }

        // CHỈ bài seeder (không gắn tài khoản người đăng) mới mượn ảnh demo.
        if (empty($images) && $this->author_user_id === null) {
            $images = [DemoImage::url('apartment,community,neighbor', $this->id)];
        }

        return $images;
    }
}
