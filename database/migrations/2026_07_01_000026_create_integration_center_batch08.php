<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Batch 08 — Integration Center, API Key & Webhook (platform-level, canonical).
 *
 * Reconcile: DROP the primitive per-tenant `integration_connections` (migration
 * 000012) and rebuild it as the platform Integration Center schema (18 tables),
 * mirroring how Batch 07 replaced the stub SaaS tables. Add-only migration.
 *
 * Platform tables carry NO tenant scope (except audit/events which reference a
 * tenant nullably). Secrets are never stored in clear: `encrypted_payload` is
 * Crypt-encrypted, API-key/webhook secrets keep only a hash + masked summary.
 */
return new class extends Migration
{
    /** High-volume logs that also get an *_archive clone (config/archive.php). */
    private array $archive = [
        'integration_events',
        'webhook_delivery_attempts',
        'integration_connection_checks',
        'integration_audit_logs',
    ];

    public function up(): void
    {
        // --- reconcile: remove the old primitive per-tenant table ---
        Schema::dropIfExists('integration_connections');

        Schema::create('integration_categories', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('name');
            $t->string('description')->nullable();
            $t->boolean('is_active')->default(true);
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
        });

        Schema::create('integration_connections', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('name');
            $t->foreignId('category_id')->nullable()->constrained('integration_categories')->nullOnDelete();
            $t->string('provider_code')->nullable();
            $t->string('environment')->default('production'); // sandbox|staging|production
            $t->string('status')->default('active');          // active|warning|disabled|incident|archived
            $t->string('api_version')->nullable();
            $t->string('base_url')->nullable();
            $t->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->unsignedInteger('timeout_seconds')->default(30);
            $t->string('retry_policy')->nullable();            // fixed_3_attempts|exponential_5_attempts|...
            $t->boolean('idempotency_enabled')->default(true);
            $t->timestamp('last_checked_at')->nullable();
            $t->decimal('success_rate_24h', 5, 2)->nullable();
            $t->unsignedInteger('avg_latency_ms')->nullable();
            $t->string('sla_status')->default('healthy');      // healthy|warning|incident
            $t->json('metadata_json')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('integration_credentials', function (Blueprint $t) {
            $t->id();
            $t->foreignId('connection_id')->constrained('integration_connections')->cascadeOnDelete();
            $t->string('credential_type');                     // api_key|oauth|smtp|hmac|...
            $t->text('encrypted_payload')->nullable();         // Crypt::encryptString
            $t->string('masked_summary')->nullable();          // e.g. sk-…7f9c
            $t->string('status')->default('valid');            // valid|expiring|expired|revoked|rotated|compromised
            $t->timestamp('expires_at')->nullable();
            $t->timestamp('rotated_at')->nullable();
            $t->foreignId('rotated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('integration_connection_checks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('connection_id')->constrained('integration_connections')->cascadeOnDelete();
            $t->string('status');                              // success|warning|failed
            $t->unsignedInteger('latency_ms')->nullable();
            $t->unsignedInteger('http_status')->nullable();
            $t->string('message')->nullable();
            $t->timestamp('checked_at')->nullable();
            $t->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('created_at')->nullable();
        });

        Schema::create('integration_mappings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('connection_id')->constrained('integration_connections')->cascadeOnDelete();
            $t->string('mapping_type');                        // event|field|status
            $t->string('source_event')->nullable();
            $t->string('target_event')->nullable();
            $t->json('mapping_json')->nullable();
            $t->unsignedInteger('version')->default(1);
            $t->string('status')->default('active');
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('integration_api_keys', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('client_id')->unique();
            $t->string('secret_hash')->nullable();
            $t->string('environment')->default('production');  // sandbox|staging|production
            $t->string('status')->default('draft');            // draft|active|expiring|revoked|expired|suspended
            $t->date('expires_at')->nullable();
            $t->timestamp('last_used_at')->nullable();
            $t->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->unsignedInteger('rate_limit_per_minute')->default(600);
            $t->boolean('require_hmac')->default(false);
            $t->boolean('require_ip_allowlist')->default(false);
            $t->json('allowed_ips_json')->nullable();
            $t->json('metadata_json')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('integration_api_key_scopes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('api_key_id')->constrained('integration_api_keys')->cascadeOnDelete();
            $t->string('scope_code');
            $t->string('scope_name')->nullable();
            $t->string('permission_level')->nullable();        // read|write|manage|full
        });

        Schema::create('integration_api_key_rotations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('api_key_id')->constrained('integration_api_keys')->cascadeOnDelete();
            $t->string('old_secret_hash')->nullable();
            $t->string('new_secret_hash')->nullable();
            $t->foreignId('rotated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('rotated_at')->nullable();
            $t->string('reason')->nullable();
            $t->timestamp('created_at')->nullable();
        });

        Schema::create('webhook_event_groups', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('name');
            $t->string('description')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('webhook_endpoints', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('endpoint_name');
            $t->string('url');
            $t->foreignId('event_group_id')->nullable()->constrained('webhook_event_groups')->nullOnDelete();
            $t->string('method')->default('POST');
            $t->string('signature_type')->default('HMAC');     // HMAC|none
            $t->string('signing_secret_hash')->nullable();
            $t->string('status')->default('pending_verification'); // active|warning|disabled|failed|pending_verification
            $t->decimal('success_rate', 5, 2)->nullable();
            $t->string('retry_policy')->nullable();
            $t->string('owner_name')->nullable();
            $t->timestamp('last_delivery_at')->nullable();
            $t->json('metadata_json')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('webhook_delivery_attempts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('webhook_endpoint_id')->constrained('webhook_endpoints')->cascadeOnDelete();
            $t->string('event_id')->nullable();                // ties to integration_events.event_id
            $t->string('correlation_id')->nullable();
            $t->string('payload_hash')->nullable();
            $t->unsignedInteger('http_status')->nullable();
            $t->unsignedInteger('duration_ms')->nullable();
            $t->string('status');                              // success|failed|pending
            $t->unsignedInteger('attempt_no')->default(1);
            $t->longText('response_body')->nullable();
            $t->text('error_message')->nullable();
            $t->timestamp('delivered_at')->nullable();
            $t->timestamp('created_at')->nullable();
            $t->index(['webhook_endpoint_id', 'status']);
        });

        Schema::create('integration_events', function (Blueprint $t) {
            $t->id();
            $t->string('event_id')->unique();                  // evt_… ULID
            $t->string('correlation_id')->nullable();
            $t->string('source');
            $t->string('event_type');
            $t->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $t->string('status')->default('success');          // success|failed|warning|pending
            $t->unsignedInteger('duration_ms')->nullable();
            $t->unsignedInteger('retry_count')->default(0);
            $t->string('payload_hash')->nullable();
            $t->text('message')->nullable();
            $t->timestamp('created_at')->nullable();
            $t->index(['source', 'status']);
            $t->index('correlation_id');
        });

        Schema::create('integration_retry_jobs', function (Blueprint $t) {
            $t->id();
            $t->string('event_id')->nullable();
            $t->foreignId('webhook_endpoint_id')->nullable()->constrained('webhook_endpoints')->nullOnDelete();
            $t->string('source')->nullable();
            $t->string('reason')->nullable();
            $t->string('status')->default('pending');          // pending|retrying|succeeded|failed|skipped|dead_letter
            $t->unsignedInteger('attempt_no')->default(0);
            $t->unsignedInteger('max_attempts')->default(5);
            $t->timestamp('next_retry_at')->nullable();
            $t->text('last_error')->nullable();
            $t->timestamps();
        });

        Schema::create('integration_incidents', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('title');
            $t->string('severity')->default('medium');         // low|medium|high|critical
            $t->string('status')->default('open');             // open|investigating|resolved
            $t->string('source')->nullable();
            $t->timestamp('started_at')->nullable();
            $t->timestamp('resolved_at')->nullable();
            $t->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->text('summary')->nullable();
            $t->text('root_cause')->nullable();
            $t->timestamps();
        });

        Schema::create('integration_security_policies', function (Blueprint $t) {
            $t->id();
            $t->string('policy_key')->unique();
            $t->json('policy_value_json')->nullable();
            $t->boolean('is_enabled')->default(true);
            $t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('integration_ip_allowlists', function (Blueprint $t) {
            $t->id();
            $t->string('scope_type')->default('global');       // global|api_key|connection|webhook
            $t->unsignedBigInteger('scope_id')->nullable();
            $t->string('ip_or_cidr');
            $t->string('description')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('integration_rate_limits', function (Blueprint $t) {
            $t->id();
            $t->string('scope_type')->default('global');       // global|api_key|connection
            $t->unsignedBigInteger('scope_id')->nullable();
            $t->unsignedInteger('limit_per_minute')->default(1000);
            $t->unsignedInteger('burst_limit')->nullable();
            $t->unsignedInteger('window_seconds')->default(60);
            $t->boolean('is_enabled')->default(true);
            $t->timestamps();
        });

        Schema::create('integration_audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $t->foreignId('connection_id')->nullable()->constrained('integration_connections')->nullOnDelete();
            $t->string('entity_type');
            $t->string('entity_id')->nullable();
            $t->string('action');
            $t->json('before_json')->nullable();
            $t->json('after_json')->nullable();
            $t->string('reason')->nullable();
            $t->string('ip_address')->nullable();
            $t->string('user_agent')->nullable();
            $t->timestamp('created_at')->nullable();
            $t->index(['entity_type', 'entity_id']);
        });

        // --- archive clones for the high-volume logs ---
        foreach ($this->archive as $table) {
            if (! Schema::hasTable($table.'_archive')) {
                $this->cloneTable($table, $table.'_archive');
            }
        }
    }

    /** Create an empty structural clone; MySQL keeps indexes via LIKE, others use AS SELECT. */
    private function cloneTable(string $from, string $to): void
    {
        $driver = DB::connection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("CREATE TABLE `{$to}` LIKE `{$from}`");
        } else {
            DB::statement("CREATE TABLE \"{$to}\" AS SELECT * FROM \"{$from}\" WHERE 1 = 0");
        }
    }

    public function down(): void
    {
        foreach ($this->archive as $table) {
            Schema::dropIfExists($table.'_archive');
        }

        foreach ([
            'integration_audit_logs', 'integration_rate_limits', 'integration_ip_allowlists',
            'integration_security_policies', 'integration_incidents', 'integration_retry_jobs',
            'integration_events', 'webhook_delivery_attempts', 'webhook_endpoints',
            'webhook_event_groups', 'integration_api_key_rotations', 'integration_api_key_scopes',
            'integration_api_keys', 'integration_mappings', 'integration_connection_checks',
            'integration_credentials', 'integration_connections', 'integration_categories',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        // recreate the old primitive table so a rollback is consistent
        Schema::create('integration_connections', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->string('provider');
            $t->string('name');
            $t->string('status')->default('connected');
            $t->json('config')->nullable();
            $t->timestamp('last_sync_at')->nullable();
            $t->timestamps();
        });
    }
};
