<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bổ sung ghim / quan trọng / ảnh cho bài cộng đồng (GET /resident/community/posts). ADD-ONLY.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_posts', function (Blueprint $table) {
            if (! Schema::hasColumn('community_posts', 'is_pinned')) {
                $table->boolean('is_pinned')->default(false)->after('status');
            }
            if (! Schema::hasColumn('community_posts', 'is_important')) {
                $table->boolean('is_important')->default(false)->after('is_pinned');
            }
            if (! Schema::hasColumn('community_posts', 'image_paths')) {
                $table->json('image_paths')->nullable()->after('is_important');
            }
        });
    }

    public function down(): void
    {
        Schema::table('community_posts', function (Blueprint $table) {
            $table->dropColumn(['is_pinned', 'is_important', 'image_paths']);
        });
    }
};
