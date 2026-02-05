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
        Schema::table('ai_posts', function (Blueprint $table) {
            $table->foreignId('ai_log_id')
                ->nullable()
                ->constrained('ai_logs')
                ->nullOnDelete()
                ->after('news_id');
            $table->string('source_type')->nullable()->after('ai_log_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_posts', function (Blueprint $table) {
            $table->dropForeign(['ai_log_id']);
            $table->dropColumn(['ai_log_id', 'source_type']);
        });
    }
};
