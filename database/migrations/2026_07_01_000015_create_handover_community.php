<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tier 5 — Bàn giao/bảo hành + cộng đồng: handover_batches(+units,+checklists,+punch_items),
 * warranty_requests, community_groups/community_posts, events(+registrations), polls(+options,+votes).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('handover_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('building_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->nullable();
            $table->string('name');
            $table->date('scheduled_date')->nullable();
            $table->unsignedInteger('total_units')->default(0);
            $table->string('status')->default('planned'); // planned|in_progress|completed
            $table->timestamps();
        });

        Schema::create('handover_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('handover_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('apartment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('resident_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('scheduled'); // scheduled|handed_over|pending_defects
            $table->timestamp('handed_over_at')->nullable();
            $table->timestamps();
        });

        Schema::create('handover_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('handover_unit_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('status')->default('pending'); // pending|passed|failed
            $table->timestamps();
        });

        Schema::create('handover_punch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('handover_checklist_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->boolean('is_ok')->default(true);
            $table->string('severity')->default('minor'); // minor|major
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('warranty_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('apartment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('resident_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->nullable(); // waterproof|electrical|paint|door|other
            $table->string('status')->default('open'); // open|in_progress|resolved|rejected
            $table->timestamp('reported_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('community_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->unsignedInteger('member_count')->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('community_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('community_group_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('author_resident_id')->nullable()->constrained('residents')->nullOnDelete();
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->unsignedInteger('like_count')->default(0);
            $table->unsignedInteger('comment_count')->default(0);
            $table->string('status')->default('published'); // published|hidden|pending
            $table->timestamps();
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->unsignedInteger('registered_count')->default(0);
            $table->string('status')->default('upcoming'); // upcoming|ongoing|finished|cancelled
            $table->timestamps();
        });

        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resident_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('guests')->default(0);
            $table->string('status')->default('registered'); // registered|attended|cancelled
            $table->timestamps();
        });

        Schema::create('polls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('question');
            $table->string('type')->default('single'); // single|multiple
            $table->string('status')->default('open'); // draft|open|closed
            $table->timestamp('closes_at')->nullable();
            $table->unsignedInteger('vote_count')->default(0);
            $table->timestamps();
        });

        Schema::create('poll_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->unsignedInteger('vote_count')->default(0);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('poll_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_id')->constrained()->cascadeOnDelete();
            $table->foreignId('poll_option_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resident_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poll_votes');
        Schema::dropIfExists('poll_options');
        Schema::dropIfExists('polls');
        Schema::dropIfExists('event_registrations');
        Schema::dropIfExists('events');
        Schema::dropIfExists('community_posts');
        Schema::dropIfExists('community_groups');
        Schema::dropIfExists('warranty_requests');
        Schema::dropIfExists('handover_punch_items');
        Schema::dropIfExists('handover_checklists');
        Schema::dropIfExists('handover_units');
        Schema::dropIfExists('handover_batches');
    }
};
