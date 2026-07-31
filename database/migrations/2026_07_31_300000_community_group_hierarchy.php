<?php

use App\Enums\CommunityGroupType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Giai đoạn 2 (nhóm & xác minh) của `COMMUNITY_IMPLEMENTATION_PLAN.md` —
 * `community_groups` mở rộng theo `COMMUNITY_DB_MAPPING.md` §2.
 *
 * `kind`/`post_policy`/`status` GIỮ NGUYÊN (app đang đọc `kind` để sắp bậc
 * thang) — cột mới cộng thêm cạnh, additive.
 *
 * Backfill ngay trong migration: dữ liệu hiện có chỉ 16 nhóm, và giá trị suy
 * trực tiếp từ `kind`/`project_id` sẵn có — không có gì để chạy sai giữa
 * chừng cần lệnh artisan riêng (khác với follow/comments ở các giai đoạn sau,
 * nơi backfill phức tạp hơn phải tách khỏi `up()` theo đúng quy tắc migration
 * của `COMMUNITY_RISK_ROLLBACK.md` §5).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_groups', function (Blueprint $table) {
            if (! Schema::hasColumn('community_groups', 'slug')) {
                $table->string('slug')->nullable()->after('name');
            }
            if (! Schema::hasColumn('community_groups', 'group_type')) {
                $table->string('group_type')->nullable()->after('kind');
            }
            if (! Schema::hasColumn('community_groups', 'lifecycle_state')) {
                $table->string('lifecycle_state')->default('active')->after('status');
            }
            if (! Schema::hasColumn('community_groups', 'scope_type')) {
                // platform | tenant | project | building | floor
                $table->string('scope_type')->nullable()->after('lifecycle_state');
            }
            if (! Schema::hasColumn('community_groups', 'scope_id')) {
                $table->unsignedBigInteger('scope_id')->nullable()->after('scope_type');
            }
            if (! Schema::hasColumn('community_groups', 'parent_group_id')) {
                $table->foreignId('parent_group_id')->nullable()->after('scope_id')
                    ->constrained('community_groups')->nullOnDelete();
            }
            if (! Schema::hasColumn('community_groups', 'created_by_user_id')) {
                $table->foreignId('created_by_user_id')->nullable()->after('parent_group_id')
                    ->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('community_groups', 'post_count')) {
                $table->unsignedInteger('post_count')->default(0)->after('member_count');
            }
        });

        Schema::table('community_groups', function (Blueprint $table) {
            if (! $this->hasIndex('community_groups', 'cg_group_type_idx')) {
                $table->index(['tenant_id', 'group_type'], 'cg_group_type_idx');
            }
            if (! $this->hasIndex('community_groups', 'cg_scope_idx')) {
                $table->index(['scope_type', 'scope_id'], 'cg_scope_idx');
            }
        });

        $this->backfill();

        Schema::create('community_group_verification_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_group_id')->constrained()->cascadeOnDelete();
            $table->string('from_level'); // none|bql_official|platform_verified
            $table->string('to_level');
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * `group_type` suy từ `kind` (đã chốt 2026-07-31: 11 nhóm `private` đều là
     * cư dân tự lập). `slug` sinh từ tên, khử trùng bằng hậu tố id nếu đụng
     * hàng trong cùng tenant. `scope_type/scope_id`: nhóm `platform` scope
     * `platform` (không `scope_id`); còn lại scope `project`.
     */
    private function backfill(): void
    {
        $groups = DB::table('community_groups')->select('id', 'tenant_id', 'name', 'kind', 'project_id')->get();
        $usedSlugs = []; // (tenant_id, slug) đã dùng trong lượt backfill này

        foreach ($groups as $g) {
            $groupType = CommunityGroupType::fromLegacyKind((string) $g->kind)->value;

            $base = Str::slug($g->name);
            $slug = $base;
            $key = $g->tenant_id.'|'.$slug;
            $suffix = 1;
            while (in_array($key, $usedSlugs, true)) {
                $suffix++;
                $slug = $base.'-'.$suffix;
                $key = $g->tenant_id.'|'.$slug;
            }
            $usedSlugs[] = $key;

            $scopeType = $g->kind === 'platform' ? 'platform' : 'project';
            $scopeId = $g->kind === 'platform' ? null : $g->project_id;

            DB::table('community_groups')->where('id', $g->id)->update([
                'group_type' => $groupType,
                'slug' => $slug,
                'scope_type' => $scopeType,
                'scope_id' => $scopeId,
                'lifecycle_state' => 'active',
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('community_group_verification_history');

        Schema::table('community_groups', function (Blueprint $table) {
            foreach (['cg_group_type_idx', 'cg_scope_idx'] as $index) {
                if ($this->hasIndex('community_groups', $index)) {
                    $table->dropIndex($index);
                }
            }
        });

        Schema::table('community_groups', function (Blueprint $table) {
            if (Schema::hasColumn('community_groups', 'parent_group_id')) {
                $table->dropConstrainedForeignId('parent_group_id');
            }
            if (Schema::hasColumn('community_groups', 'created_by_user_id')) {
                $table->dropConstrainedForeignId('created_by_user_id');
            }
            foreach (['slug', 'group_type', 'lifecycle_state', 'scope_type', 'scope_id', 'post_count'] as $column) {
                if (Schema::hasColumn('community_groups', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
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
