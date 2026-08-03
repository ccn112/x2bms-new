<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P1b — `liability_periods` (canonical): AI chịu trách nhiệm tài chính cho căn hộ
 * trong KHOẢNG nào (D11/D12). Nghĩa vụ (charge) gắn với liability period, KHÔNG
 * mặc định "chủ hộ hiện tại" — để chuyển chủ giữa kỳ không tự đẩy nợ chủ cũ sang
 * chủ mới (Phase 5).
 *
 * Additive, nullable, không đụng dữ liệu hiện có. Backfill 1 liability "owner,
 * mọi family, mở" cho mỗi căn qua `billing:backfill-liability-periods` (tách khỏi
 * migration để không chạy logic dữ liệu trong DDL).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('liability_periods')) {
            return;
        }

        Schema::create('liability_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('apartment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resident_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role')->default('owner');      // owner | former_owner | tenant
            $table->date('liable_from')->nullable();
            $table->date('liable_to')->nullable();          // NULL = còn hiệu lực (mở)
            $table->json('scope')->nullable();              // NULL/['all'] = mọi family; hoặc mảng family code
            $table->unsignedBigInteger('transfer_authorization_id')->nullable(); // Phase 5 chuyển chủ
            $table->string('source')->default('backfill');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'apartment_id', 'role']);
            $table->index(['apartment_id', 'liable_from', 'liable_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liability_periods');
    }
};
