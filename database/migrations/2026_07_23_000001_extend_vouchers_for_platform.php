<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Voucher 2 tầng sở hữu (mirror Notification): platform (SA đi hợp tác đối tác ngoài) | tenant.
 * Platform voucher tenant_id = NULL, triển khai xuống tenant qua pivot `voucher_tenant` CÓ KỲ HẠN.
 * Cư dân thấy voucher platform CHỈ khi tenant mình có rollout đang trong kỳ. ADD-ONLY.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            if (! Schema::hasColumn('vouchers', 'owner_level')) {
                $table->string('owner_level')->default('tenant')->after('tenant_id'); // platform|tenant
            }
        });

        // tenant_id nullable để chứa voucher platform (không thuộc tenant nào).
        Schema::table('vouchers', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->change();
        });

        if (! Schema::hasTable('voucher_tenant')) {
            Schema::create('voucher_tenant', function (Blueprint $table) {
                $table->id();
                $table->foreignId('voucher_id')->constrained()->cascadeOnDelete();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->string('status')->default('active'); // active|paused|ended
                $table->timestamps();

                $table->unique(['voucher_id', 'tenant_id']);
                $table->index(['tenant_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_tenant');
        Schema::table('vouchers', function (Blueprint $table) {
            if (Schema::hasColumn('vouchers', 'owner_level')) {
                $table->dropColumn('owner_level');
            }
        });
    }
};
