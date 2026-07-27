<?php

namespace App\Services\Resident;

use App\Models\CommunityPost;
use App\Models\CommunityPostReaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Quyền + tally cảm xúc cho bài cộng đồng.
 * Xem docs/COMMUNITY_WRITE_MODERATION_DESIGN.md.
 *
 * Nguyên tắc: **quyền do SERVER tính** rồi trả xuống app qua khối `can{}`. App
 * không suy vai trò từ `abilities` để bật/tắt nút — nếu để client tự đoán thì
 * sớm muộn client và server sẽ hiểu quyền khác nhau.
 */
class CommunityModerationService
{
    /**
     * Nhân sự có được kiểm duyệt bài này không: phải là staff operator VÀ bài
     * nằm trong phạm vi dự án họ phụ trách (platform admin thì không giới hạn).
     */
    public function canModerate(?User $user, CommunityPost $post): bool
    {
        if ($user === null || ! $user->isStaffOperator()) {
            return false;
        }
        $scope = $user->accessibleProjectIds(); // null = platform admin
        if ($scope === null) {
            return true;
        }

        return $post->project_id !== null && in_array((int) $post->project_id, array_map('intval', $scope), true);
    }

    /** Tác giả bài (theo tài khoản, không theo hồ sơ cư dân). */
    public function isAuthor(?User $user, CommunityPost $post): bool
    {
        return $user !== null
            && $post->author_user_id !== null
            && (int) $post->author_user_id === (int) $user->id;
    }

    /**
     * Khối `can{}` trong payload. Bài bị khóa thì tắt luôn comment/react để app
     * không dựng nút chỉ để nhận 423.
     *
     * @return array<string,bool>
     */
    public function abilities(?User $user, CommunityPost $post): array
    {
        $canModerate = $this->canModerate($user, $post);
        $isAuthor = $this->isAuthor($user, $post);
        $open = ! $post->isLocked() && ! $post->isHidden();

        return [
            'comment' => $open,
            'react' => $open,
            'delete' => $isAuthor || $canModerate,
            'moderate' => $canModerate,
            // Không cho tự báo cáo bài của chính mình.
            'report' => $open && ! $isAuthor,
        ];
    }

    /**
     * Tổng hợp cảm xúc của một bài + cảm xúc của chính người xem.
     *
     * @return array{summary:array<string,int>,total:int,mine:?string}
     */
    public function tally(CommunityPost $post, ?User $user): array
    {
        $rows = CommunityPostReaction::query()
            ->where('community_post_id', $post->id)
            ->select('emoji', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('emoji')
            ->pluck('aggregate', 'emoji');

        $summary = [];
        $total = 0;
        foreach ($rows as $emoji => $count) {
            $summary[(string) $emoji] = (int) $count;
            $total += (int) $count;
        }

        $mine = $user === null ? null : CommunityPostReaction::query()
            ->where('community_post_id', $post->id)
            ->where('user_id', $user->id)
            ->value('emoji');

        return ['summary' => $summary, 'total' => $total, 'mine' => $mine];
    }

    /**
     * Tally cho NHIỀU bài trong một lượt — feed 15-50 bài mà query từng bài thì
     * thành N+1.
     *
     * @param  array<int>  $postIds
     * @return array<int,array{summary:array<string,int>,total:int,mine:?string}>
     */
    public function tallyMany(array $postIds, ?User $user): array
    {
        if (empty($postIds)) {
            return [];
        }

        $out = [];
        foreach ($postIds as $id) {
            $out[(int) $id] = ['summary' => [], 'total' => 0, 'mine' => null];
        }

        $grouped = CommunityPostReaction::query()
            ->whereIn('community_post_id', $postIds)
            ->select('community_post_id', 'emoji', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('community_post_id', 'emoji')
            ->get();

        foreach ($grouped as $row) {
            $id = (int) $row->community_post_id;
            $out[$id]['summary'][(string) $row->emoji] = (int) $row->aggregate;
            $out[$id]['total'] += (int) $row->aggregate;
        }

        if ($user !== null) {
            $mine = CommunityPostReaction::query()
                ->whereIn('community_post_id', $postIds)
                ->where('user_id', $user->id)
                ->pluck('emoji', 'community_post_id');
            foreach ($mine as $postId => $emoji) {
                $out[(int) $postId]['mine'] = (string) $emoji;
            }
        }

        return $out;
    }

    /** Đồng bộ `like_count` = TỔNG mọi cảm xúc (giữ cột cũ khỏi vỡ code cũ). */
    public function syncLikeCount(CommunityPost $post, int $total): void
    {
        if ((int) $post->like_count !== $total) {
            $post->forceFill(['like_count' => $total])->saveQuietly();
        }
    }
}
