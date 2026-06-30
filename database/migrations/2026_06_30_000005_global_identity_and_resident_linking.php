<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SaaS identity model: separate the GLOBAL person/account from the PER-TENANT
 * resident membership.
 *
 *  - users (account_type='resident', tenant_id NULL) = the one self-registered,
 *    KYC'd human. Canonical identity. Spans companies/projects.
 *  - residents (tenant_id) = a BQL-entered membership in ONE tenant. Links to the
 *    global account via residents.user_id once matched by CCCD (id_no).
 *
 * One account → many resident memberships (project A @ Cty X, project B @ Cty Y),
 * each possibly carrying a different locally-typed name. CCCD is the match key.
 */
return new class extends Migration
{
    public function up(): void
    {
        // users becomes the global person/account for residents too.
        Schema::table('users', function (Blueprint $table) {
            $table->string('account_type')->default('staff')->after('is_platform_admin'); // staff|resident
            $table->string('phone')->nullable()->after('account_type');
            $table->string('id_no')->nullable()->after('phone');                          // CCCD — match key
            $table->date('dob')->nullable()->after('id_no');
            $table->string('gender')->nullable()->after('dob');
            $table->string('nationality')->nullable()->after('gender');
            $table->string('kyc_status')->default('unverified')->after('nationality');     // unverified|pending|verified|rejected
            $table->timestamp('kyc_verified_at')->nullable()->after('kyc_status');
            $table->string('avatar_path')->nullable()->after('kyc_verified_at');

            $table->index('id_no');
            $table->index('account_type');
        });

        // residents: link state to the global account.
        Schema::table('residents', function (Blueprint $table) {
            $table->string('link_status')->default('unlinked')->after('user_id'); // unlinked|suggested|linked
            $table->timestamp('linked_at')->nullable()->after('link_status');
        });
    }

    public function down(): void
    {
        Schema::table('residents', function (Blueprint $table) {
            $table->dropColumn(['link_status', 'linked_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['id_no']);
            $table->dropIndex(['account_type']);
            $table->dropColumn([
                'account_type', 'phone', 'id_no', 'dob', 'gender', 'nationality',
                'kyc_status', 'kyc_verified_at', 'avatar_path',
            ]);
        });
    }
};
