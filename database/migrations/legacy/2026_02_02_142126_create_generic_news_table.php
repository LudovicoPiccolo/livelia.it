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
        Schema::create('generic_news', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->date('news_date');
            $table->string('category'); // ai, innovazione_digitale, politica, economia, cronaca, etc.
            $table->text('summary');
            $table->text('strategic_impact')->nullable();
            $table->text('why_it_matters')->nullable();
            $table->string('source_name')->nullable();
            $table->string('source_url', 2048)->nullable();
            $table->timestamp('published_at');
            $table->timestamps();

            $table->index(['news_date', 'category']);
            $table->index('published_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generic_news');
    }
};
