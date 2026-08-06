<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_translations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->default(0)->index();
            $table->unsignedBigInteger('project_id')->default(0)->index();
            $table->string('translatable_type', 120);
            $table->string('translatable_id', 64);
            $table->string('field', 64)->default('content');
            $table->string('source_locale', 16);
            $table->string('target_locale', 16);
            $table->string('source_hash', 64);
            $table->longText('translated_value');
            $table->enum('translation_method', ['manual', 'ai', 'import']);
            $table->string('provider', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->decimal('quality_score', 5, 4)->nullable();
            $table->string('status', 32)->default('draft')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(
                [
                    'tenant_id',
                    'project_id',
                    'translatable_type',
                    'translatable_id',
                    'field',
                    'target_locale',
                    'source_hash',
                ],
                'content_translations_source_unique'
            );
            $table->index(
                ['translatable_type', 'translatable_id', 'field', 'target_locale', 'status'],
                'content_translations_lookup_idx'
            );
        });

        Schema::create('translation_jobs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->default(0)->index();
            $table->unsignedBigInteger('project_id')->default(0)->index();
            $table->string('translatable_type', 120);
            $table->string('translatable_id', 64);
            $table->string('field', 64)->default('content');
            $table->string('source_locale', 16);
            $table->string('target_locale', 16);
            $table->string('source_hash', 64);
            $table->longText('source_value');
            $table->string('provider', 100)->nullable();
            $table->enum('status', ['queued', 'processing', 'completed', 'failed', 'cancelled'])
                ->default('queued')
                ->index();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(
                [
                    'tenant_id',
                    'project_id',
                    'translatable_type',
                    'translatable_id',
                    'field',
                    'target_locale',
                    'source_hash',
                ],
                'translation_jobs_dedupe_unique'
            );
        });

        Schema::create('translation_feedback', function (Blueprint $table): void {
            $table->id();
            $table->uuid('content_translation_id');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('rating', ['helpful', 'not_helpful', 'incorrect']);
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->foreign('content_translation_id')
                ->references('id')
                ->on('content_translations')
                ->cascadeOnDelete();
        });

        Schema::create('translation_usage_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(0)->index();
            $table->unsignedBigInteger('project_id')->default(0)->index();
            $table->string('provider', 100);
            $table->string('model', 100)->nullable();
            $table->string('source_locale', 16);
            $table->string('target_locale', 16);
            $table->unsignedInteger('input_characters')->default(0);
            $table->unsignedInteger('output_characters')->default(0);
            $table->decimal('estimated_cost', 18, 6)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->boolean('success')->default(true);
            $table->string('request_id', 100)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translation_usage_logs');
        Schema::dropIfExists('translation_feedback');
        Schema::dropIfExists('translation_jobs');
        Schema::dropIfExists('content_translations');
    }
};
