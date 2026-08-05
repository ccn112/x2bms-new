<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kho ARCHIVE: snapshot bất biến của bản ghi đã xóa mềm quá hạn (vòng đời xóa mềm →
 * archive). Giữ bằng chứng để đối chiếu/khôi phục thủ công + audit; KHÔNG gồm bảng tiền.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archived_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->json('snapshot');
            $table->timestamp('soft_deleted_at')->nullable();
            $table->timestamp('archived_at');
            $table->unsignedBigInteger('archived_by')->nullable();
            $table->boolean('purged')->default(false); // đã xóa cứng bản gốc sau archive?
            $table->timestamps();

            $table->unique(['model_type', 'model_id'], 'archived_records_model_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archived_records');
    }
};
