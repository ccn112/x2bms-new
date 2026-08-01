<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Giai đoạn 3 (grants & membership) của `COMMUNITY_IMPLEMENTATION_PLAN.md` —
 * `community_membership_grants` theo `COMMUNITY_DB_MAPPING.md` §3.
 *
 * Bảng MỚI — không có gì tương đương trong repo. Đây là thứ làm cho "một
 * người vào nhóm nhờ hai căn hộ khác nhau" đúng (COM-007): membership chỉ bị
 * thu hồi khi KHÔNG còn grant active nào (`COMMUNITY_RISK_ROLLBACK.md` R2).
 *
 * `source_id` là id của QUAN HỆ resident↔apartment (`resident_apartment_relations.id`),
 * KHÔNG phải apartment id — một quan hệ hết hiệu lực chỉ thu hồi đúng grant
 * sinh ra từ quan hệ đó, quan hệ khác (căn hộ khác/dự án khác) không bị đụng.
 *
 * Kèm 2 thay đổi additive trên `community_group_members` để hỗ trợ auto-enroll
 * X2Living cho tier `member` thuần (đã đăng nhập nhưng CHƯA CHẮC có hồ sơ
 * Resident nào — xem `docs 01_LOCKED_DECISIONS.md` §1):
 *  - `resident_id` chuyển NULLABLE (trước đây bắt buộc) — membership loại
 *    `system_enrollment` vào `platform_community` chỉ có `user_id`.
 *  - thêm unique (`community_group_id`,`user_id`) song song với unique cũ
 *    (`community_group_id`,`resident_id`) — an toàn vì NULL được cả MySQL lẫn
 *    SQLite coi là "khác nhau" trong unique index (nhiều dòng NULL không đụng
 *    nhau); dữ liệu hiện có toàn `user_id IS NULL` nên không có gì vỡ khi thêm.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_membership_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membership_id')->constrained('community_group_members')->cascadeOnDelete();
            $table->string('source_type', 30); // resident_relation|manual_join|invitation|system_enrollment
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('granted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 10)->default('active'); // active|revoked
            $table->timestamp('granted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['membership_id', 'source_type', 'source_id'], 'cmg_membership_source_unique');
            $table->index(['source_type', 'source_id', 'status'], 'cmg_source_status_idx');
            $table->index(['expires_at', 'status'], 'cmg_expires_status_idx');
        });

        Schema::table('community_group_members', function (Blueprint $table) {
            $table->foreignId('resident_id')->nullable()->change();
        });

        Schema::table('community_group_members', function (Blueprint $table) {
            $table->unique(['community_group_id', 'user_id'], 'group_member_user_unique');
        });
    }

    public function down(): void
    {
        Schema::table('community_group_members', function (Blueprint $table) {
            $table->dropUnique('group_member_user_unique');
        });

        Schema::table('community_group_members', function (Blueprint $table) {
            $table->foreignId('resident_id')->nullable(false)->change();
        });

        Schema::dropIfExists('community_membership_grants');
    }
};
