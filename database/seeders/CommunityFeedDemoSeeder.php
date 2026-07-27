<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\CommunityPost;
use App\Models\CommunityPostReaction;
use App\Models\Resident;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Làm DÀY tab Cộng đồng để test feed thật.
 *
 * `ResidentDemoContentSeeder` đã có 10 bài nhưng **cùng một tác giả** nên bảng
 * tin vừa thưa vừa đơn điệu (mọi thẻ cùng tên, cùng avatar). Seeder này bổ sung
 * bài của NHIỀU cư dân khác nhau, kèm **cảm xúc thật** (nhiều loại emoji để
 * cụm mặt cười trên thanh tương tác có gì mà hiển thị) và một ít bình luận.
 *
 * Idempotent qua `title = FEED-<key>`; chạy lại không nhân bản.
 *
 *   php artisan db:seed --class=CommunityFeedDemoSeeder
 */
class CommunityFeedDemoSeeder extends Seeder
{
    private const PROJECT_ID = 1;
    private const TENANT_ID = 1;

    /** Trộn cảm xúc theo "khẩu vị" bài để summary không bài nào giống bài nào. */
    private const MOODS = [
        'warm' => ['love', 'love', 'like', 'haha'],
        'info' => ['like', 'like', 'wow'],
        'fun' => ['haha', 'haha', 'love', 'wow'],
        'sad' => ['sad', 'love', 'like'],
        'angry' => ['angry', 'angry', 'sad', 'like'],
    ];

    public function run(): void
    {
        /** @var array<int,Resident> $authors */
        $authors = Resident::withoutGlobalScopes()
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->get()
            ->all();

        if (empty($authors)) {
            $this->command?->warn('  Cộng đồng: chưa có cư dân nào gắn tài khoản — bỏ qua.');

            return;
        }

        $posts = [
            ['key' => 'newneighbor', 'mood' => 'warm', 'body' => 'Nhà em vừa dọn về căn 1802 tuần trước, chào cả nhà ạ! Mong được làm quen với các anh chị hàng xóm.'],
            ['key' => 'badminton', 'mood' => 'fun', 'body' => 'Nhóm cầu lông tối thứ 3-5 ở sân tầng 2 đang thiếu người, ai chơi được thì vào nhóm với mình nhé.'],
            ['key' => 'lostkey', 'mood' => 'info', 'body' => 'Em nhặt được chùm chìa khoá có móc hình con cá ở thang máy block A sáng nay, đã gửi lễ tân ạ.'],
            ['key' => 'noise', 'mood' => 'angry', 'body' => 'Căn tầng trên nhà mình khoan tường lúc 22h mấy hôm liền, mong BQL nhắc giúp giờ giấc thi công ạ.'],
            ['key' => 'petcare', 'mood' => 'warm', 'body' => 'Cuối tuần nhà em đi vắng 2 hôm, có ai nhận trông giúp bé mèo không ạ? Em gửi phí đầy đủ.'],
            ['key' => 'marketday', 'mood' => 'fun', 'body' => 'Phiên chợ quê cư dân sáng Chủ nhật đông vui quá, rau nhà trồng ngon mà rẻ. Tháng sau tổ chức tiếp nha BQL ơi!'],
            ['key' => 'liftstuck', 'mood' => 'sad', 'body' => 'Thang máy block B lại kẹt sáng nay 15 phút, may có bác bảo vệ hỗ trợ kịp. Mong sớm bảo trì dứt điểm ạ.'],
            ['key' => 'kidsclass', 'mood' => 'warm', 'body' => 'Có cô giáo nào trong toà nhận dạy kèm lớp 3 không ạ? Nhà em cần tìm cho bé buổi tối trong tuần.'],
            ['key' => 'ev', 'mood' => 'info', 'body' => 'Trạm sạc xe điện tầng hầm B1 đã hoạt động lại, hiện có 4 cổng trống. Mọi người sạc xong nhớ dời xe nhé.'],
            ['key' => 'recycle', 'mood' => 'warm', 'body' => 'Góc đổi rác tái chế lấy cây xanh ở sảnh vẫn còn cây, nhà nào có giấy báo/chai nhựa mang xuống đổi nha.'],
            ['key' => 'wifi', 'mood' => 'info', 'body' => 'Nhà nào dùng mạng ở block A tối qua có bị chập chờn không ạ? Em tưởng mỗi nhà em.'],
            ['key' => 'thanksdoc', 'mood' => 'warm', 'body' => 'Cảm ơn bác sĩ ở căn 0905 tối qua đã sang sơ cứu giúp bé nhà em. Ở chung cư mà ấm áp như họ hàng.'],
            ['key' => 'football', 'mood' => 'fun', 'body' => 'Tối nay xem bóng ở sảnh cộng đồng nhé anh em, màn chiếu đã set xong rồi!'],
            ['key' => 'flood', 'mood' => 'sad', 'body' => 'Hầm xe khu B sáng nay đọng nước sau mưa, ai để xe thấp nhớ kiểm tra giúp ạ.'],
        ];

        $created = 0;
        $reactionRows = 0;

        foreach ($posts as $i => $po) {
            $author = $authors[$i % count($authors)];

            $post = CommunityPost::withoutGlobalScopes()->updateOrCreate(
                ['project_id' => self::PROJECT_ID, 'title' => 'FEED-'.$po['key']],
                [
                    'tenant_id' => self::TENANT_ID,
                    'author_resident_id' => $author->id,
                    // Để NULL: đây là bài seeder, `CommunityPostResource` dựa vào
                    // đó để mượn ảnh demo (bài người thật đăng chay thì không).
                    'author_user_id' => null,
                    'author_kind' => 'resident',
                    'body' => $po['body'],
                    'comment_count' => 0,
                    'is_pinned' => false,
                    'is_important' => false,
                    'image_paths' => [],
                    'status' => 'published',
                    'created_at' => Carbon::parse('2026-07-27')->subHours(3 * $i + 2),
                ]
            );
            $created++;

            $reactionRows += $this->seedReactions($post, $po['mood'], $authors);
            $this->seedComments($post, $authors, $i);
        }

        $this->command?->info(
            "  Cộng đồng: {$created} bài (nhiều tác giả) + {$reactionRows} cảm xúc + bình luận mẫu."
        );
    }

