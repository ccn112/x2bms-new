<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WEB-FORM-07-04 — statement approval lifecycle (separate from payment `status`).
 * pending → approved → published (or rejected).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('statements', function (Blueprint $table) {
            $table->string('approval_status')->default('pending')->after('status'); // pending|approved|published|rejected
        });
    }

    public function down(): void
    {
        Schema::table('statements', function (Blueprint $table) {
            $table->dropColumn('approval_status');
        });
    }
};
