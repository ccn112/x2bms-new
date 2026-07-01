<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Addendum — Platform content/CMS + thư viện dự án public:
 * platform_content_categories, platform_contents, public_projects, project_media,
 * tenant_project_links. Nội dung platform-global (publish_scope điều khiển phạm vi).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_content_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('type')->default('news'); // news|announcement|banner|project|guide|policy
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('platform_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('platform_content_categories')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->nullable();
            $table->string('summary')->nullable();
            $table->longText('body')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('content_type')->default('news'); // news|announcement|banner|guide|public_project
            $table->string('publish_scope')->default('platform'); // platform|tenant|company|project|building|public
            $table->string('status')->default('draft'); // draft|pending_review|published|archived
            $table->string('language')->default('vi');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->index(['content_type', 'status']);
        });

        Schema::create('public_projects', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('developer_name')->nullable();
            $table->string('address')->nullable();
            $table->string('province')->nullable();
            $table->string('project_type')->nullable();
            $table->string('status')->default('operating'); // planning|selling|handover|operating|archived
            $table->unsignedInteger('blocks')->default(0);
            $table->unsignedInteger('apartments')->default(0);
            $table->json('amenities_json')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(true);
            $table->json('metadata_json')->nullable();
            $table->timestamps();
        });

        Schema::create('project_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('public_project_id')->constrained()->cascadeOnDelete();
            $table->string('media_type')->default('image'); // image|video|brochure|map|floor_plan
            $table->string('title')->nullable();
            $table->string('file_url');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tenant_project_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('public_project_id')->nullable()->constrained()->nullOnDelete();
            $table->json('override_content_json')->nullable();
            $table->foreignId('linked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('linked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_project_links');
        Schema::dropIfExists('project_media');
        Schema::dropIfExists('public_projects');
        Schema::dropIfExists('platform_contents');
        Schema::dropIfExists('platform_content_categories');
    }
};
