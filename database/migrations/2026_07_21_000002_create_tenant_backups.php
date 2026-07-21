<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sổ đăng ký bản backup của tenant (để quản lý ở SA: liệt kê, tải, xóa/retention).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_backups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('path');              // key bundle .zip trên tenant disk
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->unsignedInteger('file_count')->default(0);
            $table->json('table_counts')->nullable();
            $table->string('app_version')->nullable();
            $table->string('trigger')->default('manual'); // manual|offboard|scheduled
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_backups');
    }
};
