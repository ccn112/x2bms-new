<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Snapshot BẤT BIẾN của chiến dịch tại thời điểm approved/published/sent — spec 06 §7.
 * Trang chi tiết campaign đã gửi ĐỌC snapshot, không render lại từ nội dung hiện tại.
 * Gồm nội dung render theo kênh, audience rule + resolved recipients, channel/template
 * version, cost estimate, approval chain — tất cả hash để phát hiện thay đổi sau duyệt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_snapshots', function (Blueprint $t) {
            $t->id();
            $t->foreignId('notification_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('version');
            $t->string('hash', 64);                 // sha256 canonical của (content+audience+channels)
            $t->json('content');                    // tiêu đề/summary/body/cta/cover/subtype đã chốt
            $t->json('audience');                   // rule + tổng hợp resolved (count, coverage)
            $t->json('channels');                   // cấu hình từng kênh + template version
            $t->json('approval')->nullable();       // chuỗi duyệt tại thời điểm chốt
            $t->decimal('cost_estimate', 12, 2)->default(0);
            $t->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('created_at')->nullable();

            $t->unique(['notification_id', 'version'], 'notif_snapshot_version_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_snapshots');
    }
};
