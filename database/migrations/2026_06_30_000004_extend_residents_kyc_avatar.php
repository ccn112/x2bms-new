<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WEB-FORM-02-01 "Thêm cư dân" — the approved form carries far more than the
 * thin residents table held. Adds: avatar, contact (vs login) channels, intended
 * role, profile/KYC status + document images, supplementary info, attachments.
 *
 * KYC is denormalised onto residents here (status + 3 image paths + face match)
 * to keep the create form a single save; can be split to resident_kyc later.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('residents', function (Blueprint $table) {
            $table->string('avatar_path')->nullable()->after('full_name');

            // Contact channels (login phone/email already live in `phone`/`email`).
            $table->string('contact_phone')->nullable()->after('phone');
            $table->string('contact_email')->nullable()->after('email');

            // Role & profile lifecycle.
            $table->string('requested_role')->nullable()->after('contact_email'); // owner|tenant|member
            $table->string('profile_status')->default('cho_bo_sung')->after('status'); // cho_bo_sung|cho_duyet|hoat_dong|tu_choi
            $table->string('source')->default('bql_manual')->after('profile_status'); // bql_manual|app_self|import

            // KYC.
            $table->string('kyc_status')->default('unverified')->after('source'); // unverified|pending|verified|rejected
            $table->string('id_front_path')->nullable()->after('kyc_status');
            $table->string('id_back_path')->nullable()->after('id_front_path');
            $table->string('portrait_path')->nullable()->after('id_back_path');
            $table->string('face_match_status')->default('not_checked')->after('portrait_path'); // not_checked|matched|mismatch

            // Supplementary.
            $table->string('occupation')->nullable()->after('face_match_status');
            $table->string('relationship_to_head')->nullable()->after('occupation');
            $table->string('vehicle_plate')->nullable()->after('relationship_to_head');
            $table->text('internal_note')->nullable()->after('vehicle_plate');

            // Attachments (contract / certificate / KT3 / authorization) as path list.
            $table->json('documents')->nullable()->after('internal_note');
        });
    }

    public function down(): void
    {
        Schema::table('residents', function (Blueprint $table) {
            $table->dropColumn([
                'avatar_path', 'contact_phone', 'contact_email', 'requested_role',
                'profile_status', 'source', 'kyc_status', 'id_front_path', 'id_back_path',
                'portrait_path', 'face_match_status', 'occupation', 'relationship_to_head',
                'vehicle_plate', 'internal_note', 'documents',
            ]);
        });
    }
};
