<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tier 4 — Form Builder động: dynamic_forms(+versions,+sections,+fields,+workflows) +
 * form_submissions(+values). Scope tenant/project.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dynamic_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->nullable();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('category')->nullable();
            $table->string('status')->default('draft'); // draft|published|archived
            $table->unsignedInteger('current_version')->default(1);
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('form_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dynamic_form_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->json('schema')->nullable();
            $table->string('status')->default('draft'); // draft|published
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('form_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dynamic_form_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('description')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dynamic_form_id')->constrained()->cascadeOnDelete();
            $table->foreignId('form_section_id')->nullable()->constrained()->nullOnDelete();
            $table->string('key');
            $table->string('label');
            $table->string('type')->default('text'); // text|number|select|date|file|checkbox|textarea|radio
            $table->json('options')->nullable();
            $table->boolean('required')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->json('config')->nullable();
            $table->timestamps();
        });

        Schema::create('form_workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dynamic_form_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('steps')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dynamic_form_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submitted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resident_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('submitted'); // draft|submitted|approved|rejected
            $table->json('data')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('form_submission_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('form_field_id')->nullable()->constrained()->nullOnDelete();
            $table->string('field_key');
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submission_values');
        Schema::dropIfExists('form_submissions');
        Schema::dropIfExists('form_workflows');
        Schema::dropIfExists('form_fields');
        Schema::dropIfExists('form_sections');
        Schema::dropIfExists('form_versions');
        Schema::dropIfExists('dynamic_forms');
    }
};
