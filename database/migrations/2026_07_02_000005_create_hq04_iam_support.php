<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HQ-04 — Phân quyền & hỗ trợ tập trung.
 *
 * ADD-ONLY. Tái sử dụng: users, spatie roles/permissions, user_role_scopes, audit_logs,
 * support_tickets/support_sla_policies/support_kb_articles (Batch 10). Delta mới: nhóm quyền,
 * cấu hình 2FA, phiên đăng nhập.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('module')->nullable();
            $table->integer('permission_count')->default(0);
            $table->integer('role_count')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('permission_group_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permission_group_id')->constrained()->cascadeOnDelete();
            $table->string('permission_key');
            $table->string('module')->nullable();
            $table->string('action')->nullable();
            $table->timestamps();
        });

        Schema::create('two_factor_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->default(false);
            $table->string('method')->default('app'); // app|sms|email
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('login_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address')->nullable();
            $table->string('device')->nullable();
            $table->string('location')->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();

            $table->index(['tenant_id', 'user_id']);
        });
    }

    public function down(): void
    {
        foreach (['login_sessions', 'two_factor_settings', 'permission_group_items', 'permission_groups'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
