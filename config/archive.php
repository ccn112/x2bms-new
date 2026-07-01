<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Log archiving
    |--------------------------------------------------------------------------
    |
    | Append-only log/audit tables grow without bound. The `logs:archive`
    | command moves rows older than `days` out of the hot table into its
    | `<table>_archive` clone (created by the 2026_07_01_000025 migration).
    | Data stays queryable in the archive table; the hot table stays lean.
    |
    | `column` is the timestamp used to decide age (default: created_at).
    | Set `days` to 0 to skip a table without removing its config entry.
    |
    */

    'batch_size' => (int) env('ARCHIVE_BATCH_SIZE', 2000),

    'retention' => [
        'ai_usage_logs' => ['days' => 180],
        'ai_retrieval_logs' => ['days' => 180],
        'ai_requests' => ['days' => 180],
        'audit_logs' => ['days' => 365],
        'billing_audit_logs' => ['days' => 365],
        'activity_log' => ['days' => 180],
        'notification_delivery_logs' => ['days' => 90],
        'notification_reads' => ['days' => 90],
        'access_logs' => ['days' => 90],
        'sensor_events' => ['days' => 60],
        'intercom_events' => ['days' => 90],
        'energy_readings' => ['days' => 180],
        'meter_readings' => ['days' => 365],
        'usage_records' => ['days' => 365],
        'sla_events' => ['days' => 180],
        'alert_actions' => ['days' => 180],
    ],

];
