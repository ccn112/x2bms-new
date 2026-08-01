<?php

declare(strict_types=1);

namespace App\Support\CommunitySeed;

use App\Models\CommunityPost;
use App\Models\CommunityPostReaction;
use Carbon\CarbonImmutable;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Bộ sinh dữ liệu cộng đồng quy mô lớn — deterministic theo seed, batch insert,
 * idempotent + resume checkpoint. Map SANG SCHEMA THẬT của x2bms:
 *
 *  - Bài            → `community_posts` (id bigint, KHÔNG phải uuid).
 *                     Không có cột `post_type/scope_type/visibility/reaction_count`
 *                     như file mẫu handoff; loại bài chỉ phản ánh qua NỘI DUNG
 *                     `body` + `is_pinned/is_important` + `author_kind`.
 *  - Cảm xúc        → `community_post_reactions` (một hàng/1 user/1 bài, unique;
 *                     `like_count` trên bài = số hàng).
 *  - Report/kiểm duyệt → `community_post_reports` + `status/locked/moderation_reason/
 *                     report_count` trên `community_posts`.
 *  - Bình luận      → bảng POLYMORPHIC DÙNG CHUNG `comments` (commentable = CommunityPost),
 *                     đa cấp 1 lớp qua `parent_id`. KHÔNG có cột status/tenant/project.
 *
 * ⚠ QUY MÔ COMMENT: bảng `comments` là bảng dùng chung cho cả thông báo/phản ánh/
 * ticket. Dùng nó để seed comment ở `demo`/`ux` (vài trăm nghìn) thì OK. Nhưng
 * mục tiêu 25 TRIỆU comment của profile `full` PHẢI có bảng chuyên dụng
 * `community_comments` (đánh chỉ mục + partition riêng) — thuộc GIAI ĐOẠN 7 đang
 * thiết kế, CHƯA tồn tại. Không tự tạo bảng đó ở đây để tránh xung đột. Vì vậy
 * `load`/`full` chỉ nên chạy trên staging và khi GĐ7 sẵn sàng thì trỏ phần
 * insertComments() sang bảng mới.
 *
 * ID sau batch insert lấy bằng "max(id) trước → SELECT id > max sau", KHÔNG dùng
 * lastInsertId (khác nghĩa giữa MySQL trả id ĐẦU và SQLite trả id CUỐI). Cách này
 * chạy đúng trên cả hai driver và cho phép chia nhỏ statement.
 */
final class CommunityMassSeeder
{
    private const INSERT_CHUNK = 1000;

    /** @var array<string,array<int,string>> */
    private array $posts;

    /** @var array<string,array<int,string>> */
    private array $comments;

    /** @var array<string,array<int,string>> */
    private array $entities;

    private string $tag;

    private string $morphClass;

    public function __construct()
    {
        $base = (string) config('community_seed.content_path', database_path('seed-data/community'));
        $this->posts = $this->readJson($base.'/posts.vi.json');
        $this->comments = $this->readJson($base.'/comments.vi.json');
        $this->entities = $this->readJson($base.'/entities.vi.json');
        $this->tag = (string) config('community_seed.seed_tag', 'mass');
        $this->morphClass = (new CommunityPost)->getMorphClass();
        mt_srand((int) config('community_seed.seed', 20260726));
    }

    /**
     * Xoá ĐÚNG bài do bộ seed sinh ra (theo `seed_tag`) — không đụng dữ liệu
     * demo/thật. Xoá phụ thuộc trước (comment polymorphic không có FK cascade).
     */
    public function reset(int $tenantId, int $projectId): int
    {
        $deleted = 0;

        DB::table('community_posts')
            ->where('tenant_id', $tenantId)
            ->where('project_id', $projectId)
            ->where('seed_tag', $this->tag)
            ->orderBy('id')
            ->chunkById(5000, function ($rows) use (&$deleted): void {
                $ids = array_map(static fn ($r) => $r->id, $rows->all());

                DB::table('comments')
                    ->where('commentable_type', $this->morphClass)
                    ->whereIn('commentable_id', $ids)
                    ->delete();
                DB::table('community_post_reactions')->whereIn('community_post_id', $ids)->delete();
                DB::table('community_post_reports')->whereIn('community_post_id', $ids)->delete();
                $deleted += DB::table('community_posts')->whereIn('id', $ids)->delete();
            });

        Cache::forget($this->checkpointKey($tenantId, $projectId));

        return $deleted;
    }

