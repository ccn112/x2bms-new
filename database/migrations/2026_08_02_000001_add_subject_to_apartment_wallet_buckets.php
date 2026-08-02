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
 * sản: quản lý, vệ sinh…). Không đụng dữ liệu ngăn cũ.
 *
 * THỨ TỰ ĐẢO INDEX QUAN TRỌNG (MySQL): unique cũ `apt_wallet_bucket_unique` có
 * `wallet_id` đứng đầu nên đang ĐỠ luôn khoá ngoại `wallet_id` — MySQL cấm drop một
 * index đang phục vụ FK (lỗi 1553). Vì thế phải TẠO unique MỚI trước (cũng bắt đầu
 * bằng `wallet_id` → đủ đỡ FK), rồi mới drop unique cũ. Idempotent theo SỰ TỒN TẠI
 * của index (không theo cờ "vừa thêm cột") để chạy lại sau khi fail nửa chừng vẫn
 * hoàn tất đúng.
 */
return new class extends Migration
{
    private const TABLE = 'apartment_wallet_buckets';
    private const OLD_UNIQUE = 'apt_wallet_bucket_unique';
    private const NEW_UNIQUE = 'apt_wallet_bucket_subject_unique';
    private const SUBJECT_IDX = 'apt_wallet_bucket_subject_idx';

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table) {
            if (! Schema::hasColumn(self::TABLE, 'subject_type')) {
                // Morph phẳng, KHÔNG khoá ngoại: subject là Vehicle/Meter/… (đa hình),
                // trùng cột `subject_type/subject_id` như `statement_lines`.
                $table->string('subject_type')->nullable()->after('fee_type_id');
            }
            if (! Schema::hasColumn(self::TABLE, 'subject_id')) {
                $table->unsignedBigInteger('subject_id')->nullable()->after('subject_type');
            }
        });

        // 1) Tạo unique MỚI + index tài sản TRƯỚC (unique mới vẫn có wallet_id đứng
        //    đầu nên FK wallet_id có index thay thế ngay).
        if (! $this->hasIndex(self::NEW_UNIQUE)) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                // Ngăn cũ unique theo (wallet, fee_category, fee_type_id) sẽ CHẶN NHẦM
                // hai xe khác nhau cùng loại phí (cùng fee_type_id) — phải mở rộng khoá.
                $table->unique(
                    ['wallet_id', 'fee_category', 'fee_type_id', 'subject_type', 'subject_id'],
                    self::NEW_UNIQUE,
                );
            });
        }
        if (! $this->hasIndex(self::SUBJECT_IDX)) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->index(['subject_type', 'subject_id'], self::SUBJECT_IDX);
            });
        }

        // 2) Giờ mới drop unique CŨ — FK wallet_id đã có unique mới đỡ thay.
        if ($this->hasIndex(self::OLD_UNIQUE)) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->dropUnique(self::OLD_UNIQUE);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        // Đối xứng: dựng lại unique cũ TRƯỚC (đỡ FK wallet_id), rồi mới drop unique
        // mới + index + cột.
        if (! $this->hasIndex(self::OLD_UNIQUE) && Schema::hasColumn(self::TABLE, 'fee_type_id')) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->unique(['wallet_id', 'fee_category', 'fee_type_id'], self::OLD_UNIQUE);
            });
        }
        if ($this->hasIndex(self::NEW_UNIQUE)) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->dropUnique(self::NEW_UNIQUE);
            });
        }
        if ($this->hasIndex(self::SUBJECT_IDX)) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->dropIndex(self::SUBJECT_IDX);
            });
        }
        if (Schema::hasColumn(self::TABLE, 'subject_type')) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->dropColumn(['subject_type', 'subject_id']);
            });
        }
    }

    /** Có index tên này trên bảng không — hỏi schema, chạy được cả MySQL lẫn sqlite. */
    private function hasIndex(string $name): bool
    {
        foreach (Schema::getIndexes(self::TABLE) as $index) {
            if (($index['name'] ?? null) === $name) {
                return true;
            }
        }

        return false;
    }
};
