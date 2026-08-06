<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locales', function (Blueprint $table): void {
            $table->string('code', 16)->primary();
            $table->string('name', 100);
            $table->string('native_name', 100);
            $table->enum('direction', ['ltr', 'rtl'])->default('ltr');
            $table->boolean('enabled')->default(false)->index();
            $table->boolean('is_default')->default(false)->index();
            $table->string('fallback_locale', 16)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(100);
            $table->timestamps();

            $table->foreign('fallback_locale')->references('code')->on('locales')->nullOnDelete();
        });

        Schema::create('translation_namespaces', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 100)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(true);
            $table->timestamps();
        });

        Schema::create('translation_keys', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('namespace_id')->constrained('translation_namespaces')->cascadeOnDelete();
            $table->string('key', 255);
            $table->text('description')->nullable();
            $table->json('placeholders')->nullable();
            $table->boolean('allow_tenant_override')->default(false);
            $table->boolean('is_critical')->default(false);
            $table->timestamps();

            $table->unique(['namespace_id', 'key'], 'translation_keys_namespace_key_unique');
            $table->index('key');
        });

        Schema::create('translation_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('translation_key_id')->constrained('translation_keys')->cascadeOnDelete();
            $table->string('locale', 16);
            $table->enum('scope_type', ['platform', 'product', 'tenant', 'project'])->default('product');
            // Empty string is the global/product sentinel and keeps MySQL uniqueness deterministic.
            $table->string('scope_id', 64)->default('');
            $table->longText('value');
            $table->string('status', 32)->default('draft')->index();
            $table->enum('translation_method', ['manual', 'ai', 'import'])->default('manual');
            $table->string('source_hash', 64)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->foreign('locale')->references('code')->on('locales')->cascadeOnUpdate()->restrictOnDelete();
            $table->unique(
                ['translation_key_id', 'locale', 'scope_type', 'scope_id'],
                'translation_values_key_locale_scope_unique'
            );
            $table->index(['locale', 'status']);
        });

        Schema::create('translation_releases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('namespace_id')->constrained('translation_namespaces')->cascadeOnDelete();
            $table->string('locale', 16);
            $table->string('version', 50);
            $table->string('scope_type', 20)->default('product');
            $table->string('scope_id', 64)->default('');
            $table->string('checksum', 64);
            $table->enum('status', ['draft', 'published', 'rolled_back', 'archived'])->default('draft')->index();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->foreign('locale')->references('code')->on('locales')->cascadeOnUpdate()->restrictOnDelete();
            $table->unique(
                ['namespace_id', 'locale', 'version', 'scope_type', 'scope_id'],
                'translation_releases_identity_unique'
            );
        });

        Schema::create('translation_release_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('release_id')->constrained('translation_releases')->cascadeOnDelete();
            $table->foreignId('translation_key_id')->constrained('translation_keys')->cascadeOnDelete();
            $table->longText('value');
            $table->string('value_checksum', 64);
            $table->timestamps();

            $table->unique(['release_id', 'translation_key_id']);
        });

        Schema::create('translation_glossaries', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 100);
            $table->string('name', 150);
            $table->string('scope_type', 20)->default('product');
            $table->string('scope_id', 64)->default('');
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['code', 'scope_type', 'scope_id']);
        });

        Schema::create('translation_glossary_terms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('glossary_id')->constrained('translation_glossaries')->cascadeOnDelete();
            $table->string('source_locale', 16);
            $table->string('target_locale', 16);
            $table->string('source_term', 255);
            $table->string('target_term', 255);
            $table->text('notes')->nullable();
            $table->boolean('case_sensitive')->default(false);
            $table->boolean('locked')->default(false);
            $table->timestamps();

            $table->unique(
                ['glossary_id', 'source_locale', 'target_locale', 'source_term'],
                'translation_glossary_terms_unique'
            );
        });

        Schema::create('user_locale_preferences', function (Blueprint $table): void {
            $table->foreignId('user_id')->primary()->constrained('users')->cascadeOnDelete();
            $table->string('locale', 16);
            $table->boolean('follow_device')->default(true);
            $table->boolean('auto_translate_content')->default(false);
            $table->json('content_translation_preferences')->nullable();
            $table->timestamps();

            $table->foreign('locale')->references('code')->on('locales')->cascadeOnUpdate()->restrictOnDelete();
        });

        Schema::create('tenant_locale_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('default_locale', 16)->default('vi-VN');
            $table->json('supported_locales');
            $table->boolean('allow_auto_translate')->default(false);
            $table->unsignedBigInteger('monthly_character_limit')->nullable();
            $table->timestamps();

            $table->unique('tenant_id');
            $table->foreign('default_locale')->references('code')->on('locales')->restrictOnDelete();
        });

        Schema::create('project_locale_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('project_id');
            $table->string('default_locale', 16)->nullable();
            $table->json('supported_locales')->nullable();
            $table->boolean('allow_auto_translate')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'project_id']);
            $table->index('project_id');
            $table->foreign('default_locale')->references('code')->on('locales')->nullOnDelete();
        });

        Schema::create('translation_overrides', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('translation_key_id')->constrained('translation_keys')->cascadeOnDelete();
            $table->string('locale', 16);
            $table->enum('scope_type', ['tenant', 'project']);
            $table->string('scope_id', 64);
            $table->longText('value');
            $table->string('status', 32)->default('draft')->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['translation_key_id', 'locale', 'scope_type', 'scope_id'],
                'translation_overrides_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translation_overrides');
        Schema::dropIfExists('project_locale_settings');
        Schema::dropIfExists('tenant_locale_settings');
        Schema::dropIfExists('user_locale_preferences');
        Schema::dropIfExists('translation_glossary_terms');
        Schema::dropIfExists('translation_glossaries');
        Schema::dropIfExists('translation_release_items');
        Schema::dropIfExists('translation_releases');
        Schema::dropIfExists('translation_values');
        Schema::dropIfExists('translation_keys');
        Schema::dropIfExists('translation_namespaces');
        Schema::dropIfExists('locales');
    }
};
