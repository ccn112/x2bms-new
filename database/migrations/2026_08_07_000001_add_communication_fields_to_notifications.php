<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * BQL Communication (BQL-NOTI) — mở rộng ADDITIVE cột chiến dịch trên `notifications`
 * (canonical campaign, ADR-002). KHÔNG đụng `status` (cổng cư dân) hay `category`
 * (đã có từ mig ...000008 = business category). Legacy rows backfill an toàn.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $t) {
            if (! Schema::hasColumn('notifications', 'content_type')) {
                $t->string('content_type')->default('announcement')->after('type'); // announcement|news|event|poll
            }
            if (! Schema::hasColumn('notifications', 'workflow_status')) {
                $t->string('workflow_status')->default('draft')->after('status');    // máy trạng thái campaign
            }
            if (! Schema::hasColumn('notifications', 'allow_feedback')) {
                $t->boolean('allow_feedback')->default(false)->after('requires_ack');
            }
            if (! Schema::hasColumn('notifications', 'cta_label')) {
                $t->string('cta_label')->nullable()->after('allow_feedback');
            }
            if (! Schema::hasColumn('notifications', 'cta_target')) {
                $t->string('cta_target')->nullable()->after('cta_label');
            }
            if (! Schema::hasColumn('notifications', 'content_meta')) {
                $t->json('content_meta')->nullable()->after('body'); // subtype fields (news: author/featured/slug/visibility)
            }
            if (! Schema::hasColumn('notifications', 'audience_rule')) {
                $t->json('audience_rule')->nullable()->after('content_meta'); // DSL JSON (spec 07)
            }
            if (! Schema::hasColumn('notifications', 'audience_locked')) {
                $t->boolean('audience_locked')->default(false)->after('audience_rule');
            }
            if (! Schema::hasColumn('notifications', 'audience_snapshot_hash')) {
                $t->string('audience_snapshot_hash')->nullable()->after('audience_locked');
            }
            if (! Schema::hasColumn('notifications', 'send_strategy')) {
                $t->string('send_strategy')->default('parallel')->after('audience_snapshot_hash');
            }
            if (! Schema::hasColumn('notifications', 'approval_route_key')) {
                $t->string('approval_route_key')->nullable()->after('send_strategy');
            }
            if (! Schema::hasColumn('notifications', 'snapshot_version')) {
                $t->unsignedInteger('snapshot_version')->default(0)->after('approval_route_key');
            }
            if (! Schema::hasColumn('notifications', 'sent_at')) {
                $t->timestamp('sent_at')->nullable()->after('published_at');
            }
            if (! Schema::hasColumn('notifications', 'completed_at')) {
                $t->timestamp('completed_at')->nullable()->after('sent_at');
            }
            if (! Schema::hasColumn('notifications', 'cost_estimate')) {
                $t->decimal('cost_estimate', 12, 2)->default(0)->after('completed_at');
            }
            if (! Schema::hasColumn('notifications', 'cost_actual')) {
                $t->decimal('cost_actual', 12, 2)->default(0)->after('cost_estimate');
            }
        });

        Schema::table('notifications', function (Blueprint $t) {
            $t->index(['content_type', 'workflow_status'], 'notifications_content_workflow_idx');
        });

        // Backfill legacy rows: content_type=announcement; workflow_status suy từ status.
        DB::table('notifications')->whereNull('content_type')->update(['content_type' => 'announcement']);
        DB::table('notifications')->where('status', 'draft')->update(['workflow_status' => 'draft']);
        DB::table('notifications')->where('status', 'scheduled')->update(['workflow_status' => 'scheduled']);
        DB::table('notifications')->where('status', 'published')->update(['workflow_status' => 'completed']);
        DB::table('notifications')->where('status', 'archived')->update(['workflow_status' => 'cancelled']);
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $t) {
            $t->dropIndex('notifications_content_workflow_idx');
            $t->dropColumn([
                'content_type', 'workflow_status', 'allow_feedback', 'cta_label', 'cta_target',
                'content_meta', 'audience_rule', 'audience_locked', 'audience_snapshot_hash',
                'send_strategy', 'approval_route_key', 'snapshot_version', 'sent_at', 'completed_at',
                'cost_estimate', 'cost_actual',
            ]);
        });
    }
};
