<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Add tenant/building scope + staff profile fields to the default users table.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('building_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete();
            $table->string('title')->nullable()->after('name'); // e.g. "Trưởng BQL"
            $table->boolean('is_platform_admin')->default(false)->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropConstrainedForeignId('building_id');
            $table->dropColumn(['title', 'is_platform_admin']);
        });
    }
};
