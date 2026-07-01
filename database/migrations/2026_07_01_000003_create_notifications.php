<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tier 2 — Notification (canonical C5). Phân quyền 3 lớp:
 *   owner_level = platform | tenant | project (ai TẠO/SỞ HỮU thông báo).
 *   notification_audiences = nhắm đối tượng theo tầng (all/tenant/project/building/
 *   apartment/role/resident) → platform gửi xuống công ty/dự án, công ty gửi xuống
 *   dự án/tòa, BQL gửi trong tòa/căn.
 * + notification_channels (kênh gửi), notification_delivery_logs (nhật ký gửi),
 *   notification_reads (trạng thái đã đọc per người).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();   // null = platform
            $table->string('owner_level')->default('project');                            // platform|tenant|project
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('building_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->nullable();
            $table->string('type')->default('announcement'); // announcement|billing|maintenance|emergency|community|system
            $table->string('title');
            $table->string('summary')->nullable();
            $table->longText('body')->nullable();
            $table->string('priority')->default('normal');    // low|normal|high|urgent
            $table->string('status')->default('draft');       // draft|scheduled|published|archived
            $table->boolean('is_pinned')->default(false);
            $table->string('cover_path')->nullable();
            $table->timestamp('publish_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('read_count')->default(0);
            $table->unsignedInteger('recipient_count')->default(0);
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('published_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['owner_level', 'status']);
        });

        Schema::create('notification_audiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')->constrained()->cascadeOnDelete();
            $table->string('scope_type');            // all|tenant|project|building|apartment|role|resident|user
            $table->unsignedBigInteger('scope_id')->nullable(); // null với scope_type=all
            $table->timestamps();

            $table->index(['scope_type', 'scope_id']);
        });

        Schema::create('notification_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')->constrained()->cascadeOnDelete();
            $table->string('channel');               // app|email|sms|zalo|push
            $table->boolean('enabled')->default(true);
            $table->json('config')->nullable();
            $table->timestamps();
        });

        Schema::create('notification_delivery_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('resident_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel')->default('app');
            $table->string('status')->default('queued'); // queued|sent|failed|read
            $table->string('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['notification_id', 'status']);
        });

        Schema::create('notification_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('resident_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['notification_id', 'user_id'], 'notif_read_user_unique');
            $table->index(['notification_id', 'resident_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_reads');
        Schema::dropIfExists('notification_delivery_logs');
        Schema::dropIfExists('notification_channels');
        Schema::dropIfExists('notification_audiences');
        Schema::dropIfExists('notifications');
    }
};
