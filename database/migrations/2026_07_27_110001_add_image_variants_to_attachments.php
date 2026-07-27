<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kích thước thật + các bản dẫn xuất của ảnh đính kèm
 * (đề xuất `x2mobile/docs/IMAGE_PIPELINE_PROPOSAL_20260727.md`, tầng 3).
 *
 * `width`/`height` là thứ app CẦN để dựng khung ảnh trước khi tải xong: thiếu
 * nó thì hoặc layout nhảy khi cuộn, hoặc phải ép cứng một tỉ lệ và cắt hỏng ảnh
 * dọc. `variants` = {thumb,feed,original} → path trên cùng disk.
 *
 * ADD-ONLY: ảnh cũ để NULL, resource tự rơi về `url` gốc.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            if (! Schema::hasColumn('attachments', 'width')) {
                $table->unsignedInteger('width')->nullable()->after('size');
            }
            if (! Schema::hasColumn('attachments', 'height')) {
                $table->unsignedInteger('height')->nullable()->after('width');
            }
            if (! Schema::hasColumn('attachments', 'variants')) {
                $table->json('variants')->nullable()->after('height');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            foreach (['width', 'height', 'variants'] as $col) {
                if (Schema::hasColumn('attachments', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
