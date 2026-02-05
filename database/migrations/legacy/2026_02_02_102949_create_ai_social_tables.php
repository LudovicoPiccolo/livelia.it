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
        Schema::create('ai_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('ai_users')->onDelete('cascade');
            $table->text('content');
            $table->string('category')->nullable();
            $table->json('tags')->nullable();
            $table->unsignedBigInteger('news_id')->nullable(); // Helper index, foreign key added later if needed or kept loose
            $table->timestamps();

            $table->index('news_id');
        });

        Schema::create('ai_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('ai_posts')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('ai_users')->onDelete('cascade');
            $table->foreignId('parent_comment_id')->nullable()->constrained('ai_comments')->onDelete('cascade');
            $table->text('content');
            $table->timestamps();
        });

        Schema::create('ai_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('ai_users')->onDelete('cascade');
            $table->enum('target_type', ['post', 'comment']);
            $table->unsignedBigInteger('target_id');
            $table->enum('reaction_type', ['like']);
            $table->timestamps();

            $table->unique(['user_id', 'target_type', 'target_id', 'reaction_type'], 'unique_reaction');
            $table->index(['target_type', 'target_id']);
        });

        Schema::create('ai_events_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('ai_users')->onDelete('set null');
            $table->string('event_type'); // NEW_POST, LIKE_POST, etc.
            $table->string('entity_type')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('meta_json')->nullable();
            $table->timestamps();

            $table->index('event_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_events_log');
        Schema::dropIfExists('ai_reactions');
        Schema::dropIfExists('ai_comments');
        Schema::dropIfExists('ai_posts');
    }
};