    /**
     * @param  array<string,mixed>  $profile
     * @return array{posts:int,comments:int,reactions:int,reports:int}
     */
    public function run(int $tenantId, int $projectId, array $profile, bool $resume, ?OutputStyle $output = null): array
    {
        $target = (int) $profile['posts'];
        $batchSize = max(self::INSERT_CHUNK, (int) $profile['batch_size']);

        $authors = $this->loadAuthors($tenantId);
        if ($authors === []) {
            throw new RuntimeException(
                'Chưa có cư dân gắn tài khoản (residents.user_id) cho tenant '.$tenantId
                .'. Hãy chạy DemoDataSeeder/ResidentDemoContentSeeder trước.'
            );
        }
        $userIds = array_values(array_unique(array_map(static fn ($a) => $a['user_id'], $authors)));
        $poolSize = count($authors);
        $userPool = count($userIds);

        $groupIds = DB::table('community_groups')->where('tenant_id', $tenantId)->pluck('id')->all();

        $start = $resume ? (int) Cache::get($this->checkpointKey($tenantId, $projectId), 0) : 0;

        $bar = $output?->createProgressBar(max(0, $target - $start));
        $bar?->start();

        $totals = ['posts' => 0, 'comments' => 0, 'reactions' => 0, 'reports' => 0];

        for ($offset = $start; $offset < $target; $offset += $batchSize) {
            $count = min($batchSize, $target - $offset);
            $now = CarbonImmutable::now();

            $postRows = [];
            $plans = [];

            for ($i = 0; $i < $count; $i++) {
                $ordinal = $offset + $i + 1;
                $createdAt = $this->randomTimestamp($now);
                $theme = $this->weighted([
                    'questions' => 18, 'management' => 14, 'events' => 12, 'maintenance' => 10,
                    'sharing' => 10, 'lost_found' => 8, 'pets_green' => 8, 'market' => 8,
                ]);
                $state = $this->statusPlan($createdAt);

                $reactionN = min($poolSize, $this->reactionTarget($profile));
                $commentN = $this->commentCount($profile);
                $reportN = (mt_rand(1, 1000) <= 15) ? min($userPool, mt_rand(1, 5)) : 0;

                $author = $authors[$ordinal % $poolSize];
                $isManagement = $theme === 'management';

                $postRows[] = [
                    'tenant_id' => $tenantId,
                    'project_id' => $projectId,
                    'community_group_id' => $this->pickGroup($groupIds, $ordinal),
                    'content_type' => 'status',
                    'author_resident_id' => $author['resident_id'],
                    'author_user_id' => $author['user_id'],
                    'author_kind' => $isManagement ? 'management' : 'resident',
                    'title' => null,
                    'body' => $this->postContent($theme),
                    'like_count' => $reactionN,
                    'comment_count' => $commentN,
                    'status' => $state['status'],
                    'published_at' => $state['published_at'],
                    'locked_at' => null,
                    'moderated_at' => $state['moderated_at'],
                    'moderation_reason' => $state['moderation_reason'],
                    'report_count' => $reportN,
                    'seed_tag' => $this->tag,
                    'is_pinned' => $isManagement && mt_rand(1, 100) <= 12,
                    'is_important' => $isManagement && mt_rand(1, 100) <= 25,
                    'image_paths' => null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                    'deleted_at' => $state['deleted_at'],
                ];

                $plans[] = [
                    'reactions' => $reactionN,
                    'comments' => $commentN,
                    'reports' => $reportN,
                    'time' => $createdAt,
                ];
            }

            $postIds = $this->insertReturningIds('community_posts', $postRows, $tenantId, $projectId, $count);

            $totals['posts'] += count($postIds);
            $totals['reactions'] += $this->insertReactions($postIds, $plans, $userIds);
            $totals['reports'] += $this->insertReports($postIds, $plans, $userIds);
            $totals['comments'] += $this->insertComments($postIds, $plans, $authors);

            Cache::forever($this->checkpointKey($tenantId, $projectId), $offset + $count);
            $bar?->advance($count);
            unset($postRows, $plans, $postIds);
        }

        $bar?->finish();
        $output?->newLine(2);

        return $totals;
    }

    // ── Reactions ────────────────────────────────────────────────────────────

