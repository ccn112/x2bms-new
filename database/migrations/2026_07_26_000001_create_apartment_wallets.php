<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ví cư dân theo CĂN HỘ (1 ví / căn hộ). Khác với `wallets` (quỹ công ty per-tenant).
        if (! Schema::hasTable('apartment_wallets')) {
            Schema::create('apartment_wallets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('building_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('apartment_id')->constrained()->cascadeOnDelete();
                $table->string('currency', 8)->default('VND');
                // balance = quỹ chung (chưa gán fee_cat/fee_type). Tổng khả dụng = balance + Σ buckets.
                $table->decimal('balance', 18, 2)->default(0);
                $table->string('status')->default('active'); // active|frozen|closed
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['apartment_id', 'deleted_at']);
                $table->index(['tenant_id', 'building_id']);
            });
        }

        // Các NGĂN: earmark tiền thừa theo fee_category, và tùy chọn xuống fee_type cụ thể.
        if (! Schema::hasTable('apartment_wallet_buckets')) {
            Schema::create('apartment_wallet_buckets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('wallet_id')->constrained('apartment_wallets')->cascadeOnDelete();
                $table->string('fee_category'); // management|parking|utility|service|other
                $table->foreignId('fee_type_id')->nullable()->constrained('fee_types')->nullOnDelete();
                $table->decimal('balance', 18, 2)->default(0);
                $table->timestamps();
                $table->unique(['wallet_id', 'fee_category', 'fee_type_id'], 'apt_wallet_bucket_unique');
            });
        }

        // Sổ ví: IN = phiếu thu / nộp tiền / topup; OUT = hạch toán trả nợ / hoàn / điều chỉnh.
        if (! Schema::hasTable('apartment_wallet_transactions')) {
            Schema::create('apartment_wallet_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('wallet_id')->constrained('apartment_wallets')->cascadeOnDelete();
                $table->foreignId('apartment_id')->constrained()->cascadeOnDelete();
                $table->string('direction'); // in|out
                $table->string('type');      // receipt|payment_slip|topup|adjustment_in | debt_settlement|refund|adjustment_out
                $table->string('fee_category')->nullable(); // ngăn nào (null = quỹ chung)
                $table->foreignId('fee_type_id')->nullable()->constrained('fee_types')->nullOnDelete();
                $table->decimal('amount', 18, 2);
                $table->decimal('balance_after', 18, 2)->nullable(); // tổng khả dụng của ví sau giao dịch
                $table->nullableMorphs('reference'); // receipt / statement / statement_line / debt
                $table->string('reference_no')->nullable();
                $table->string('description')->nullable();
                $table->string('status')->default('confirmed'); // pending|confirmed|failed
                $table->timestamp('posted_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['wallet_id', 'direction']);
                $table->index('posted_at');
            });
        }

        // statement_lines: theo dõi nợ per-service. LƯU Ý fee_type_id có thể ĐÃ tồn tại → guard.
        Schema::table('statement_lines', function (Blueprint $table) {
            if (! Schema::hasColumn('statement_lines', 'paid_amount')) {
                $table->decimal('paid_amount', 16, 2)->default(0)->after('amount');
            }
            if (! Schema::hasColumn('statement_lines', 'fee_category')) {
                $table->string('fee_category')->nullable()->after('fee_type');
            }
            if (! Schema::hasColumn('statement_lines', 'fee_type_id')) {
                $table->foreignId('fee_type_id')->nullable()->after('fee_category')->constrained('fee_types')->nullOnDelete();
            }
            if (! Schema::hasColumn('statement_lines', 'status')) {
                $table->string('status')->default('issued')->after('amount');
            }
        });

        // FeeType: đánh dấu phí ưu tiên (điện/xe) — nợ thì BQL cắt dịch vụ; thứ tự trừ tiền.
        Schema::table('fee_types', function (Blueprint $table) {
            if (! Schema::hasColumn('fee_types', 'is_critical')) {
                $table->boolean('is_critical')->default(false)->after('category');
            }
            if (! Schema::hasColumn('fee_types', 'payment_priority')) {
                $table->unsignedSmallInteger('payment_priority')->default(100)->after('is_critical');
            }
            if (! Schema::hasColumn('fee_types', 'enforcement_action')) {
                $table->string('enforcement_action')->nullable()->after('payment_priority');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fee_types', function (Blueprint $table) {
            foreach (['is_critical', 'payment_priority', 'enforcement_action'] as $col) {
                if (Schema::hasColumn('fee_types', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
        // KHÔNG drop fee_type_id trên statement_lines (có thể tồn tại trước migration này).
        Schema::table('statement_lines', function (Blueprint $table) {
            foreach (['paid_amount', 'fee_category', 'status'] as $col) {
                if (Schema::hasColumn('statement_lines', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
        Schema::dropIfExists('apartment_wallet_transactions');
        Schema::dropIfExists('apartment_wallet_buckets');
        Schema::dropIfExists('apartment_wallets');
    }
};
