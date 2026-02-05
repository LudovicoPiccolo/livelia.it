<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AiSchemaMigrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_creates_the_ai_schema_tables_with_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('ai_users'));
        $this->assertTrue(Schema::hasColumns('ai_users', [
            'id',
            'user_id',
            'nome',
            'sesso',
            'orientamento_sessuale',
            'lavoro',
            'orientamento_politico',
            'passioni',
            'bias_informativo',
            'personalita',
            'stile_comunicativo',
            'atteggiamento_verso_attualita',
            'propensione_al_conflitto',
            'sensibilita_ai_like',
            'ritmo_attivita',
            'energia_sociale',
            'umore',
            'last_action_at',
            'cooldown_until',
            'bisogno_validazione',
            'generated_by_model',
            'is_pay',
            'source_prompt_file',
            'software_version',
            'created_at',
            'updated_at',
            'avatar_updated_at',
        ]));

        $this->assertTrue(Schema::hasTable('ai_logs'));
        $this->assertTrue(Schema::hasColumns('ai_logs', [
            'id',
            'model',
            'is_pay',
            'input_prompt',
            'output_content',
            'full_response',
            'status_code',
            'error_message',
            'prompt_file',
            'created_at',
            'updated_at',
        ]));

        $this->assertTrue(Schema::hasTable('ai_models'));
        $this->assertTrue(Schema::hasColumns('ai_models', [
            'id',
            'model_id',
            'canonical_slug',
            'name',
            'pricing',
            'estimated_costs',
            'architecture',
            'is_free',
            'suspended_until',
            'was_free',
            'is_text',
            'is_audio',
            'is_image',
            'created_at',
            'updated_at',
            'deleted_at',
        ]));

        $this->assertTrue(Schema::hasTable('generic_news'));
        $this->assertTrue(Schema::hasColumns('generic_news', [
            'id',
            'social_post_id',
            'title',
            'news_date',
            'category',
            'summary',
            'strategic_impact',
            'why_it_matters',
            'source_name',
            'source_url',
            'published_at',
            'created_at',
            'updated_at',
        ]));

        $this->assertTrue(Schema::hasTable('ai_posts'));
        $this->assertTrue(Schema::hasColumns('ai_posts', [
            'id',
            'user_id',
            'content',
            'category',
            'tags',
            'news_id',
            'ai_log_id',
            'is_pay',
            'source_type',
            'software_version',
            'created_at',
            'updated_at',
        ]));

        $this->assertTrue(Schema::hasTable('ai_comments'));
        $this->assertTrue(Schema::hasColumns('ai_comments', [
            'id',
            'post_id',
            'user_id',
            'parent_comment_id',
            'ai_log_id',
            'is_pay',
            'content',
            'software_version',
            'created_at',
            'updated_at',
        ]));

        $this->assertTrue(Schema::hasTable('ai_reactions'));
        $this->assertTrue(Schema::hasColumns('ai_reactions', [
            'id',
            'user_id',
            'target_type',
            'target_id',
            'reaction_type',
            'created_at',
            'updated_at',
        ]));

        $this->assertTrue(Schema::hasTable('ai_events_log'));
        $this->assertTrue(Schema::hasColumns('ai_events_log', [
            'id',
            'user_id',
            'is_pay',
            'event_type',
            'entity_type',
            'entity_id',
            'meta_json',
            'created_at',
            'updated_at',
        ]));

        $this->assertTrue(Schema::hasTable('user_reactions'));
        $this->assertTrue(Schema::hasColumns('user_reactions', [
            'id',
            'user_id',
            'target_type',
            'target_id',
            'reaction_type',
            'created_at',
            'updated_at',
        ]));
    }
}
