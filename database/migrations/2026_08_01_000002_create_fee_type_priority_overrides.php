<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase B4 (D4-bis) — override thứ tự phân bổ THEO DỰ ÁN.
 *
 * `fee_types` là danh mục TOÀN TENANT (một BQL sửa `payment_priority` ở đây sẽ đổi thứ
 * tự cho MỌI dự án của công ty đó), nhưng D4 chốt "override theo dự án" — hai dự án
 * cùng tenant phải xếp được thứ tự khác nhau (vd. dự án A ưu tiên phí gửi xe trước điện
 * vì bãi xe hay quá tải, dự án B thì không).
 *
 * Cân nhắc đã bỏ: mở rộng `fee_scope_assignments` (bảng có sẵn, áp fee_type/rate vào
 * scope project|building|apartment) thêm cột `payment_priority`. Bỏ vì bảng đó phục vụ
 * MỘT việc khác (gán biểu giá) — một dòng `fee_scope_assignments` có thể không tồn tại
 * cho fee_type nào đó (fee_type áp dụng toàn dự án qua đường khác), trộn thêm khái niệm
 * "thứ tự phân bổ" vào đó buộc phải tạo dòng giả chỉ để mang một con số, và bất kỳ ai
 * xoá/sửa dòng gán biểu giá vì lý do giá tiền sẽ vô tình xoá luôn override thứ tự.
 * Bảng riêng, một mục đích, độc lập vòng đời với việc gán biểu giá.
 *
 * Không soft-delete (giống `fee_scope_assignments` — bảng pivot/cấu hình thuần, xem
 * deny-list ở `2026_07_01_000025_add_soft_deletes_and_archive.php`): xoá một override là
 * "quay lại dùng mặc định tenant", không cần giữ lịch sử xoá.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_type_priority_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_type_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('payment_priority')->default(100);
            $table->timestamps();

            $table->unique(['project_id', 'fee_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_type_priority_overrides');
    }
};
