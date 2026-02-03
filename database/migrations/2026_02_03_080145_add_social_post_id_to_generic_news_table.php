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
        Schema::table('generic_news', function (Blueprint $table) {
            $table->unsignedBigInteger('social_post_id')->nullable()->after('id');
            // We can add foreign key if we want, but user didn't explicitly ask for constraint, just storage.
            // Adding index for performance on filtering nulls.
            $table->index('social_post_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('generic_news', function (Blueprint $table) {
            $table->dropColumn('social_post_id');
        });
    }
};
