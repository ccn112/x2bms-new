<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Addendum — Thư viện mẫu tài liệu 3 cấp: document_template_categories,
 * document_templates(+shares,+clones). owner_scope platform|tenant|company|project|building;
 * share_mode view_only|use_as_template|clone_allowed|force_apply (giống KB inheritance).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_template_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('document_template_categories')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('category_id')->nullable()->constrained('document_template_categories')->nullOnDelete();
            $table->string('title');
            $table->string('description')->nullable();
            $table->string('template_type')->default('notice'); // notice|sop|policy|contract|checklist|form|fee_template|pccc|maintenance
            $table->string('owner_scope')->default('platform'); // platform|tenant|company|project|building
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->string('status')->default('active'); // draft|active|deprecated|archived
            $table->string('file_url')->nullable();
            $table->longText('body_markdown')->nullable();
            $table->json('variables_json')->nullable();
            $table->boolean('ai_readable')->default(true);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['owner_scope', 'status']);
        });

        Schema::create('document_template_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('document_templates')->cascadeOnDelete();
            $table->string('from_scope');
            $table->unsignedBigInteger('from_owner_id')->nullable();
            $table->string('to_scope');
            $table->unsignedBigInteger('to_owner_id')->nullable();
            $table->string('share_mode')->default('view_only'); // view_only|use_as_template|clone_allowed|force_apply
            $table->boolean('can_ai_read')->default(true);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('document_template_clones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_template_id')->constrained('document_templates')->cascadeOnDelete();
            $table->foreignId('cloned_template_id')->nullable()->constrained('document_templates')->nullOnDelete();
            $table->foreignId('cloned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cloned_at')->nullable();
            $table->string('clone_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_template_clones');
        Schema::dropIfExists('document_template_shares');
        Schema::dropIfExists('document_templates');
        Schema::dropIfExists('document_template_categories');
    }
};
