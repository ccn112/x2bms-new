<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BQL-NOTI content_type=event — mở rộng ADDITIVE `events`/`event_registrations` cho
 * đăng ký/waitlist/check-in/guest/fee (spec 04). GIỮ `events.status` chuẩn hoá
 * (upcoming|ongoing|finished|cancelled — mig ...normalize_event_status); trạng thái
 * đăng ký tách sang `registration_status` để không churn cột đã chuẩn hoá.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $t) {
            foreach ([
                'registration_status' => fn () => $t->string('registration_status')->default('open'), // open|closed|full
                'registration_deadline' => fn () => $t->timestamp('registration_deadline')->nullable(),
                'allow_guests' => fn () => $t->boolean('allow_guests')->default(false),
                'max_guests' => fn () => $t->unsignedInteger('max_guests')->default(0),
                'fee_amount' => fn () => $t->decimal('fee_amount', 12, 2)->default(0),
                'qr_checkin' => fn () => $t->boolean('qr_checkin')->default(false),
                'waitlist_count' => fn () => $t->unsignedInteger('waitlist_count')->default(0),
                'checked_in_count' => fn () => $t->unsignedInteger('checked_in_count')->default(0),
                'cancel_reason' => fn () => $t->string('cancel_reason')->nullable(),
            ] as $col => $add) {
                if (! Schema::hasColumn('events', $col)) {
                    $add();
                }
            }
        });

        Schema::table('event_registrations', function (Blueprint $t) {
            // status mở rộng giá trị (cột string, không enum DB): registered|waitlisted|
            // cancelled|checked_in|no_show.
            if (! Schema::hasColumn('event_registrations', 'checked_in_at')) {
                $t->timestamp('checked_in_at')->nullable();
            }
            if (! Schema::hasColumn('event_registrations', 'waitlisted_at')) {
                $t->timestamp('waitlisted_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $t) {
            $t->dropColumn(['registration_status', 'registration_deadline', 'allow_guests', 'max_guests',
                'fee_amount', 'qr_checkin', 'waitlist_count', 'checked_in_count', 'cancel_reason']);
        });
        Schema::table('event_registrations', function (Blueprint $t) {
            $t->dropColumn(['checked_in_at', 'waitlisted_at']);
        });
    }
};
