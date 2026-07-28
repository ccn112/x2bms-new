<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Addendum — thư viện ảnh dự án: nguồn ảnh + cờ ảnh bìa + cờ watermark.
 * Idempotent (hasColumn guard).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_media', function (Blueprint $table) {
            if (! Schema::hasColumn('project_media', 'source')) {
                $table->string('source')->nullable()->after('media_type'); // batdongsan|official|manual
            }
            if (! Schema::hasColumn('project_media', 'is_cover')) {
                $table->boolean('is_cover')->default(false)->after('sort_order');
            }
            if (! Schema::hasColumn('project_media', 'is_watermarked')) {
                $table->boolean('is_watermarked')->default(false)->after('is_cover');
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_media', function (Blueprint $table) {
            foreach (['source', 'is_cover', 'is_watermarked'] as $c) {
                if (Schema::hasColumn('project_media', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
