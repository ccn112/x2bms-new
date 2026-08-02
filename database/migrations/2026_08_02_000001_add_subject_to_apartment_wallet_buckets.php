<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * D6 Slice B — thêm CHIỀU TÀI SẢN vào ngăn ví (`apartment_wallet_buckets`).
 *
 * Trước bản này ngăn chỉ key theo (wallet_id, fee_category, fee_type_id). Khi cư
 * dân trả trước cho MỘT tài sản (vd chiếc xe 51K-838888) mà dư tiền, phần thừa
 * phải ở lại ĐÚNG chiều tài sản đó để lần sau tự trừ tiếp cho chính chiếc xe ấy —
 * không lẫn sang xe khác cùng loại phí. Thêm `subject_type`/`subject_id` (nullable)
 * và đưa chúng vào unique index.
 *
 * `subject_type`/`subject_id` NULL = ngăn theo fee_type NHƯ CŨ (phí không gắn tài
 * sản: quản lý, vệ sinh…). Không đụng dữ liệu ngăn cũ. Idempotent: guard
 * `Schema::hasColumn` để chạy lại không vỡ; chỉ đảo unique index khi vừa thêm cột.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('apartment_wallet_buckets')) {
            return;
        }

        $addedSubject = false;
        Schema::table('apartment_wallet_buckets', function (Blueprint $table) use (&$addedSubject) {
            if (! Schema::hasColumn('apartment_wallet_buckets', 'subject_type')) {
                // Morph phẳng, KHÔNG khoá ngoại: subject là Vehicle/Meter/… (đa hình),
                // trùng cột `subject_type/subject_id` như `statement_lines`.
                $table->string('subject_type')->nullable()->after('fee_type_id');
                $addedSubject = true;
            }
            if (! Schema::hasColumn('apartment_wallet_buckets', 'subject_id')) {
                $table->unsignedBigInteger('subject_id')->nullable()->after('subject_type');
            }
        });

        // Chỉ đảo index khi migration này THỰC SỰ vừa thêm cột (chạy lần đầu) — tránh
        // dropUnique một index đã bị đảo ở lần chạy trước.
        if ($addedSubject) {
            Schema::table('apartment_wallet_buckets', function (Blueprint $table) {
                // Ngăn cũ unique theo (wallet, fee_category, fee_type_id) sẽ CHẶN NHẦM
                // hai xe khác nhau cùng loại phí (cùng fee_type_id) — phải mở rộng khoá.
                $table->dropUnique('apt_wallet_bucket_unique');
                $table->unique(
                    ['wallet_id', 'fee_category', 'fee_type_id', 'subject_type', 'subject_id'],
                    'apt_wallet_bucket_subject_unique',
                );
                $table->index(['subject_type', 'subject_id'], 'apt_wallet_bucket_subject_idx');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('apartment_wallet_buckets')) {
            return;
        }

        if (Schema::hasColumn('apartment_wallet_buckets', 'subject_type')) {
            Schema::table('apartment_wallet_buckets', function (Blueprint $table) {
                $table->dropUnique('apt_wallet_bucket_subject_unique');
                $table->dropIndex('apt_wallet_bucket_subject_idx');
                $table->unique(['wallet_id', 'fee_category', 'fee_type_id'], 'apt_wallet_bucket_unique');
                $table->dropColumn(['subject_type', 'subject_id']);
            });
        }
    }
};
