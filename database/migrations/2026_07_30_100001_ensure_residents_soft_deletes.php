<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bù cho `add_soft_deletes_and_archive` dưới SQLite (DB test, `phpunit.xml`).
 *
 * Migration đó dò bảng bằng `Schema::getTableListing()` rồi thêm `deleted_at`
 * cho mọi bảng NGOÀI danh sách deny — chạy đúng trên MySQL (dev/production đã
 * có đủ cột, kể cả `residents`/`apartments`). Dưới SQLite, driver liệt kê bảng
 * khác đi nên một số bảng bị bỏ sót cột này, dù model đã khai `SoftDeletes`.
 * Không Feature test nào trước đây chạm các bảng đó nên lỗ hổng chưa lộ; viết
 * test cho tin rao (chạm `Resident`/`Apartment` qua `ResidentContextService`)
 * là lần đầu lộ ra.
 *
 * Add-only + có điều kiện `hasColumn` → no-op hoàn toàn trên MySQL, chỉ bù cột
 * còn thiếu ở SQLite. Không sửa migration cũ theo đúng luật "add-only".
 */
return new class extends Migration
{
    /** Y HỆT deny-list của `add_soft_deletes_and_archive` — không tự bịa thêm bảng. */
    private array $deny = [
        'migrations', 'cache', 'cache_locks', 'sessions', 'jobs', 'job_batches',
        'failed_jobs', 'password_reset_tokens', 'personal_access_tokens', 'media',
        'permissions', 'roles', 'model_has_permissions', 'model_has_roles',
        'role_has_permissions', 'settings',
        'monitored_scheduled_tasks', 'monitored_scheduled_task_log_items',
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
        'resident_apartment_relations', 'fee_scope_assignments', 'tenant_project_links',
        'plan_features', 'tenant_entitlements', 'tenant_module_overrides', 'tenant_modules',
        'knowledge_scopes', 'notification_audiences', 'notification_channels',
        'tenant_partner_assignments', 'knowledge_article_shares', 'document_template_shares',
        'event_registrations', 'poll_options', 'contract_acceptances',
        'work_order_assignments', 'feedback_assignments',
    ];

    public function up(): void
    {
        $driver = DB::connection()->getDriverName();
        $database = DB::connection()->getDatabaseName();

        foreach (Schema::getTableListing() as $t) {
            if (str_contains($t, '.')) {
                [$schema, $name] = explode('.', $t, 2);
                // SQLite qualifies with the ATTACHED SCHEMA name ("main"), not
                // the database name — so the MySQL-style `$schema !== $database`
                // filter here always skips every table under SQLite (this is
                // exactly why the original migration silently no-op'd there).
                // MySQL keeps the real cross-database check.
                if ($driver !== 'sqlite' && $schema !== $database) {
                    continue;
                }
                $t = $name;
            }
            if (in_array($t, $this->deny, true) || str_ends_with($t, '_archive')) {
                continue;
            }
            if (! Schema::hasColumn($t, 'deleted_at')) {
                Schema::table($t, fn (Blueprint $b) => $b->softDeletes());
            }
        }
    }

    public function down(): void
    {
        // Không rollback: phần lớn các bảng có thể đang chạy trên MySQL nơi
        // cột đã tồn tại từ rất lâu trước migration này — rollback vô điều
        // kiện sẽ xoá nhầm cột đó ở môi trường thật.
    }
};
