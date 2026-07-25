<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bình luận của cư dân dưới một thông báo (GET/POST
 * /resident/notifications/{id}/comments). Author = user (tài khoản person-level).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('author_name')->nullable();
            $table->text('body');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['notification_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_comments');
    }
};
