<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// RES-DETAIL-01 — richer resident profile + apartment attributes + emergency contacts.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('residents', function (Blueprint $table) {
            $table->date('dob')->nullable()->after('full_name');
            $table->string('gender')->nullable()->after('dob');
            $table->date('id_issued_date')->nullable()->after('id_no');
            $table->string('id_issued_place')->nullable()->after('id_issued_date');
            $table->string('nationality')->nullable()->after('id_issued_place');
            $table->string('marital_status')->nullable()->after('nationality');
            $table->string('contact_address')->nullable();
            $table->string('mailing_address')->nullable();
            $table->date('join_date')->nullable();
            $table->text('note')->nullable();
        });

        Schema::table('apartments', function (Blueprint $table) {
            $table->string('type')->nullable()->after('area_sqm');
            $table->string('ownership_type')->nullable()->after('type');
            $table->date('handover_date')->nullable()->after('ownership_type');
            $table->decimal('management_fee', 12, 2)->nullable()->after('handover_date');
            $table->text('note')->nullable();
        });

        Schema::create('resident_emergency_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resident_id')->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->string('relationship')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resident_emergency_contacts');
        Schema::table('apartments', function (Blueprint $table) {
            $table->dropColumn(['type', 'ownership_type', 'handover_date', 'management_fee', 'note']);
        });
        Schema::table('residents', function (Blueprint $table) {
            $table->dropColumn(['dob', 'gender', 'id_issued_date', 'id_issued_place', 'nationality', 'marital_status', 'contact_address', 'mailing_address', 'join_date', 'note']);
        });
    }
};
