<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tin rao (real_estate_listings) — thêm dấu vết "BQL đẩy lên SuperAdmin xét"
 * (chốt 2026-07-30, màn duyệt Filament /admin + /sa).
 *
 * ## Vì sao KHÔNG thêm giá trị mới cho `approval_status`
 *
 * Bản nháp đầu tiên định thêm approval_status='escalated' để KHOÁ hẳn quyền
 * duyệt của BQL khi đã đẩy lên. Chủ dự án chốt lại (2026-07-30, sau khi xem
 * bản nháp): SA phải xử lý được MỌI tin — kể cả tin BQL chưa từng đụng tới,
 * vì có dự án không có người trực hoặc BQL bỏ quên. Escalation vì vậy chỉ là
 * MỘT TÍN HIỆU ƯU TIÊN (BQL chủ động xin ý kiến cấp trên), không phải điều
 * kiện để SA được quyền hành động — nên vẫn giữ `approval_status` chỉ ba giá
 * trị pending|approved|rejected như cũ, và escalated_at/escalated_by/note là
 * CỘT RIÊNG, độc lập, không tham gia vào việc tính "ai được phép duyệt".
 *
 * ## Luật tránh hai cấp (BQL/SA) duyệt/từ chối ngược nhau
 *
 * KHÔNG khoá theo trạng thái escalated (xem trên). Thay vào đó, hành động
 * duyệt/từ chối (viết ở `App\Filament\Concerns\ModeratesRealEstateListings`)
 * luôn SELECT ... FOR UPDATE bản ghi mới nhất trong một transaction rồi mới
 * áp quyết định, và bỏ qua (no-op, báo "đã được xử lý") nếu bản ghi đã ở đúng
 * trạng thái đích. Nhờ vậy quyết định luôn tuần tự hoá theo ai COMMIT trước —
 * không có chuyện một tin vừa approved vừa rejected treo lơ lửng do đụng độ.
 *
 * `escalated_at` KHÔNG bị xoá khi tin được quyết định sau đó — giữ làm dấu
 * vết lịch sử "tin này từng được BQL xin ý kiến cấp trên", để BQL/SA nhìn lại
 * biết vì sao một tin cụ thể lại có quyết định từ SA thay vì từ BQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('real_estate_listings', function (Blueprint $table) {
            if (! Schema::hasColumn('real_estate_listings', 'escalated_at')) {
                $table->timestamp('escalated_at')->nullable()->after('rejection_reason');
            }
            if (! Schema::hasColumn('real_estate_listings', 'escalated_by_user_id')) {
                $table->foreignId('escalated_by_user_id')->nullable()->after('escalated_at')
                    ->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('real_estate_listings', 'escalation_note')) {
                $table->string('escalation_note', 500)->nullable()->after('escalated_by_user_id');
            }
        });
    }

    public function down(): void
    {
        // Add-only theo luật của dự án: các cột này chỉ mới thêm ở bản này nên
        // rollback an toàn (không đụng cột có thể đã tồn tại từ trước), nhưng
        // vẫn guard bằng hasColumn phòng trường hợp chạy down() hai lần.
        Schema::table('real_estate_listings', function (Blueprint $table) {
            if (Schema::hasColumn('real_estate_listings', 'escalated_by_user_id')) {
                $table->dropConstrainedForeignId('escalated_by_user_id');
            }
            $drop = array_filter(
                ['escalated_at', 'escalation_note'],
                fn (string $c) => Schema::hasColumn('real_estate_listings', $c)
            );
            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }
};
