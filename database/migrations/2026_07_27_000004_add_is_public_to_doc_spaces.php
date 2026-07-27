<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2 — site tài liệu PUBLIC (doc.x2.fino.vn).
 * `is_public` = true → khách CHƯA đăng nhập vẫn xem được (chỉ trang published).
 * false (mặc định) → giữ nguyên: yêu cầu login + quyền docs.view.{audience}.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doc_spaces', function (Blueprint $table) {
            $table->boolean('is_public')->default(false)->after('is_published');
        });
    }

    public function down(): void
    {
        Schema::table('doc_spaces', function (Blueprint $table) {
            $table->dropColumn('is_public');
        });
    }
};
