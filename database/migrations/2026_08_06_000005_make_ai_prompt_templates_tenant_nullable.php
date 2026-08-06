<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ai_prompt_templates.tenant_id → nullable.
 *
 * Prompt templates are created at the SuperAdmin /sa panel as PLATFORM-level prompts
 * (owner_scope='platform') by a platform admin, who has no tenant. The column was NOT
 * NULL, so the create hit SQLSTATE 1364. A platform prompt genuinely has no owning
 * tenant → nullable is the correct model (tenant-scoped prompts, if ever added, still
 * set tenant_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_prompt_templates', function (Blueprint $table): void {
            $table->unsignedBigInteger('tenant_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('ai_prompt_templates', function (Blueprint $table): void {
            $table->unsignedBigInteger('tenant_id')->nullable(false)->change();
        });
    }
};
