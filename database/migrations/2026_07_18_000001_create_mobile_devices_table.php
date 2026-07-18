<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mobile device / push-token registry (docs/ARCHITECTURE_X2_PLATFORM_V1.md §8).
 * user_id is nullable so an anonymous (public) install can opt in to push before login;
 * login attaches the user, logout detaches it while keeping the public subscription.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_devices', function (Blueprint $table) {
            $table->id();
            $table->uuid('installation_id')->unique();       // X-Device-Id — stable per install
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('platform');                       // ios|android
            $table->string('provider')->default('fcm');       // fcm|apns|hms
            $table->text('push_token')->nullable();           // stored encrypted at rest (cast)
            $table->string('app_version')->nullable();
            $table->string('locale')->nullable();
            $table->string('timezone')->nullable();
            $table->string('notification_permission')->nullable(); // granted|denied|provisional
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('token_refreshed_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index(['provider', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_devices');
    }
};
