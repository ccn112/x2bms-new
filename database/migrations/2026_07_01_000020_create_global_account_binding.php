<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Addendum — Tài khoản gốc toàn hệ thống + luồng gắn cư dân:
 * global_user_accounts (registry), resident_binding_requests (yêu cầu gắn căn),
 * resident_unit_bindings (liên kết đã duyệt). Bổ trợ cho users/residents hiện có.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('global_user_accounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('full_name')->nullable();
            $table->string('avatar_url')->nullable();
            $table->string('identity_status')->default('unverified'); // unverified|phone_verified|email_verified|verified
            $table->string('account_status')->default('active');      // active|suspended|deleted
            $table->string('account_type')->default('public_user');   // public_user|resident|employee|contractor|vendor|platform_admin
            $table->timestamp('first_registered_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->unsignedInteger('risk_score')->default(0);
            $table->string('duplicate_group_id')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->index('phone');
            $table->index('duplicate_group_id');
        });

        Schema::create('resident_binding_requests', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->foreignId('user_account_id')->constrained('global_user_accounts')->cascadeOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('building_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('apartment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('requested_role')->default('owner'); // owner|tenant|family_member|guest
            $table->json('evidence_files_json')->nullable();
            $table->string('status')->default('pending'); // pending|approved|rejected|need_more_info|cancelled
            $table->timestamp('requested_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_note')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        Schema::create('resident_unit_bindings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_account_id')->constrained('global_user_accounts')->cascadeOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('building_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('apartment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role')->default('owner'); // owner|tenant|family_member|guest
            $table->string('status')->default('active'); // active|inactive|revoked
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->foreignId('approved_request_id')->nullable()->constrained('resident_binding_requests')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resident_unit_bindings');
        Schema::dropIfExists('resident_binding_requests');
        Schema::dropIfExists('global_user_accounts');
    }
};
