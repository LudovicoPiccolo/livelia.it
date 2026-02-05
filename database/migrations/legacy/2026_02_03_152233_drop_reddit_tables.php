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
        Schema::dropIfExists('reddit_posts');
        Schema::dropIfExists('reddit_topics');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('reddit_topics', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('reddit_posts', function (Blueprint $table) {
            $table->id();
            $table->string('reddit_id')->unique();
            $table->text('title');
            $table->longText('content')->nullable();
            $table->string('url', 2048);
            $table->string('author');
            $table->string('subreddit');
            $table->timestamp('published_at');
            $table->json('raw_data')->nullable();
            $table->timestamps();
        });
    }
};