    /**
     * @param  array<int,int>  $postIds
     * @param  array<int,array<string,mixed>>  $plans
     * @param  array<int,int>  $userIds
     */
    private function insertReactions(array $postIds, array $plans, array $userIds): int
    {
        $pool = count($userIds);
        $rows = [];
        $written = 0;

        foreach ($postIds as $k => $postId) {
            $n = (int) $plans[$k]['reactions'];
            if ($n <= 0) {
                continue;
            }
            $time = $plans[$k]['time'];
            $startIdx = ($postId * 7) % $pool; // rải điểm bắt đầu để bài khác nhau khác nhau

            for ($j = 0; $j < $n; $j++) {
                $rows[] = [
                    'community_post_id' => $postId,
                    'user_id' => $userIds[($startIdx + $j) % $pool], // n<=pool ⇒ user phân biệt
                    'emoji' => CommunityPostReaction::CODES[($postId + $j) % count(CommunityPostReaction::CODES)],
                    'created_at' => $time,
                    'updated_at' => $time,
                ];
                if (count($rows) >= self::INSERT_CHUNK) {
                    DB::table('community_post_reactions')->insert($rows);
                    $written += count($rows);
                    $rows = [];
                }
            }
        }
        if ($rows !== []) {
            DB::table('community_post_reactions')->insert($rows);
            $written += count($rows);
        }

        return $written;
    }

    // ── Reports ──────────────────────────────────────────────────────────────

    /**
     * @param  array<int,int>  $postIds
     * @param  array<int,array<string,mixed>>  $plans
     * @param  array<int,int>  $userIds
     */
    private function insertReports(array $postIds, array $plans, array $userIds): int
    {
        $pool = count($userIds);
        $rows = [];
        $written = 0;

        foreach ($postIds as $k => $postId) {
            $n = (int) $plans[$k]['reports'];
            if ($n <= 0) {
                continue;
            }
            $time = $plans[$k]['time'];
            $startIdx = ($postId * 3 + 1) % $pool;

            for ($j = 0; $j < $n; $j++) {
                $rows[] = [
                    'community_post_id' => $postId,
                    'reported_by_user_id' => $userIds[($startIdx + $j) % $pool],
                    'reason' => $this->weighted(['spam' => 45, 'offensive' => 25, 'false_info' => 20, 'other' => 10]),
                    'note' => null,
                    'status' => 'open',
                    'created_at' => $time,
                    'updated_at' => $time,
                ];
            }
            if (count($rows) >= self::INSERT_CHUNK) {
                DB::table('community_post_reports')->insert($rows);
                $written += count($rows);
                $rows = [];
            }
        }
        if ($rows !== []) {
            DB::table('community_post_reports')->insert($rows);
            $written += count($rows);
        }

        return $written;
    }

    // ── Comments (polymorphic, 2 cấp) ────────────────────────────────────────

    /**
     * @param  array<int,int>  $postIds
     * @param  array<int,array<string,mixed>>  $plans
     * @param  array<int,array<string,mixed>>  $authors
     */
    private function insertComments(array $postIds, array $plans, array $authors): int
    {
        $poolSize = count($authors);

        // Pass 1 — bình luận gốc (parent_id = null).
        $rootRows = [];
        $rootPostOf = [];   // vị trí root k → postId
        $planPerPost = [];  // postId → ['roots'=>, 'replies'=>, 'time'=>]

        foreach ($postIds as $k => $postId) {
            $total = (int) $plans[$k]['comments'];
            if ($total <= 0) {
                $planPerPost[$postId] = ['roots' => 0, 'replies' => 0, 'time' => $plans[$k]['time']];
                continue;
            }
            $replies = $total > 3 ? intdiv($total * 28, 100) : 0;
            $roots = $total - $replies;
            $planPerPost[$postId] = ['roots' => $roots, 'replies' => $replies, 'time' => $plans[$k]['time']];

            for ($r = 0; $r < $roots; $r++) {
                $author = $authors[($postId + $r) % $poolSize];
                $rootRows[] = $this->commentRow($postId, null, $author, $plans[$k]['time'], false, $r);
                $rootPostOf[] = $postId;
            }
        }

        $maxBefore = (int) (DB::table('comments')->max('id') ?? 0);
        $this->chunkInsert('comments', $rootRows);
        $rootIds = DB::table('comments')->where('id', '>', $maxBefore)->orderBy('id')->pluck('id')->all();

        // Gom id root theo post (không giả định liền mạch qua ranh giới chunk).
        $rootIdsByPost = [];
        foreach ($rootIds as $idx => $cid) {
            $rootIdsByPost[$rootPostOf[$idx]][] = $cid;
        }

        // Pass 2 — trả lời (parent_id trỏ tới 1 root CÙNG bài).
        $replyRows = [];
        foreach ($planPerPost as $postId => $meta) {
            $replies = (int) $meta['replies'];
            $roots = $rootIdsByPost[$postId] ?? [];
            if ($replies <= 0 || $roots === []) {
                continue;
            }
            $rc = count($roots);
            for ($r = 0; $r < $replies; $r++) {
                $author = $authors[($postId * 2 + $r) % $poolSize];
                $parentId = $roots[($postId + $r) % $rc];
                $replyRows[] = $this->commentRow($postId, $parentId, $author, $meta['time'], true, $r);
            }
        }
        $this->chunkInsert('comments', $replyRows);

        return count($rootRows) + count($replyRows);
    }

