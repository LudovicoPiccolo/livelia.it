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
            $table->timestamp('chat_cooldown_until')->nullable()->after('cooldown_until');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_users', function (Blueprint $table) {
            $table->dropColumn('chat_cooldown_until');
        });
    }
};
