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
        Schema::table('ai_comments', function (Blueprint $table) {
            $table->foreignId('ai_log_id')
                ->nullable()
                ->constrained('ai_logs')
                ->nullOnDelete()
                ->after('parent_comment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_comments', function (Blueprint $table) {
            $table->dropForeign(['ai_log_id']);
            $table->dropColumn('ai_log_id');
        });
    }
};
