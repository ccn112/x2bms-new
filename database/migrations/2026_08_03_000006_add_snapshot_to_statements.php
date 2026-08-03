<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P3 — Snapshot BẤT BIẾN khi phát hành bảng kê (canonical D15 / invariant "published
 * statement là immutable").
 *
 * Lúc `publish`, chụp lại toàn bộ nội dung bảng kê (dòng phí, số tiền, kỳ dịch vụ,
 * tổng) thành JSON + `snapshot_checksum` (sha256). Đây là "bản gốc pháp lý" cư dân
 * nhận — dù dữ liệu dòng có bị đổi về sau (không nên, nhưng để phòng), bản đã phát
 * hành không đổi. `billing:verify-published-snapshots` so live vs snapshot để bắt
 * lệch.
 *
 * Additive, nullable, reversible.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('statements', function (Blueprint $table) {
            if (! Schema::hasColumn('statements', 'snapshot')) {
                $table->json('snapshot')->nullable()->after('sent_channel');
            }
            if (! Schema::hasColumn('statements', 'snapshot_checksum')) {
                $table->string('snapshot_checksum', 64)->nullable()->after('snapshot');
            }
            if (! Schema::hasColumn('statements', 'snapshot_at')) {
                $table->timestamp('snapshot_at')->nullable()->after('snapshot_checksum');
            }
        });
    }

    public function down(): void
    {
        Schema::table('statements', function (Blueprint $table) {
            foreach (['snapshot', 'snapshot_checksum', 'snapshot_at'] as $col) {
                if (Schema::hasColumn('statements', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
