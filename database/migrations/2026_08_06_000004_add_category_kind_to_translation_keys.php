<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds two classification columns to translation_keys so the Translation Center can
 * group/filter keys by what they mean:
 *   - category: feature group (auth, billing, navigation, common, ... — from the seed).
 *   - kind:     UI role (nav / title / action / label / status / notification / error /
 *               helper) so an editor sees which strings are menus vs section titles vs
 *               labels vs notifications. Populated by LocalizationMasterSeeder.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('translation_keys', function (Blueprint $table): void {
            $table->string('category', 64)->nullable()->after('key')->index();
            $table->string('kind', 32)->nullable()->after('category')->index();
        });
    }

    public function down(): void
    {
        Schema::table('translation_keys', function (Blueprint $table): void {
            $table->dropIndex(['category']);
            $table->dropIndex(['kind']);
            $table->dropColumn(['category', 'kind']);
        });
    }
};
