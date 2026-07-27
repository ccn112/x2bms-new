<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Addendum — con trỏ phân trang cho việc thu thập dự án batdongsan ("Lấy tiếp").
 * Mỗi khu vực (city) nhớ trang cuối đã lấy để lần sau lấy trang kế tiếp.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bds_import_states', function (Blueprint $table) {
            $table->id();
            $table->string('city')->unique();       // key trong config/bds.php (ha-noi, tp-hcm...)
            $table->unsignedInteger('last_page')->default(0);
            $table->string('last_status')->nullable(); // ok|empty|blocked|error
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bds_import_states');
    }
};
