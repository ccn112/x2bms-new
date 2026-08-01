<?php

namespace App\Console\Commands;

use App\Models\Attachment;
use App\Models\Comment;
use App\Models\CommunityComment;
use App\Models\CommunityPost;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * GĐ7 — chuyển bình luận cộng đồng từ bảng `comments` polymorphic sang bảng
 * chuyên dụng `community_comments`. Idempotent qua `legacy_comment_id` (không
 * copy trùng), giữ created_at, remap parent_id 2 cấp, và chuyển ảnh đính kèm
 * (attachable Comment → CommunityComment).
 *
 * Chạy sau khi deploy code đã trỏ endpoint sang bảng mới; các bình luận CŨ vẫn
 * hiện được sau khi migrate.
 */
class CommunityMigrateComments extends Command
{
    protected $signature = 'community:migrate-comments';

    protected $description = 'Chuyển bình luận cộng đồng: comments polymorphic → community_comments';

    public function handle(): int
    {
        $morph = (new CommunityPost())->getMorphClass();

        // Đã migrate trước đó: legacy_id → new_id.
        $map = CommunityComment::query()
            ->whereNotNull('legacy_comment_id')
            ->pluck('id', 'legacy_comment_id')->all();

        $old = Comment::withTrashed()
            ->where('commentable_type', $morph)
            ->orderBy('id')
            ->get();
        $todo = $old->whereNotIn('id', array_keys($map));

        $this->info("Cộng đồng: {$old->count()} bình luận, cần migrate {$todo->count()}.");
        if ($todo->isEmpty()) {
            return self::SUCCESS;
        }

        DB::transaction(function () use ($todo, &$map): void {
            foreach ($todo->whereNull('parent_id') as $c) {
                $map[$c->id] = $this->copy($c, null)->id;
            }
            foreach ($todo->whereNotNull('parent_id') as $c) {
                $map[$c->id] = $this->copy($c, $map[$c->parent_id] ?? null)->id;
            }

            $commentMorph = (new Comment())->getMorphClass();
            $newMorph = (new CommunityComment())->getMorphClass();
            foreach ($map as $legacy => $new) {
                Attachment::query()
                    ->where('attachable_type', $commentMorph)
                    ->where('attachable_id', $legacy)
                    ->update(['attachable_type' => $newMorph, 'attachable_id' => $new]);
            }
        });

        $this->info('Xong migrate bình luận cộng đồng.');

        return self::SUCCESS;
    }

    private function copy(Comment $c, ?int $parentId): CommunityComment
    {
        $post = CommunityPost::withoutGlobalScopes()->find($c->commentable_id);

        $new = CommunityComment::create([
            'community_post_id' => $c->commentable_id,
            'tenant_id' => $post?->tenant_id,
            'project_id' => $post?->project_id,
            'parent_id' => $parentId,
            'user_id' => $c->user_id,
            'author_name' => $c->author_name,
            'author_subtitle' => $c->author_subtitle,
            'author_kind' => $c->is_staff ? 'staff' : 'resident',
            'is_staff' => $c->is_staff,
            'body' => $c->body,
            'status' => $c->deleted_at ? 'deleted' : 'visible',
            'legacy_comment_id' => $c->id,
        ]);
        // Giữ mốc thời gian gốc (không để create() ghi đè bằng now()).
        $new->forceFill(['created_at' => $c->created_at, 'updated_at' => $c->updated_at])
            ->saveQuietly();

        return $new;
    }
}
