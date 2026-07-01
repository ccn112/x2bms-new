<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tier 2 — hoàn thiện Feedback (phản ánh, canonical C3). Làm giàu `feedback_requests`
 * (ADD-ONLY) + các bảng con: feedback_comments, feedback_attachments,
 * feedback_assignments, feedback_status_histories. project_id thêm vào để RBAC 3 lớp
 * lọc theo dự án (BQL) / tenant (công ty) / tất cả (platform).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedback_requests', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('building_id')->constrained()->nullOnDelete();
            $table->foreignId('resident_id')->nullable()->after('apartment_id')->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->after('resident_id')->constrained()->nullOnDelete(); // người gửi (tài khoản)
            $table->string('code')->nullable()->after('id');
            $table->text('description')->nullable()->after('title');
            $table->string('channel')->default('app')->after('priority'); // app|web|hotline|email|walk_in
            $table->foreignId('assigned_to_id')->nullable()->after('channel')->constrained('users')->nullOnDelete();
            $table->foreignId('team_id')->nullable()->after('assigned_to_id')->constrained()->nullOnDelete();
            $table->timestamp('sla_due_at')->nullable()->after('team_id');
            $table->timestamp('resolved_at')->nullable()->after('sla_due_at');
            $table->timestamp('closed_at')->nullable()->after('resolved_at');
            $table->unsignedTinyInteger('rating')->nullable()->after('closed_at'); // 1..5 hài lòng
            $table->string('rating_comment')->nullable()->after('rating');
        });

        Schema::create('feedback_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feedback_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('resident_id')->nullable()->constrained()->nullOnDelete();
            $table->string('author_name')->nullable();
            $table->text('body');
            $table->boolean('is_internal')->default(false); // ghi chú nội bộ BQL, cư dân không thấy
            $table->timestamps();

            $table->index('feedback_request_id');
        });

        Schema::create('feedback_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feedback_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('feedback_comment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('path');
            $table->string('name')->nullable();
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->foreignId('uploaded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('feedback_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feedback_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('assigned'); // assigned|accepted|reassigned|done
            $table->string('note')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();
        });

        Schema::create('feedback_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feedback_request_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('changed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamp('changed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_status_histories');
        Schema::dropIfExists('feedback_assignments');
        Schema::dropIfExists('feedback_attachments');
        Schema::dropIfExists('feedback_comments');
        Schema::table('feedback_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_id');
            $table->dropConstrainedForeignId('resident_id');
            $table->dropConstrainedForeignId('user_id');
            $table->dropConstrainedForeignId('assigned_to_id');
            $table->dropConstrainedForeignId('team_id');
            $table->dropColumn(['code', 'description', 'channel', 'sla_due_at', 'resolved_at', 'closed_at', 'rating', 'rating_comment']);
        });
    }
};
