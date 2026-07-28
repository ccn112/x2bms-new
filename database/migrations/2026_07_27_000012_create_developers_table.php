<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Addendum — CHỦ ĐẦU TƯ (developers) là entity riêng, dedup theo slug.
 * public_projects.developer_id (nullable FK) trỏ về; giữ developer_name (chuỗi gốc đối chiếu).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('developers')) {
            Schema::create('developers', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('code')->nullable();
                $table->string('website')->nullable();
                $table->string('logo_path')->nullable();
                $table->text('description')->nullable();
                $table->string('source')->nullable();
                $table->json('metadata_json')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        Schema::table('public_projects', function (Blueprint $table) {
            if (! Schema::hasColumn('public_projects', 'developer_id')) {
                $table->foreignId('developer_id')->nullable()->after('developer_name')
                    ->constrained('developers')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('public_projects', function (Blueprint $table) {
            if (Schema::hasColumn('public_projects', 'developer_id')) {
                $table->dropConstrainedForeignId('developer_id');
            }
        });
        Schema::dropIfExists('developers');
    }
};
