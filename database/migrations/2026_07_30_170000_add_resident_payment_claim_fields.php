<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cư dân tự chuyển khoản → nộp ảnh chứng từ → BQL duyệt (chốt 2026-07-30).
 *
 * KHÔNG dựng bảng song song kiểu `payment_claims`. Một khoản tiền cư dân khai
 * BÁO chính là một `Payment` ở trạng thái `pending`; khi BQL duyệt thì nó thành
 * `confirmed` và sinh `payment_allocations` + `receipts` như mọi khoản khác.
 * Dựng bảng riêng thì phải copy sang `payments` lúc duyệt, tức là có hai nguồn
 * sự thật cho cùng một số tiền — và `payment_allocations` mất vai trò ledger
 * duy nhất (CANONICAL_ENTITY_MAP C8).
 *
 * Vì vậy `payments.status` mở rộng vốn từ: `pending` (cư dân khai, chờ BQL) ·
 * `confirmed` · `rejected` (BQL từ chối, PHẢI có lý do) · `reversed`.
 *
 * `claimed_*` là những gì cư dân KHAI, không phải sự thật đã đối soát — giữ
 * nguyên cả sau khi duyệt để còn đối chiếu được lời khai với thực tế.
 *
 * Nhóm `ai_*` là GỢI Ý đọc từ ảnh chứng từ, chỉ để BQL bấm nhanh hơn. Không có
 * đường nào cho AI tự chuyển sang `confirmed`: sai một lần là ghi nhận tiền
 * không có thật.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Nguồn khoản tiền: staff (BQL nhập tay) · resident_app (cư dân khai
            // báo) · gateway (cổng thanh toán) · reconciliation (khớp sao kê).
            // Mặc định `staff` để mọi bản ghi CŨ giữ đúng ý nghĩa hiện tại.
            $table->string('source')->default('staff')->after('status');

            $table->foreignId('submitted_by_id')->nullable()->after('source')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable()->after('submitted_by_id');

            // Hoá đơn và tài khoản nhận mà CƯ DÂN khai. Chưa phân bổ gì lúc này:
            // phân bổ chỉ được tạo khi duyệt, nếu tạo sớm thì công nợ giảm trước
            // khi tiền được xác nhận.
            $table->foreignId('claimed_statement_id')->nullable()->after('submitted_at')
                ->constrained('statements')->nullOnDelete();
            $table->foreignId('claimed_bank_account_id')->nullable()->after('claimed_statement_id')
                ->constrained('bank_accounts')->nullOnDelete();

            $table->foreignId('reviewed_by_id')->nullable()->after('claimed_bank_account_id')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by_id');
            $table->string('review_note', 500)->nullable()->after('reviewed_at');

            // Gợi ý của AI đọc ảnh chứng từ.
            $table->json('ai_extraction')->nullable()->after('review_note');
            $table->string('ai_suggestion')->nullable()->after('ai_extraction'); // approve|review|reject
            $table->unsignedTinyInteger('ai_confidence')->nullable()->after('ai_suggestion'); // 0..100
            $table->timestamp('ai_checked_at')->nullable()->after('ai_confidence');

            // Hàng chờ duyệt của BQL luôn lọc theo tenant/dự án + status.
            $table->index(['tenant_id', 'status']);
            $table->index(['apartment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'status']);
            $table->dropIndex(['apartment_id', 'status']);
            $table->dropConstrainedForeignId('submitted_by_id');
            $table->dropConstrainedForeignId('claimed_statement_id');
            $table->dropConstrainedForeignId('claimed_bank_account_id');
            $table->dropConstrainedForeignId('reviewed_by_id');
            $table->dropColumn([
                'source', 'submitted_at', 'reviewed_at', 'review_note',
                'ai_extraction', 'ai_suggestion', 'ai_confidence', 'ai_checked_at',
            ]);
        });
    }
};
