<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hỗ trợ bộ sinh dữ liệu cộng đồng quy mô (`community:seed-scale`).
 *
 * 1. Cột `seed_tag` (nullable) trên `community_posts` — đánh dấu bài do bộ mass
 *    seed sinh ra để `--reset` xoá ĐÚNG chúng, KHÔNG chạm dữ liệu demo/thật.
 * 2. Bổ SUNG các index feed/moderation/comment CÒN THIẾU, đối chiếu index đã có:
 *    - đã có: cp_type_published_idx(content_type,published_at),
 *             cp_group_state_idx(community_group_id,status,published_at),
 *             comments(commentable_type,commentable_id,id).
 *    - feed thật (CommunityController@posts) sắp theo is_pinned DESC, created_at
 *      DESC, id DESC lọc project_id + status → cần keyset index tương ứng.
 *
 * ADD-ONLY + guard hasColumn/hasIndex để chạy lại nhiều lần không vỡ, có down().
 */
return new class extends Migration
{
    /** table => [indexName => columns] các index BỔ SUNG. */
    private array $indexes = [
        'community_posts' => [
            // Feed dự án: keyset (is_pinned, created_at, id) sau lọc project_id+status.
            'cp_feed_cursor_idx' => ['project_id', 'status', 'is_pinned', 'created_at', 'id'],
            // Feed theo nhóm sở thích (cùng thứ tự sắp xếp, khác cột lọc đầu).
            'cp_group_cursor_idx' => ['community_group_id', 'status', 'is_pinned', 'created_at', 'id'],
            // Hàng đợi kiểm duyệt: bài bị report nhiều lên trước.
            'cp_report_scan_idx' => ['project_id', 'status', 'report_count'],
            // Reset nhanh theo nhãn seed.
            'cp_seed_tag_idx' => ['tenant_id', 'project_id', 'seed_tag'],
        ],
        'comments' => [
            // Phân trang REPLY (lọc parent_id) — index sẵn có không có parent_id.
            'comments_reply_page_idx' => ['commentable_type', 'commentable_id', 'parent_id', 'id'],
        ],
    ];

    public function up(): void
    {
        if (! Schema::hasColumn('community_posts', 'seed_tag')) {
            Schema::table('community_posts', function (Blueprint $table) {
                $table->string('seed_tag')->nullable()->after('report_count');
            });
        }

        foreach ($this->indexes as $table => $defs) {
            Schema::table($table, function (Blueprint $blueprint) use ($table, $defs) {
                foreach ($defs as $name => $columns) {
                    if (! $this->hasIndex($table, $name)) {
                        $blueprint->index($columns, $name);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $table => $defs) {
            Schema::table($table, function (Blueprint $blueprint) use ($table, $defs) {
                foreach (array_keys($defs) as $name) {
                    if ($this->hasIndex($table, $name)) {
                        $blueprint->dropIndex($name);
                    }
                }
            });
        }

        if (Schema::hasColumn('community_posts', 'seed_tag')) {
            Schema::table('community_posts', function (Blueprint $table) {
                $table->dropColumn('seed_tag');
            });
        }
    }

    private function hasIndex(string $table, string $index): bool
    {
        foreach (Schema::getIndexes($table) as $existing) {
            if (($existing['name'] ?? null) === $index) {
                return true;
            }
        }

        return false;
    }
};
