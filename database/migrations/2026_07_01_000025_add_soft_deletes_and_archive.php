<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add-only migration (project rule: never edit a migration that has run).
 *
 *  1. Adds `deleted_at` (soft deletes) to every business table EXCEPT framework
 *     tables, append-only logs/ledgers and pure pivots (the DENY set below).
 *  2. Rebuilds the four business unique indexes as composite `[col, deleted_at]`
 *     so a soft-deleted row no longer blocks re-creating the same natural key
 *     (MySQL/MariaDB treat NULL as distinct → one live row per key, N trashed).
 *  3. Creates `*_archive` clones (CREATE TABLE … LIKE) for the bloat-prone logs;
 *     rows are moved there by `logs:archive` (see App\Console\Commands\ArchiveStaleLogs).
 */
return new class extends Migration
{
    /** Tables that must NOT get soft deletes: framework, logs/append-only, pure pivots. */
    private array $deny = [
        // framework / system
        'migrations', 'cache', 'cache_locks', 'sessions', 'jobs', 'job_batches',
        'failed_jobs', 'password_reset_tokens', 'personal_access_tokens', 'media',
        'permissions', 'roles', 'model_has_permissions', 'model_has_roles',
        'role_has_permissions', 'settings',
        'monitored_scheduled_tasks', 'monitored_scheduled_task_log_items',
        // append-only logs / audit / streams / ledgers
        'activity_log', 'activity_logs', 'audit_logs', 'billing_audit_logs',
        'ai_usage_logs', 'ai_retrieval_logs', 'ai_requests',
        'statement_publish_logs', 'notification_delivery_logs', 'notification_reads',
        'sla_events', 'alert_actions', 'sensor_events', 'intercom_events',
        'energy_readings', 'meter_readings', 'access_logs', 'usage_records',
        'apartment_status_histories', 'feedback_status_histories',
        'fund_transactions', 'pass_through_transactions', 'loyalty_transactions',
        'payment_allocations', 'poll_votes', 'qr_payment_tokens', 'booking_qr_passes',
        'emergency_alerts', 'reconciliation_matches',
        'bank_transactions', 'bank_statement_imports', 'import_jobs', 'export_jobs',
        // pure many-to-many pivots
        'resident_apartment_relations', 'fee_scope_assignments', 'tenant_project_links',
        'plan_features', 'tenant_entitlements', 'tenant_module_overrides', 'tenant_modules',
        'knowledge_scopes', 'notification_audiences', 'notification_channels',
        'tenant_partner_assignments', 'knowledge_article_shares', 'document_template_shares',
        'event_registrations', 'poll_options', 'contract_acceptances',
        'work_order_assignments', 'feedback_assignments',
    ];

    /** Composite-unique rebuilds: table => [indexName, columns...]. */
    private array $uniqueRebuilds = [
        'buildings' => ['buildings_code_unique', 'code'],
        'projects' => ['projects_code_unique', 'code'],
        'tenants' => ['tenants_code_unique', 'code'],
        'users' => ['users_email_unique', 'email'],
    ];

    /** Bloat-prone logs to clone into `*_archive`. */
    private array $archive = [
        'ai_usage_logs', 'ai_retrieval_logs', 'ai_requests', 'audit_logs',
        'activity_log', 'notification_delivery_logs', 'notification_reads',
        'access_logs', 'sensor_events', 'intercom_events', 'energy_readings',
        'meter_readings', 'usage_records', 'sla_events', 'alert_actions',
        'billing_audit_logs',
    ];

    public function up(): void
    {
        // 1. soft deletes on the INCLUDE set
        foreach ($this->includeTables() as $table) {
            if (! Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, fn (Blueprint $t) => $t->softDeletes());
            }
        }

        // 2. composite uniques (drop single-column unique, add [col, deleted_at])
        foreach ($this->uniqueRebuilds as $table => [$indexName, $column]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'deleted_at')) {
                continue;
            }
            Schema::table($table, function (Blueprint $t) use ($indexName, $column) {
                try {
                    $t->dropUnique($indexName);
                } catch (\Throwable $e) {
                    // index already renamed/absent — ignore
                }
                $t->unique([$column, 'deleted_at']);
            });
        }

        // 3. archive clones (MySQL keeps indexes via LIKE; other drivers use AS SELECT)
        $driver = DB::connection()->getDriverName();
        foreach ($this->archive as $table) {
            if (Schema::hasTable($table) && ! Schema::hasTable($table.'_archive')) {
                if (in_array($driver, ['mysql', 'mariadb'], true)) {
                    DB::statement("CREATE TABLE `{$table}_archive` LIKE `{$table}`");
                } else {
                    DB::statement("CREATE TABLE \"{$table}_archive\" AS SELECT * FROM \"{$table}\" WHERE 1 = 0");
                }
            }
        }
    }

    public function down(): void
    {
        foreach ($this->archive as $table) {
            Schema::dropIfExists($table.'_archive');
        }

        foreach ($this->uniqueRebuilds as $table => [$indexName, $column]) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function (Blueprint $t) use ($indexName, $column) {
                try {
                    $t->dropUnique([$column, 'deleted_at']);
                } catch (\Throwable $e) {
                }
                $t->unique($column, $indexName);
            });
        }

        foreach ($this->includeTables() as $table) {
            if (Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, fn (Blueprint $t) => $t->dropSoftDeletes());
            }
        }
    }

    /** @return array<int, string> base tables that should receive soft deletes */
    private function includeTables(): array
    {
        // Schema::getTableListing() may return schema-qualified names (e.g. "db.table")
        // on Laravel 13 / MySQL — strip the prefix so the deny match works.
        $tables = array_map(
            fn (string $t) => str_contains($t, '.') ? substr($t, strrpos($t, '.') + 1) : $t,
            Schema::getTableListing(),
        );

        return array_values(array_filter(
            $tables,
            fn (string $table) => ! in_array($table, $this->deny, true)
                && ! str_ends_with($table, '_archive'),
        ));
    }
};
