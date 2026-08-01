<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** GĐ7 — cảm xúc trên bình luận cộng đồng (một cảm xúc / người / bình luận). */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('community_comment_reactions')) {
            return;
        }
        Schema::create('community_comment_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_comment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('emoji', 16);
            $table->timestamps();
            $table->unique(['community_comment_id', 'user_id']);
            $table->index(['community_comment_id', 'emoji']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_comment_reactions');
    }
};
