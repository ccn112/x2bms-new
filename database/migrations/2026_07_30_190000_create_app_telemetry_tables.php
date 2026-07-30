<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nhật ký màn hình + báo lỗi từ app (chốt 2026-07-30).
 *
 * Chủ dự án chốt: ghi theo **thiết bị**, có `user_id` thì gắn kèm; thiết bị ẩn danh
 * vẫn phải ghi và giữ `device_id` để **ghép với người dùng định danh sau này**.
 * Gửi **theo lô, định kỳ** (không phải mỗi lần đổi màn một request).
 *
 * ## Vì sao HAI bảng chứ không một
 *
 * `app_screen_events` là bảng THÔ, dự kiến hàng triệu dòng/ngày. Không truy vấn báo
 * cáo trực tiếp trên nó (đếm distinct device trên vài trăm triệu dòng là chết), và
 * nó bị **dọn theo hạn lưu** (`config/telemetry.php`).
 *
 * `app_screen_daily_stats` là bảng TỔNG HỢP theo ngày — nhỏ, giữ mãi, là nguồn cho
 * mọi biểu đồ. Job tổng hợp phải chạy TRƯỚC khi dọn bảng thô, nếu không mất số.
 *
 * ## Vì sao dùng 0 chứ không NULL cho tenant_id/project_id ở bảng tổng hợp
 * MySQL coi mỗi NULL là một giá trị khác nhau trong unique index, nên
 * `unique(stat_date, screen_key, tenant_id, project_id)` với NULL sẽ cho phép chèn
 * trùng vô hạn. Dùng 0 = "không xác định" (thiết bị ẩn danh, chưa chọn căn hộ).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_screen_events', function (Blueprint $table) {
            $table->id();

            // X-Device-Id — ổn định theo lần cài. Ẩn danh thì đây là danh tính duy
            // nhất; đăng nhập rồi thì `user_id` được gắn thêm, và các dòng CŨ của
            // cùng device_id chính là hành vi trước khi định danh (ghép được sau).
            $table->string('device_id', 64)->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();

            // Khoá màn do app quyết, dạng `nhóm.màn` — vd `community.feed`,
            // `billing.statement_detail`. Không lưu tiêu đề tiếng Việt: đổi chữ trên
            // UI là vỡ hết số liệu lịch sử.
            $table->string('screen_key', 100);
            $table->string('route', 200)->nullable();

            // `view` = vào màn · `action` = một thao tác trong màn (chủ dự án yêu cầu
            // "thao tác các màn hình"). Tách bằng cột chứ không bằng hai bảng.
            $table->string('kind', 20)->default('view');
            $table->string('action', 60)->nullable();

            // Giờ do CLIENT ghi (lô gửi trễ), nên phải lưu riêng với created_at là
            // giờ server nhận — lệch giữa hai cái là dấu hiệu app gửi lô rất muộn.
            $table->timestamp('occurred_at');
            $table->unsignedInteger('duration_ms')->nullable();

            $table->string('session_id', 64)->nullable();
            $table->string('app_version', 40)->nullable();
            $table->string('platform', 20)->nullable();     // android|ios|web
            $table->string('os_version', 40)->nullable();

            $table->timestamp('created_at')->nullable();

            $table->index(['screen_key', 'occurred_at']);
            $table->index(['device_id', 'occurred_at']);
            $table->index(['tenant_id', 'occurred_at']);
            $table->index('occurred_at');                    // để job dọn theo hạn
        });

        Schema::create('app_screen_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->date('stat_date');
            $table->string('screen_key', 100);
            $table->unsignedBigInteger('tenant_id')->default(0);
            $table->unsignedBigInteger('project_id')->default(0);

            $table->unsignedBigInteger('views')->default(0);
            $table->unsignedBigInteger('actions')->default(0);
            $table->unsignedBigInteger('unique_devices')->default(0);
            $table->unsignedBigInteger('unique_users')->default(0);
            $table->unsignedInteger('avg_duration_ms')->nullable();

            $table->timestamps();

            $table->unique(['stat_date', 'screen_key', 'tenant_id', 'project_id'], 'app_screen_daily_unique');
            $table->index(['stat_date', 'views']);
        });

        Schema::create('app_screen_reports', function (Blueprint $table) {
            $table->id();

            $table->string('device_id', 64)->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();

            // Màn đang mở lúc bấm nút báo lỗi — cái này mới là giá trị chính của
            // tính năng: biết lỗi Ở ĐÂU mà không phải hỏi lại người báo.
            $table->string('screen_key', 100)->nullable();
            $table->string('route', 200)->nullable();

            $table->string('kind', 20)->default('bug');      // bug|idea|other
            $table->text('message');

            $table->string('app_version', 40)->nullable();
            $table->string('platform', 20)->nullable();
            $table->string('os_version', 40)->nullable();
            $table->string('locale', 20)->nullable();

            $table->string('status', 20)->default('new');    // new|triaged|in_progress|resolved|wont_fix
            $table->foreignId('assigned_to_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_note')->nullable();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
            $table->index(['screen_key', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_screen_reports');
        Schema::dropIfExists('app_screen_daily_stats');
        Schema::dropIfExists('app_screen_events');
    }
};
