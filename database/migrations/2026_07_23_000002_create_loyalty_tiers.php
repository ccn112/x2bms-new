<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng hạng loyalty + quyền lợi (cho GET /resident/loyalty: tier/next_tier/benefits).
 * Ngưỡng + quyền lợi là dữ liệu tham chiếu — seed mặc định, owner chỉnh sau.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();          // silver|gold|platinum
            $table->string('name');
            $table->unsignedInteger('min_points')->default(0);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('loyalty_tier_benefits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loyalty_tier_id')->constrained()->cascadeOnDelete();
            $table->string('icon_key')->nullable();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        $now = now();
        $tiers = [
            ['key' => 'silver', 'name' => 'Bạc', 'min_points' => 0, 'sort' => 1],
            ['key' => 'gold', 'name' => 'Vàng', 'min_points' => 5000, 'sort' => 2],
            ['key' => 'platinum', 'name' => 'Bạch kim', 'min_points' => 20000, 'sort' => 3],
        ];
        foreach ($tiers as $t) {
            $id = DB::table('loyalty_tiers')->insertGetId($t + ['created_at' => $now, 'updated_at' => $now]);
            DB::table('loyalty_tier_benefits')->insert([
                'loyalty_tier_id' => $id, 'icon_key' => 'gift', 'title' => 'Tích điểm mọi giao dịch',
                'subtitle' => 'Đổi quà & ưu đãi', 'sort' => 1, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_tier_benefits');
        Schema::dropIfExists('loyalty_tiers');
    }
};
