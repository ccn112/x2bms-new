<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Token thiết bị nhận push (FCM). Đa nền tảng: app cư dân (android/ios) + WEB
 * ADMIN (web). Một token là duy nhất; gắn với user đang đăng nhập. Đăng xuất /
 * đổi user thì token được gỡ hoặc gắn lại qua updateOrCreate theo token.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('device_tokens')) {
            return;
        }
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token', 512)->unique();
            $table->string('platform')->default('android'); // android|ios|web
            $table->string('device_label')->nullable();      // tên máy/trình duyệt (tuỳ chọn)
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
