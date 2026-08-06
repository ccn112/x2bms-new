<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 150)->unique();
            $table->string('category', 80);
            $table->enum('risk', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->json('allowed_variables')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('notification_template_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('template_id')->constrained('notification_templates')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->enum('status', ['draft', 'in_review', 'approved', 'published', 'archived'])
                ->default('draft')
                ->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['template_id', 'version']);
        });

        Schema::create('notification_template_localizations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('template_version_id')
                ->constrained('notification_template_versions')
                ->cascadeOnDelete();
            $table->string('channel', 32);
            $table->string('locale', 16);
            $table->string('title', 255)->nullable();
            $table->longText('body');
            $table->json('channel_options')->nullable();
            $table->string('body_checksum', 64);
            $table->timestamps();

            $table->unique(['template_version_id', 'channel', 'locale'], 'notification_template_l10n_unique');
        });

        Schema::create('notification_delivery_snapshots', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('project_id')->nullable()->index();
            $table->foreignId('recipient_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('template_version_id')
                ->nullable()
                ->constrained('notification_template_versions')
                ->nullOnDelete();
            $table->string('template_code', 150);
            $table->string('channel', 32);
            $table->string('locale', 16);
            $table->string('title', 255)->nullable();
            $table->longText('body');
            $table->json('rendered_variables')->nullable();
            $table->string('status', 32)->default('queued')->index();
            $table->string('provider_message_id', 150)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['recipient_user_id', 'created_at']);
            $table->index(['template_code', 'locale', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_delivery_snapshots');
        Schema::dropIfExists('notification_template_localizations');
        Schema::dropIfExists('notification_template_versions');
        Schema::dropIfExists('notification_templates');
    }
};
