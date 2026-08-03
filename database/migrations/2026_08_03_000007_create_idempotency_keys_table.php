<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotency-Key store cho POST tài chính của cư dân (thanh toán/claim/intent/
 * preview). Client sinh key/thao tác; lần gửi lại (retry mạng, double-tap) mang
 * cùng key → middleware trả lại response đã lưu thay vì thực thi lần hai.
 *
 * Khóa duy nhất theo (idempotency_key, scope) với scope = hash(user|route) để
 * hai người / hai endpoint không đụng key của nhau.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->string('idempotency_key', 191);
            $table->string('scope', 64);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('method', 10);
            $table->string('path', 255);
            $table->string('request_hash', 64)->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->longText('response_body')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();

            $table->unique(['idempotency_key', 'scope']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
    }
};
