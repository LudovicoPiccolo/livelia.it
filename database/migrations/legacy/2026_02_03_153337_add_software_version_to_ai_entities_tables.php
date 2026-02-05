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
            $table->string('software_version')->nullable()->after('source_prompt_file');
        });

        Schema::table('ai_posts', function (Blueprint $table) {
            $table->string('software_version')->nullable()->after('source_type');
        });

        Schema::table('ai_comments', function (Blueprint $table) {
            $table->string('software_version')->nullable()->after('ai_log_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_comments', function (Blueprint $table) {
            $table->dropColumn('software_version');
        });

        Schema::table('ai_posts', function (Blueprint $table) {
            $table->dropColumn('software_version');
        });

        Schema::table('ai_users', function (Blueprint $table) {
            $table->dropColumn('software_version');
        });
    }
};
