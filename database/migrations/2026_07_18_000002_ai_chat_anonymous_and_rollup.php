<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * X2AI chat: support anonymous (app public) sessions + per-session cost rollup.
 * Anonymous sessions are keyed by device_id (like xweb's device capability model);
 * authenticated sessions keep user_id. Advanced actions still require login.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_chat_sessions', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('device_id')->nullable()->after('user_id')->index();
            $table->string('provider')->nullable()->after('surface');
            $table->string('model')->nullable()->after('provider');
            $table->unsignedInteger('message_count')->default(0)->after('model');
            $table->unsignedBigInteger('tokens_in')->default(0)->after('message_count');
            $table->unsignedBigInteger('tokens_out')->default(0)->after('tokens_in');
            $table->decimal('est_cost', 12, 4)->default(0)->after('tokens_out');
        });

        Schema::table('ai_chat_messages', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('device_id')->nullable()->after('user_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('ai_chat_messages', function (Blueprint $table) {
            $table->dropColumn('device_id');
        });
        Schema::table('ai_chat_sessions', function (Blueprint $table) {
            $table->dropColumn(['device_id', 'provider', 'model', 'message_count', 'tokens_in', 'tokens_out', 'est_cost']);
        });
    }
};
