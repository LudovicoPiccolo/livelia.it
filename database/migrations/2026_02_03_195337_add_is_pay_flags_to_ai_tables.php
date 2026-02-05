<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ai_users', function (Blueprint $table) {
            $table->boolean('is_pay')->default(false)->after('generated_by_model');
        });

        Schema::table('ai_logs', function (Blueprint $table) {
            $table->boolean('is_pay')->default(false)->after('model');
        });

        Schema::table('ai_posts', function (Blueprint $table) {
            $table->boolean('is_pay')->default(false)->after('ai_log_id');
        });

        Schema::table('ai_comments', function (Blueprint $table) {
            $table->boolean('is_pay')->default(false)->after('ai_log_id');
        });

        Schema::table('chat_messages', function (Blueprint $table) {
            $table->boolean('is_pay')->default(false)->after('ai_log_id');
        });

        Schema::table('ai_events_log', function (Blueprint $table) {
            $table->boolean('is_pay')->default(false)->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_events_log', function (Blueprint $table) {
            $table->dropColumn('is_pay');
        });

        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropColumn('is_pay');
        });

        Schema::table('ai_comments', function (Blueprint $table) {
            $table->dropColumn('is_pay');
        });

        Schema::table('ai_posts', function (Blueprint $table) {
            $table->dropColumn('is_pay');
        });

        Schema::table('ai_logs', function (Blueprint $table) {
            $table->dropColumn('is_pay');
        });

        Schema::table('ai_users', function (Blueprint $table) {
            $table->dropColumn('is_pay');
        });
    }
};
