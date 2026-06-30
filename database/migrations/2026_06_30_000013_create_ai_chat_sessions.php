<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WEB-UX-09 — group X2AI chat turns into sessions (one per conversation / screen
 * pre-prompt). The copilot's history icon lists a user's past sessions to restore.
 * ADD-ONLY: creates ai_chat_sessions and links ai_chat_messages to it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();          // derived from the first prompt
            $table->string('surface')->nullable();        // screen the session started on
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'last_message_at']);
        });

        Schema::table('ai_chat_messages', function (Blueprint $table) {
            $table->foreignId('ai_chat_session_id')->nullable()->after('user_id')
                ->constrained()->nullOnDelete();
            $table->index('ai_chat_session_id');
        });
    }

    public function down(): void
    {
        Schema::table('ai_chat_messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ai_chat_session_id');
        });
        Schema::dropIfExists('ai_chat_sessions');
    }
};
