<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Đính kèm (ảnh…) dùng chung polymorphic — gắn cho Comment và mọi loại
        // phiếu (đăng ký khách, chuyển đồ, đặt tiện ích, thanh toán…).
        // attachable NULL = vừa upload, chưa gắn (sẽ link khi tạo phiếu/bình luận).
        if (Schema::hasTable('attachments')) {
            return;
        }
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->nullableMorphs('attachable');
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('url')->nullable();
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->unsignedInteger('order_column')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['uploaded_by', 'attachable_type', 'attachable_id'], 'attachment_owner_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
