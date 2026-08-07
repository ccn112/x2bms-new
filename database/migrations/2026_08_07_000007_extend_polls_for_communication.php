<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * BQL-NOTI content_type=poll — mở rộng ADDITIVE `polls`/`poll_options`/`poll_votes` cho
 * anonymous, vote scope (resident|apartment), đổi phiếu, chọn nhiều, hiển thị kết quả,
 * hẹn mở/đóng (spec 04/06 §5). Vote uniqueness apartment-scope qua unique index MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('polls', function (Blueprint $t) {
            foreach ([
                'summary' => fn () => $t->string('summary')->nullable(),
                'anonymous' => fn () => $t->boolean('anonymous')->default(false),
                'vote_scope' => fn () => $t->string('vote_scope')->default('resident'), // resident|apartment
                'allow_change_vote' => fn () => $t->boolean('allow_change_vote')->default(false),
                'max_choices' => fn () => $t->unsignedInteger('max_choices')->nullable(),
                'result_visibility' => fn () => $t->string('result_visibility')->default('after_vote'), // after_vote|after_close|public_after_close|admin_only
                'opens_at' => fn () => $t->timestamp('opens_at')->nullable(),
            ] as $col => $add) {
                if (! Schema::hasColumn('polls', $col)) {
                    $add();
                }
            }
        });

        if (! Schema::hasColumn('poll_options', 'option_key')) {
            Schema::table('poll_options', function (Blueprint $t) {
                $t->string('option_key')->nullable()->after('poll_id'); // 'a'|'b'|'1'.. (seed key ổn định)
            });
        }

        Schema::table('poll_votes', function (Blueprint $t) {
            if (! Schema::hasColumn('poll_votes', 'apartment_id')) {
                $t->foreignId('apartment_id')->nullable()->after('resident_id')->constrained()->nullOnDelete();
            }
        });

        // Vote uniqueness apartment-scope (chỉ MySQL; sqlite test kiểm ở tầng service).
        if (DB::getDriverName() === 'mysql') {
            Schema::table('poll_votes', function (Blueprint $t) {
                $t->index(['poll_id', 'apartment_id'], 'poll_votes_poll_apartment_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::table('polls', function (Blueprint $t) {
            $t->dropColumn(['summary', 'anonymous', 'vote_scope', 'allow_change_vote', 'max_choices', 'result_visibility', 'opens_at']);
        });
        Schema::table('poll_options', fn (Blueprint $t) => $t->dropColumn('option_key'));
        if (DB::getDriverName() === 'mysql') {
            Schema::table('poll_votes', fn (Blueprint $t) => $t->dropIndex('poll_votes_poll_apartment_idx'));
        }
        Schema::table('poll_votes', function (Blueprint $t) {
            $t->dropConstrainedForeignId('apartment_id');
        });
    }
};
