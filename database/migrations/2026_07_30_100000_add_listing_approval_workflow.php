<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tin rao (real_estate_listings) — thêm quy trình DUYỆT TRƯỚC (chủ dự án chốt
 * 2026-07-30), khác với bài cộng đồng thường (hậu kiểm).
 *
 * ## Vì sao thêm approval_status thay vì tái dùng `status`
 *
 * `status` sẵn có (active|pending|sold|rented|expired) là VÒNG ĐỜI GIAO DỊCH —
 * tin còn hiệu lực hay đã bán/hết hạn. `approval_status` là VÒNG ĐỜI KIỂM DUYỆT
 * — BQL có cho hiển thị hay không. Hai trục độc lập: một tin `active` (còn rao)
 * vẫn có thể đang `pending` (chờ duyệt) hoặc `rejected`. Gộp chung một cột thì
 * không biểu diễn được tin "đã duyệt nhưng đã bán" khác với "chưa duyệt".
 *
 * ## Vì sao denormalize interest_count/inquiry_count
 *
 * Thẻ tin rao nằm trong feed cộng đồng — nơi rất đông người xem. COUNT(*) trên
 * `listing_inquiries` mỗi lần vẽ thẻ là chỗ vỡ đầu tiên khi feed có vài nghìn
 * lượt xem/phút. Đếm một lần khi ghi (increment/decrement nguyên tử), đọc thẳng
 * cột khi vẽ.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('real_estate_listings', function (Blueprint $table) {
            // pending|approved|rejected — mặc định 'pending' cho tin MỚI. Tin đã
            // có trước bản này được backfill 'approved' bên dưới (chúng đã công
            // khai từ trước khi có khái niệm duyệt, hạ xuống pending sẽ làm biến
            // mất tin đang hiển thị thật ngoài production/demo).
            $table->string('approval_status')->default('pending')->after('status');
            $table->foreignId('approved_by_user_id')->nullable()->after('approval_status')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by_user_id');
            // Cư dân phải THẤY LÝ DO từ chối ngay trên tin của mình — không thì
            // tưởng app lỗi mất tin (giống nguyên tắc `moderation_reason` của bài
            // cộng đồng).
            $table->string('rejection_reason', 500)->nullable()->after('approved_at');

            $table->unsignedInteger('interest_count')->default(0)->after('rejection_reason');
            $table->unsignedInteger('inquiry_count')->default(0)->after('interest_count');

            // Tài khoản đã ĐĂNG tin — khác `owner_resident_id` (chủ căn hợp
            // pháp) khi người đăng là môi giới/người thuê được BQL cấp quyền.
            // Cần cột riêng vì "ai tạo" và "căn hộ thuộc về ai" là hai câu hỏi
            // khác nhau: môi giới đăng hộ chủ nhà thì owner_resident_id vẫn là
            // chủ nhà, nhưng created_by_user_id mới là người có quyền rút tin.
            $table->foreignId('created_by_user_id')->nullable()->after('owner_resident_id')
                ->constrained('users')->nullOnDelete();

            $table->index(['tenant_id', 'project_id', 'approval_status', 'status'], 'rel_scope_status_idx');
        });

        // Backfill: tin đã tồn tại trước bản này (seed cũ) coi như đã duyệt —
        // chúng vốn đã `status=active` công khai, không phải chờ ai duyệt lại.
        DB::table('real_estate_listings')->update([
            'approval_status' => 'approved',
            'approved_at' => now(),
        ]);

        Schema::table('listing_inquiries', function (Blueprint $table) {
            // Tài khoản đã gửi liên hệ — LUÔN có (kể cả khi họ chưa là cư dân,
            // quyết định 2026-07-30 #3: có tài khoản thì được để lại thông tin).
            // `resident_id` chỉ có khi người gửi là cư dân — dùng để hiện "căn
            // hộ nào quan tâm" cho người bán, không phải điều kiện bắt buộc.
            $table->foreignId('user_id')->nullable()->after('resident_id')
                ->constrained('users')->nullOnDelete();

            // interest|viewing|contact — MỘT bảng cho cả ba, không phải ba bảng:
            // hộp thư lead của người bán phải nằm một chỗ. `interest` là lượt
            // bấm "Quan tâm" (không PII, xem là tín hiệu quan tâm); `viewing` đi
            // kèm `preferred_at`; `contact` đi kèm name/phone/message như cũ.
            $table->string('kind', 20)->default('contact')->after('status');
            $table->timestamp('preferred_at')->nullable()->after('kind');

            $table->index(['real_estate_listing_id', 'kind'], 'inq_listing_kind_idx');
        });

        Schema::create('listing_posting_grants', function (Blueprint $table) {
            $table->id();
            // Quyền rao đã XÁC MINH cho người thuê/môi giới (quyết định #1: chủ
            // căn được rao trực tiếp, người khác phải được BQL cấp quyền).
            // Khoá theo (apartment_id, resident_id) — MỘT người cho MỘT căn cụ
            // thể, không phải một quyền chung chung cho cả dự án: BQL phải biết
            // chính xác ai được rao căn nào để chịu trách nhiệm khi có tranh chấp.
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('apartment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resident_id')->constrained()->cascadeOnDelete();
            $table->foreignId('granted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('active'); // active|revoked
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->unique(['apartment_id', 'resident_id'], 'listing_grant_unique');
        });

        Schema::table('projects', function (Blueprint $table) {
            // Bật thì tin lên NGAY (approval_status=approved tự động khi tạo);
            // tắt thì tin luôn vào hàng chờ BQL duyệt. Không dùng package
            // laravel-settings (có cài nhưng chưa nơi nào trong app dùng thật —
            // thêm một cột đơn giản hơn là mở một hệ cấu hình mới cho một cờ
            // bật/tắt duy nhất).
            $table->boolean('listings_auto_approve')->default(false)->after('sales_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_posting_grants');

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('listings_auto_approve');
        });

        Schema::table('listing_inquiries', function (Blueprint $table) {
            $table->dropIndex('inq_listing_kind_idx');
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['kind', 'preferred_at']);
        });

        Schema::table('real_estate_listings', function (Blueprint $table) {
            $table->dropIndex('rel_scope_status_idx');
            $table->dropConstrainedForeignId('approved_by_user_id');
            $table->dropConstrainedForeignId('created_by_user_id');
            $table->dropColumn([
                'approval_status', 'approved_at', 'rejection_reason',
                'interest_count', 'inquiry_count',
            ]);
        });
    }
};