    /**
     * Thả cảm xúc từ các cư dân khác nhau. Ràng buộc unique(post,user) nên dùng
     * updateOrCreate — chạy lại seeder không phình số.
     *
     * @param  array<int,Resident>  $authors
     */
    private function seedReactions(CommunityPost $post, string $mood, array $authors): int
    {
        $palette = self::MOODS[$mood] ?? self::MOODS['info'];
        $n = 0;

        foreach ($authors as $k => $r) {
            if ($r->user_id === null) {
                continue;
            }
            // Bỏ qua một phần để không phải bài nào cũng full cảm xúc.
            if (($post->id + $k) % 4 === 0) {
                continue;
            }
            CommunityPostReaction::updateOrCreate(
                ['community_post_id' => $post->id, 'user_id' => $r->user_id],
                ['emoji' => $palette[($post->id + $k) % count($palette)]],
            );
            $n++;
        }

        // `like_count` = tổng cảm xúc, khớp cách API trả `likes`.
        $total = CommunityPostReaction::where('community_post_id', $post->id)->count();
        $post->forceFill(['like_count' => $total])->saveQuietly();

        return $n;
    }

    /** @param array<int,Resident> $authors */
    private function seedComments(CommunityPost $post, array $authors, int $index): void
    {
        // Chỉ ~1/3 số bài có bình luận, để feed trông tự nhiên.
        if ($index % 3 !== 0) {
            return;
        }

        $lines = [
            'Cảm ơn bạn đã chia sẻ nhé!',
            'Nhà mình cũng gặp tình trạng tương tự ạ.',
            'Đã nắm thông tin, cảm ơn cả nhà.',
        ];

        foreach (array_slice($lines, 0, 1 + ($index % 3)) as $j => $line) {
            $author = $authors[($index + $j + 1) % count($authors)];
            Comment::updateOrCreate(
                [
                    'commentable_type' => $post->getMorphClass(),
                    'commentable_id' => $post->id,
                    'user_id' => $author->user_id,
                    'body' => $line,
                ],
                [
                    'author_name' => $author->full_name,
                    'author_subtitle' => $author->apartmentRelations()->first()?->apartment?->code,
                    'is_staff' => false,
                ]
            );
        }

        $post->forceFill([
            'comment_count' => Comment::where('commentable_type', $post->getMorphClass())
                ->where('commentable_id', $post->id)->count(),
        ])->saveQuietly();
    }
}