    /**
     * @param  array<string,mixed>  $author
     * @return array<string,mixed>
     */
    private function commentRow(int $postId, ?int $parentId, array $author, CarbonImmutable $postTime, bool $isReply, int $seq): array
    {
        $createdAt = $postTime->addMinutes(($isReply ? 60 : 10) + ($seq * 37) + mt_rand(1, 720));

        return [
            'commentable_type' => $this->morphClass,
            'commentable_id' => $postId,
            'parent_id' => $parentId,
            'user_id' => $author['user_id'],
            'author_name' => $author['name'],
            'author_subtitle' => $author['subtitle'],
            'is_staff' => false,
            'body' => $this->commentContent($isReply),
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }

    // ── Nội dung ─────────────────────────────────────────────────────────────

    private function postContent(string $theme): string
    {
        $bank = $this->posts[$theme] ?? $this->posts['sharing'];
        $template = $bank[mt_rand(0, count($bank) - 1)];

        return strtr($template, [
            '{facility}' => $this->pick('facilities'), '{service}' => $this->pick('services'),
            '{location}' => $this->pick('locations'), '{procedure}' => $this->pick('procedures'),
            '{item}' => $this->pick('items'), '{issue}' => $this->pick('issues'),
            '{event}' => $this->pick('events'), '{group}' => $this->pick('groups'),
            '{pet_name}' => $this->pick('pet_names'), '{plant}' => $this->pick('plants'),
            '{project}' => $this->pick('projects'), '{building}' => $this->pick('buildings'),
            '{time}' => sprintf('%02d:%02d', mt_rand(6, 21), [0, 15, 30, 45][mt_rand(0, 3)]),
            '{date}' => CarbonImmutable::now()->addDays(mt_rand(1, 45))->format('d/m/Y'),
        ]);
    }

    private function commentContent(bool $reply): string
    {
        $bucket = $reply
            ? $this->weighted(['answer' => 60, 'positive' => 20, 'neutral' => 20])
            : $this->weighted(['positive' => 40, 'question' => 25, 'neutral' => 25, 'answer' => 10]);
        $bank = $this->comments[$bucket];

        return $bank[mt_rand(0, count($bank) - 1)];
    }

    // ── Phân phối ────────────────────────────────────────────────────────────

    /** @param array<string,mixed> $profile */
    private function commentCount(array $profile): int
    {
        $min = (int) $profile['comments_min'];
        $max = (int) $profile['comments_max'];

        if (($profile['comments_distribution'] ?? 'uniform') === 'zipf') {
            $r = mt_rand(1, 1000);
            if ($r <= 550) {
                return mt_rand(0, min(2, $max));
            }
            if ($r <= 850) {
                return mt_rand(3, min(8, $max));
            }
            if ($r <= 970) {
                return mt_rand(9, min(20, $max));
            }

            return mt_rand(max(21, $min), $max);
        }

        return mt_rand($min, $max);
    }

    /** @param array<string,mixed> $profile — 70/25/5 low/medium/viral, gated bởi reactions_ratio. */
    private function reactionTarget(array $profile): int
    {
        $ratio = (int) round(((float) ($profile['reactions_ratio'] ?? 0.6)) * 100);
        if (mt_rand(1, 100) > $ratio) {
            return 0;
        }
        $viral = (int) round(((float) ($profile['viral_ratio'] ?? 0.03)) * 1000);
        $r = mt_rand(1, 1000);
        if ($r <= $viral) {
            return mt_rand(16, 40);
        }
        if ($r <= 300) {
            return mt_rand(4, 15);
        }

        return mt_rand(1, 3);
    }

    /** @return array{status:string,published_at:?CarbonImmutable,moderated_at:?CarbonImmutable,moderation_reason:?string,deleted_at:?CarbonImmutable} */
    private function statusPlan(CarbonImmutable $createdAt): array
    {
        $bucket = $this->weighted([
            'published' => 945, 'pending' => 20, 'hidden' => 15, 'rejected' => 10, 'deleted' => 10,
        ]);

        return match ($bucket) {
            'pending' => ['status' => 'pending', 'published_at' => null, 'moderated_at' => null, 'moderation_reason' => null, 'deleted_at' => null],
            'hidden' => ['status' => 'hidden', 'published_at' => $createdAt, 'moderated_at' => $createdAt->addHours(mt_rand(1, 72)), 'moderation_reason' => 'Ẩn để rà soát nội dung', 'deleted_at' => null],
            // `rejected` không thuộc enum thật (published|hidden|pending) → biểu diễn = hidden + lý do.
            'rejected' => ['status' => 'hidden', 'published_at' => null, 'moderated_at' => $createdAt->addHours(mt_rand(1, 48)), 'moderation_reason' => 'Bài bị từ chối kiểm duyệt', 'deleted_at' => null],
            // `deleted` = xoá mềm (SoftDeletes) — tác giả tự gỡ, vẫn từng published.
            'deleted' => ['status' => 'published', 'published_at' => $createdAt, 'moderated_at' => null, 'moderation_reason' => null, 'deleted_at' => $createdAt->addDays(mt_rand(1, 60))],
            default => ['status' => 'published', 'published_at' => $createdAt, 'moderated_at' => null, 'moderation_reason' => null, 'deleted_at' => null],
        };
    }

    /** 24 tháng, peak theo giờ (sáng/trưa/tối) — deterministic. */
    private function randomTimestamp(CarbonImmutable $now): CarbonImmutable
    {
        $day = mt_rand(0, 729);
        $peaks = [7, 8, 12, 13, 19, 20, 21]; // giờ cao điểm cư dân online
        $hour = mt_rand(1, 100) <= 65 ? $peaks[mt_rand(0, count($peaks) - 1)] : mt_rand(0, 23);

        return $now->startOfDay()->subDays($day)->addHours($hour)->addMinutes(mt_rand(0, 59));
    }

    // ── Hạ tầng ──────────────────────────────────────────────────────────────

    /**
     * Insert (chia nhỏ statement) rồi lấy lại id THEO THỨ TỰ CHÈN, không phụ thuộc
     * lastInsertId (MySQL/SQLite khác nghĩa).
     *
     * @param  array<int,array<string,mixed>>  $rows
     * @return array<int,int>
     */
    private function insertReturningIds(string $table, array $rows, int $tenantId, int $projectId, int $expected): array
    {
        $maxBefore = (int) (DB::table($table)->max('id') ?? 0);
        $this->chunkInsert($table, $rows);

        return DB::table($table)
            ->where('id', '>', $maxBefore)
            ->where('tenant_id', $tenantId)
            ->where('project_id', $projectId)
            ->where('seed_tag', $this->tag)
            ->orderBy('id')
            ->pluck('id')
            ->all();
    }

    /** @param array<int,array<string,mixed>> $rows */
    private function chunkInsert(string $table, array $rows): void
    {
        if ($rows === []) {
            return;
        }
        foreach (array_chunk($rows, self::INSERT_CHUNK) as $chunk) {
            DB::table($table)->insert($chunk);
        }
    }

    /**
     * Cư dân có tài khoản (residents.user_id) — tác giả bài + người thả cảm xúc.
     *
     * @return array<int,array{resident_id:int,user_id:int,name:string,subtitle:?string}>
     */
    private function loadAuthors(int $tenantId): array
    {
        return DB::table('residents')
            ->where('tenant_id', $tenantId)
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->limit(50_000)
            ->get(['id', 'user_id', 'full_name', 'code'])
            ->map(fn ($r) => [
                'resident_id' => (int) $r->id,
                'user_id' => (int) $r->user_id,
                'name' => (string) ($r->full_name ?? 'Cư dân'),
                'subtitle' => $r->code !== null ? 'Mã CD '.$r->code : null,
            ])
            ->all();
    }

    /** @param array<int,int> $groupIds */
    private function pickGroup(array $groupIds, int $ordinal): ?int
    {
        if ($groupIds === [] || mt_rand(1, 100) > 20) {
            return null; // 80% bài phạm vi toàn dự án
        }

        return $groupIds[$ordinal % count($groupIds)];
    }

    private function pick(string $key): string
    {
        $a = $this->entities[$key];

        return $a[mt_rand(0, count($a) - 1)];
    }

    /** @param array<string,int> $weights */
    private function weighted(array $weights): string
    {
        $r = mt_rand(1, array_sum($weights));
        foreach ($weights as $k => $w) {
            $r -= $w;
            if ($r <= 0) {
                return $k;
            }
        }

        return array_key_first($weights);
    }

    private function checkpointKey(int $tenantId, int $projectId): string
    {
        return "community-seed:$tenantId:$projectId";
    }

    /** @return array<string,array<int,string>> */
    private function readJson(string $path): array
    {
        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }
}
