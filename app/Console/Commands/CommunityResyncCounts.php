<?php

namespace App\Console\Commands;

use App\Models\CommunityPost;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Đồng bộ lại comment_count (và like_count) của bài cộng đồng theo SỐ THẬT trong
 * bảng con. Bài seed / bài tạo trước khi có tăng đếm có thể lệch, làm feed hiện
 * sai số bình luận. Chạy 1 lần sau deploy:  php artisan community:resync-counts
 */
class CommunityResyncCounts extends Command
{
    protected $signature = 'community:resync-counts {--chunk=500}';

    protected $description = 'Đồng bộ comment_count/like_count bài cộng đồng theo số thật';

    public function handle(): int
    {
        $chunk = (int) $this->option('chunk');
        $n = 0;

        CommunityPost::withoutGlobalScopes()->chunkById($chunk, function ($posts) use (&$n) {
            foreach ($posts as $post) {
                $comments = DB::table('community_comments')
                    ->where('community_post_id', $post->id)
                    ->when(DB::getSchemaBuilder()->hasColumn('community_comments', 'status'),
                        fn ($q) => $q->where('status', 'visible'))
                    ->count();
                $likes = DB::table('community_post_reactions')
                    ->where('community_post_id', $post->id)
                    ->count();

                if ((int) $post->comment_count !== $comments || (int) $post->like_count !== $likes) {
                    $post->forceFill([
                        'comment_count' => $comments,
                        'like_count' => $likes,
                    ])->saveQuietly();
                    $n++;
                }
            }
        });

        $this->info("Đã đồng bộ $n bài.");

        return self::SUCCESS;
    }
}
