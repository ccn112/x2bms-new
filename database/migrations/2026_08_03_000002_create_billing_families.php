<?php

use App\Enums\BillingFamily;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * P1c — Catalog NHÃN cho 5 billing family (canonical D10/D11).
 *
 * `App\Enums\BillingFamily` VẪN là nguồn sự thật về MÃ (`code`) + thứ tự phân bổ
 * (`priority`) — không thay bằng bảng. Bảng này chỉ để:
 *   - Tenant đổi NHÃN hiển thị (D10: đổi nhãn, không đổi mã) — cột `name_vi` sau này
 *     có thể override per-tenant (chưa làm ở P1c).
 *   - Làm đích FK cho charge/fee_definition canonical (Phase sau).
 *
 * 5 dòng `system_locked` seed idempotent (upsert theo `code`) từ enum — chạy lại
 * không tạo trùng, không đụng `created_at`.
 */
return new class extends Migration
{
    private const NAME_EN = [
        'management' => 'Management',
        'water' => 'Water',
        'electricity' => 'Electricity',
        'vehicle' => 'Vehicle',
        'other' => 'Other',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('billing_families')) {
            Schema::create('billing_families', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name_vi');
                $table->string('name_en');
                $table->unsignedSmallInteger('priority');
                $table->boolean('requires_subject')->default(false);
                $table->string('subject_kind')->nullable();
                $table->boolean('system_locked')->default(true);
                $table->string('status')->default('active');
                $table->timestamps();
            });
        }

        $now = now();
        $rows = [];
        foreach (BillingFamily::cases() as $f) {
            $rows[] = [
                'code' => $f->value,
                'name_vi' => $f->label(),
                'name_en' => self::NAME_EN[$f->value],
                'priority' => $f->defaultPriority(),
                'requires_subject' => $f->requiresSubject(),
                'subject_kind' => $f->subjectKind(),
                'system_locked' => true,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('billing_families')->upsert(
            $rows,
            ['code'],
            ['name_vi', 'name_en', 'priority', 'requires_subject', 'subject_kind', 'system_locked', 'status', 'updated_at'],
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_families');
    }
};
