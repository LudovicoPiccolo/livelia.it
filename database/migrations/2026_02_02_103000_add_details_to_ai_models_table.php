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
        Schema::table('ai_models', function (Blueprint $table) {
            $table->boolean('is_free')->default(false)->after('pricing');
            $table->boolean('was_free')->default(false)->after('is_free');
            $table->boolean('is_text')->default(false)->after('was_free');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_models', function (Blueprint $table) {
            $table->dropColumn(['is_free', 'was_free', 'is_text']);
            $table->dropSoftDeletes();
        });
    }
};
