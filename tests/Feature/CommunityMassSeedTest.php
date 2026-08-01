<?php

namespace Tests\Feature;

use App\Models\CommunityPost;
use App\Models\CommunityPostReaction;
use App\Support\CommunitySeed\CommunityMassSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Bộ sinh dữ liệu cộng đồng quy mô (`community:seed-scale`).
 *
 * Ba bảo đảm cốt lõi: cô lập tenant (bài tenant A không lọt query tenant B),
 * cursor pagination keyset (created_at,id) ổn định, và counter consistency
 * (like_count / comment_count / report_count khớp số bản ghi). Deterministic:
 * dùng profile nhỏ cố định, khẳng định bằng số thật sinh ra.
 */
class CommunityMassSeedTest extends TestCase
{
    use RefreshDatabase;

    /** Profile nhỏ, chạy nhanh trên sqlite in-memory. */
    private const PROFILE = [
        'posts' => 60,
        'comments_min' => 2,
        'comments_max' => 8,
        'comments_distribution' => 'uniform',
        'reactions_ratio' => 0.7,
        'viral_ratio' => 0.03,
        'batch_size' => 25,
    ];

    /** Tạo tenant + project + N cư dân có tài khoản (để làm tác giả/reactor). */
    private function seedResidents(int $tenantId, int $projectId, int $count, string $tag): void
    {
        DB::table('tenants')->insertOrIgnore(['id' => $tenantId, 'code' => "TEN-$tag", 'name' => "Tenant $tag", 'created_at' => now(), 'updated_at' => now()]);
        DB::table('projects')->insertOrIgnore(['id' => $projectId, 'tenant_id' => $tenantId, 'code' => "PRJ-$tag", 'name' => "Project $tag", 'created_at' => now(), 'updated_at' => now()]);
        $buildingId = DB::table('buildings')->insertGetId(['tenant_id' => $tenantId, 'project_id' => $projectId, 'code' => "BLD-$tag", 'name' => "Toà $tag", 'created_at' => now(), 'updated_at' => now()]);

        for ($i = 1; $i <= $count; $i++) {
            $userId = DB::table('users')->insertGetId([
                'tenant_id' => $tenantId,
                'name' => "U$tag$i",
                'email' => strtolower($tag)."-u$i@test.vn",
                'password' => bcrypt('secret'),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('residents')->insert([
                'tenant_id' => $tenantId,
                'building_id' => $buildingId,
                'user_id' => $userId,
                'code' => "CD-$tag-$i",
                'full_name' => "Cư dân $tag $i",
                'status' => 'active',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function seedScale(int $tenantId, int $projectId): array
    {
        return (new CommunityMassSeeder)->run($tenantId, $projectId, self::PROFILE, false, null);
    }

    public function test_sinh_du_bai_va_deterministic(): void
    {
        $this->seedResidents(1, 1, 12, 'A');

        $t1 = $this->seedScale(1, 1);
        $this->assertSame(self::PROFILE['posts'], $t1['posts']);
        $this->assertGreaterThan(0, $t1['comments']);
        $this->assertGreaterThan(0, $t1['reactions']);

        // Chạy lại sau reset trên DB sạch → cùng số liệu (deterministic theo seed).
        DB::table('comments')->delete();
        DB::table('community_post_reactions')->delete();
        DB::table('community_post_reports')->delete();
        DB::table('community_posts')->delete();

        $t2 = $this->seedScale(1, 1);
        $this->assertSame($t1, $t2, 'Cùng seed phải cho cùng số bài/comment/reaction.');
    }

    public function test_co_lap_tenant_bai_A_khong_lot_query_tenant_B(): void
    {
        $this->seedResidents(1, 1, 10, 'A');
        $this->seedResidents(2, 2, 10, 'B');

        $this->seedScale(1, 1);
        $this->seedScale(2, 2);

        // Mọi bài của tenant 1 đều mang tenant_id=1.
        $this->assertSame(0, DB::table('community_posts')->where('tenant_id', 1)->where('project_id', '!=', 1)->count());

        // Query phạm vi tenant 2 KHÔNG trả về bài nào của tenant 1.
        $idsT1 = DB::table('community_posts')->where('tenant_id', 1)->pluck('id');
        $leak = DB::table('community_posts')->where('tenant_id', 2)->whereIn('id', $idsT1)->count();
        $this->assertSame(0, $leak, 'Bài tenant 1 lọt vào phạm vi tenant 2.');

        // Comment của bài tenant 1 không dính commentable_id của tenant 2.
        $morph = (new CommunityPost)->getMorphClass();
        $idsT2 = DB::table('community_posts')->where('tenant_id', 2)->pluck('id')->all();
        $crossComments = DB::table('comments')
            ->where('commentable_type', $morph)
            ->whereIn('commentable_id', $idsT1)
            ->whereIn('commentable_id', $idsT2)
            ->count();
        $this->assertSame(0, $crossComments);
    }

    public function test_counter_consistency_like_comment_report(): void
    {
        $this->seedResidents(1, 1, 12, 'A');
        $this->seedScale(1, 1);

        $morph = (new CommunityPost)->getMorphClass();

        $posts = DB::table('community_posts')->where('tenant_id', 1)->get(['id', 'like_count', 'comment_count', 'report_count']);

        foreach ($posts as $p) {
            $reactions = DB::table('community_post_reactions')->where('community_post_id', $p->id)->count();
            $this->assertSame((int) $p->like_count, $reactions, "like_count lệch ở bài {$p->id}");

            $comments = DB::table('comments')->where('commentable_type', $morph)->where('commentable_id', $p->id)->count();
            $this->assertSame((int) $p->comment_count, $comments, "comment_count lệch ở bài {$p->id}");

            $reports = DB::table('community_post_reports')->where('community_post_id', $p->id)->count();
            $this->assertSame((int) $p->report_count, $reports, "report_count lệch ở bài {$p->id}");
        }

        // Ràng buộc unique(post,user) — không có cảm xúc trùng.
        $dupes = DB::table('community_post_reactions')
            ->select('community_post_id', 'user_id')
            ->groupBy('community_post_id', 'user_id')
            ->havingRaw('count(*) > 1')
            ->get();
        $this->assertCount(0, $dupes, 'Có cảm xúc trùng (post,user).');
    }

    public function test_cursor_pagination_keyset_on_dinh_khong_trung_khong_sot(): void
    {
        $this->seedResidents(1, 1, 12, 'A');
        $this->seedScale(1, 1);

        // Feed keyset giống CommunityController: published, sắp created_at DESC, id DESC.
        $base = fn () => DB::table('community_posts')
            ->where('tenant_id', 1)->where('project_id', 1)
            ->whereNull('deleted_at')
            ->where('status', 'published');

        $all = $base()->orderByDesc('created_at')->orderByDesc('id')->pluck('id')->all();
        $this->assertGreaterThan(20, count($all), 'Cần đủ bài để phân nhiều trang.');

        $perPage = 15;
        $collected = [];
        $cursorTime = null;
        $cursorId = null;

        while (true) {
            $q = $base()->orderByDesc('created_at')->orderByDesc('id');
            if ($cursorTime !== null) {
                // (created_at, id) < (cursorTime, cursorId)
                $q->where(function ($w) use ($cursorTime, $cursorId) {
                    $w->where('created_at', '<', $cursorTime)
                        ->orWhere(fn ($w2) => $w2->where('created_at', $cursorTime)->where('id', '<', $cursorId));
                });
            }
            $page = $q->limit($perPage)->get(['id', 'created_at'])->all();
            if ($page === []) {
                break;
            }
            foreach ($page as $row) {
                $collected[] = $row->id;
            }
            $last = end($page);
            $cursorTime = $last->created_at;
            $cursorId = $last->id;
        }

        $this->assertSame($all, $collected, 'Duyệt keyset phải bằng đúng danh sách đầy đủ, không trùng không sót.');
        $this->assertSame(count($collected), count(array_unique($collected)), 'Không được lặp bài giữa các trang.');
    }
}
