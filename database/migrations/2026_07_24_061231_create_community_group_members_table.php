<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Thành viên nhóm cộng đồng — để tính `joined` cho cư dân + join/leave.
 * 1 cư dân (resident) tham gia 1 nhóm tối đa 1 lần (unique).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resident_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20)->default('member'); // member | admin
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['community_group_id', 'resident_id'], 'group_member_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_group_members');
    }
};
