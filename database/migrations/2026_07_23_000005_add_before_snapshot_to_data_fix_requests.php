<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Màn BQL-02-07 (Yêu cầu đổi thông tin) tái dùng data_fix_requests.
 * + before_snapshot: chụp giá trị CŨ khi áp dụng (đối chiếu before/after + rollback).
 *
 * LƯU Ý: DB dev một số nơi thiếu bảng data_fix_requests (migration gốc 000012 bị đánh dấu
 * đã chạy nhưng bảng vắng — drift). Migration này tạo bảng nếu thiếu (khớp schema gốc), rồi
 * đảm bảo cột before_snapshot. ADD-ONLY, idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('data_fix_requests')) {
            Schema::create('data_fix_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('code')->nullable();
                $table->string('entity');
                $table->unsignedBigInteger('target_id')->nullable();
                $table->text('reason')->nullable();
                $table->json('requested_change')->nullable();
                $table->json('before_snapshot')->nullable();
                $table->string('status')->default('pending'); // pending|approved|applied|rejected
                $table->foreignId('requested_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('applied_at')->nullable();
                $table->timestamps();

                $table->index(['entity', 'target_id']);
                $table->index(['tenant_id', 'status']);
            });

            return;
        }

        Schema::table('data_fix_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('data_fix_requests', 'before_snapshot')) {
                $table->json('before_snapshot')->nullable()->after('requested_change');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('data_fix_requests', 'before_snapshot')) {
            Schema::table('data_fix_requests', function (Blueprint $table) {
                $table->dropColumn('before_snapshot');
            });
        }
    }
};
