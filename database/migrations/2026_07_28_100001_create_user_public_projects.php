<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dự án QUAN TÂM của tài khoản (chọn ở màn đăng ký app cư dân).
 *
 * Khác hẳn `apartment_resident_relations` — đó là quan hệ cư dân ĐÃ XÁC THỰC với
 * căn hộ. Bảng này chỉ ghi "tôi quan tâm dự án X" của người vừa tạo tài khoản:
 * chưa xác thực gì, chỉ dùng để gợi ý nội dung và để BQL biết nhu cầu. Vì vậy
 * nó trỏ sang `public_projects` (thư viện dùng chung) chứ không phải `projects`
 * (dự án đang vận hành của tenant).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_public_projects')) {
            return;
        }

        Schema::create('user_public_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('public_project_id')->constrained('public_projects')->cascadeOnDelete();
            $table->string('source', 32)->default('register'); // register | profile | admin
            $table->timestamps();

            $table->unique(['user_id', 'public_project_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_public_projects');
    }
};
