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
        if (! Schema::hasTable('ai_users')) {
            Schema::create('ai_users', function (Blueprint $table) {
                $table->id();
                $table->string('nome');
                $table->string('sesso')->nullable();
                $table->string('orientamento_sessuale');
                $table->string('lavoro');
                $table->string('orientamento_politico');
                $table->json('passioni');
                $table->text('bias_informativo');
                $table->text('personalita');
                $table->text('stile_comunicativo');
                $table->text('atteggiamento_verso_attualita');
                $table->integer('propensione_al_conflitto');
                $table->integer('sensibilita_ai_like');
                $table->string('ritmo_attivita');
                $table->tinyInteger('energia_sociale')->default(100);
                $table->string('umore')->default('neutro');
                $table->timestamp('last_action_at')->nullable();
                $table->timestamp('cooldown_until')->nullable();
                $table->tinyInteger('bisogno_validazione')->default(50);
                $table->string('generated_by_model');
                $table->string('source_prompt_file');
                $table->string('software_version')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('ai_logs')) {
            Schema::create('ai_logs', function (Blueprint $table) {
                $table->id();
                $table->string('model');
                $table->longText('input_prompt');
                $table->longText('output_content')->nullable();
                $table->json('full_response')->nullable();
                $table->integer('status_code')->nullable();
                $table->text('error_message')->nullable();
                $table->string('prompt_file')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('ai_models')) {
            Schema::create('ai_models', function (Blueprint $table) {
                $table->id();
                $table->string('model_id')->unique();
                $table->string('canonical_slug')->nullable();
                $table->string('name')->nullable();
                $table->json('pricing')->nullable();
                $table->json('architecture')->nullable();
                $table->boolean('is_free')->default(false);
                $table->timestamp('suspended_until')->nullable();
                $table->boolean('was_free')->default(false);
                $table->boolean('is_text')->default(false);
                $table->boolean('is_audio')->default(false);
                $table->boolean('is_image')->default(false);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('generic_news')) {
            Schema::create('generic_news', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('social_post_id')->nullable();
                $table->string('title');
                $table->date('news_date');
                $table->string('category');
                $table->text('summary');
                $table->text('strategic_impact')->nullable();
                $table->text('why_it_matters')->nullable();
                $table->string('source_name')->nullable();
                $table->string('source_url', 2048)->nullable();
                $table->timestamp('published_at');
                $table->timestamps();

                $table->index(['news_date', 'category']);
                $table->index('published_at');
                $table->index('social_post_id');
            });
        }

        if (! Schema::hasTable('ai_posts')) {
            Schema::create('ai_posts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('ai_users')->onDelete('cascade');
                $table->text('content');
                $table->string('category')->nullable();
                $table->json('tags')->nullable();
                $table->unsignedBigInteger('news_id')->nullable();
                $table->foreignId('ai_log_id')
                    ->nullable()
                    ->constrained('ai_logs')
                    ->nullOnDelete();
                $table->string('source_type')->nullable();
                $table->string('software_version')->nullable();
                $table->timestamps();

                $table->index('news_id');
            });
        }

        if (! Schema::hasTable('ai_comments')) {
            Schema::create('ai_comments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('post_id')->constrained('ai_posts')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('ai_users')->onDelete('cascade');
                $table->foreignId('parent_comment_id')->nullable()->constrained('ai_comments')->onDelete('cascade');
                $table->foreignId('ai_log_id')
                    ->nullable()
                    ->constrained('ai_logs')
                    ->nullOnDelete();
                $table->text('content');
                $table->string('software_version')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('ai_reactions')) {
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
        }

        if (! Schema::hasTable('ai_events_log')) {
            Schema::create('ai_events_log', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('ai_users')->onDelete('set null');
                $table->string('event_type');
                $table->string('entity_type')->nullable();
                $table->unsignedBigInteger('entity_id')->nullable();
                $table->json('meta_json')->nullable();
                $table->timestamps();

                $table->index('event_type');
            });
        }
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
        Schema::dropIfExists('generic_news');
        Schema::dropIfExists('ai_models');
        Schema::dropIfExists('ai_logs');
        Schema::dropIfExists('ai_users');
    }
};
