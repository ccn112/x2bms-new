<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * N0 (module notifications-multichannel, ADR-001) — CHUÔNG targeted.
 *
 * `activity_notifications`: mỗi dòng = MỘT dấu hiệu nhắm MỘT người về một sự kiện
 * (phiếu duyệt, trả lời công nợ, bình luận/cảm xúc/@mention…). Fan-out-on-write vì
 * đã targeted sẵn — số dòng theo hoạt động thật, KHÔNG theo dân số × broadcast
 * (broadcast BQL vẫn ở `notifications`, tính lúc đọc).
 *
 * `resident_bell_state`: mốc `bell_seen_at`/user (high-water) để đếm chưa-đọc broadcast
 * mà không phải ghi 2 triệu dòng "chưa đọc".
 *
 * Coalesce: nhiều tương tác cùng entity gộp một dòng qua `group_key` (unique) +
 * `coalesce_count`. Retention 180 ngày (archive ở slice sau).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recipient_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('kind');                 // ticket_approved | debt_reply | post_comment | reaction | mention | announcement | ...
            $table->string('subtype')->nullable();
            $table->string('title');
            $table->string('body')->nullable();
            $table->string('image_url')->nullable();
            $table->string('entity_type')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('action_key')->nullable();
            $table->foreignId('announcement_id')->nullable()->constrained('notifications')->nullOnDelete();
            $table->string('group_key')->nullable(); // coalesce: "post:123:reaction"
            $table->unsignedInteger('coalesce_count')->default(1);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['recipient_user_id', 'created_at']);        // feed keyset
            $table->index(['recipient_user_id', 'read_at']);           // đếm chưa đọc
            $table->index(['tenant_id', 'created_at']);                // retention/archive
            $table->unique(['recipient_user_id', 'group_key'], 'act_recipient_group_unique');
        });

        Schema::create('resident_bell_state', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained('users')->cascadeOnDelete();
            $table->timestamp('bell_seen_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_notifications');
        Schema::dropIfExists('resident_bell_state');
    }
};
