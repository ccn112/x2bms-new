<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4 — tìm kiếm full-text thực sự trên `doc_pages` (title + body).
 * Thêm FULLTEXT index (MySQL/InnoDB, 5.6+). Nếu engine không hỗ trợ (vd sqlite
 * khi test) thì bỏ qua — Controller có fallback LIKE.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! $this->supportsFulltext()) {
            return;
        }

        // Dùng raw để đặt tên index rõ ràng; guard nếu đã tồn tại.
        $exists = collect(DB::select("SHOW INDEX FROM doc_pages WHERE Key_name = 'doc_pages_fulltext'"))->isNotEmpty();
        if (! $exists) {
            DB::statement('ALTER TABLE doc_pages ADD FULLTEXT doc_pages_fulltext (title, body)');
        }
    }

    public function down(): void
    {
        if (! $this->supportsFulltext()) {
            return;
        }

        $exists = collect(DB::select("SHOW INDEX FROM doc_pages WHERE Key_name = 'doc_pages_fulltext'"))->isNotEmpty();
        if ($exists) {
            DB::statement('ALTER TABLE doc_pages DROP INDEX doc_pages_fulltext');
        }
    }

    private function supportsFulltext(): bool
    {
        return Schema::getConnection()->getDriverName() === 'mysql';
    }
};
