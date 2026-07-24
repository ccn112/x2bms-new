<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cổng thanh toán bật/tắt theo TENANT + áp dụng theo DỰ ÁN. Owner enable qua backend.
 *   - channel: vietqr | vnpay | momo
 *   - project_id NULL = áp dụng mọi dự án của tenant; có giá trị = riêng dự án đó.
 *   - config (json): vietqr = {bank_bin, bank_code, account_no, account_name};
 *     vnpay = {tmn_code, env}; momo = {partner_code, env}. Khoá bí mật để ở ENV,
 *     KHÔNG lưu DB (xem config/services.php).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('project_id')->nullable()->index();
            $table->string('channel', 20); // vietqr | vnpay | momo
            $table->boolean('is_enabled')->default(true);
            $table->string('display_name')->nullable();
            $table->json('config')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'project_id', 'channel'], 'payment_channels_scope_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_channels');
    }
};
