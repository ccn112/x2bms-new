<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RBAC scope foundation (owner-confirmed 3-tier model):
 *   Platform (is_platform_admin)
 *   → Tenant   (Công ty vận hành)  — tenant_id, project_id null
 *   → Project  (Ban quản lý dự án) — tenant_id + project_id
 * Building is a sub-level FILTER, not a scope tier (a BQL manages one project = many tòa).
 *
 * `users.project_id` = the user's home/default project (context default for BQL staff).
 * `user_role_scopes` = source of truth for "this user holds this role within this scope".
 */
return new class extends Migration
{
    public function up(): void
    {
        // BQL staff are scoped to a project; add the missing home-project pointer.
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete();
        });

        Schema::create('user_role_scopes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Spatie roles table (guard 'web'); nullable so a pure data-scope grant is possible.
            $table->foreignId('role_id')->nullable()->constrained('roles')->cascadeOnDelete();
            $table->string('scope_type'); // platform|tenant|project|building
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('building_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'scope_type']);
            $table->index(['tenant_id', 'project_id', 'building_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_role_scopes');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_id');
        });
    }
};
