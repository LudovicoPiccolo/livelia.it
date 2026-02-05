<?php

namespace Tests\Feature;

use App\Models\AiLog;
use App\Models\AiPost;
use App\Models\AiUser;
use App\Models\GenericNews;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostDetailsDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_prompt_is_loaded_on_demand(): void
    {
        $user = AiUser::factory()->create([
            'generated_by_model' => 'test-model',
        ]);

        $news = GenericNews::create([
            'title' => 'Notizia di test',
            'news_date' => now()->toDateString(),
            'category' => 'Economia',
            'summary' => 'Questo e un riassunto di test.',
            'why_it_matters' => 'E utile per verificare i dettagli.',
            'source_name' => 'Fonte Test',
            'source_url' => 'https://example.test/news',
            'published_at' => now(),
        ]);

        $aiLog = AiLog::create([
            'model' => 'test-model',
            'input_prompt' => 'PROMPT_TEST_UNICO',
            'output_content' => 'Output di test',
            'full_response' => ['ok' => true],
            'status_code' => 200,
            'error_message' => null,
            'prompt_file' => 'create_post.md',
        ]);

        $post = AiPost::create([
            'user_id' => $user->id,
            'content' => 'Contenuto del post',
            'news_id' => $news->id,
            'category' => 'Economia',
            'tags' => ['economia'],
            'ai_log_id' => $aiLog->id,
            'source_type' => 'generic_news',
        ]);

        $response = $this->get(route('posts.show', $post));

        $response->assertOk();
        $response->assertSee('Info creazione');
        $response->assertSee('Modello usato');
        $response->assertSee('test-model');
        $response->assertSee('Prompt file');
        $response->assertSee('create_post.md');
        $response->assertSee('Origine');
        $response->assertSee('Notizia esterna');
        $response->assertSee('Titolo notizia');
        $response->assertSee('Notizia di test');
        $response->assertSee('Fonte');
        $response->assertSee('Fonte Test');
        $response->assertSee(route('ai.details', ['type' => 'post', 'id' => $post->id]));
        $response->assertSee('data-ai-details');
        $response->assertSee(route('contact', ['post' => $post->id]));
        $response->assertSee('data-report-trigger');
        $response->assertSee('data-report-modal');
        $response->assertSee('Segnala contenuto');
        $response->assertSee('Sei sicuro che vuoi segnalare questo messaggio come inopportuno?');
        $response->assertDontSee('PROMPT_TEST_UNICO');
    }
}
