<?php

namespace Tests\Feature;

use App\Models\AiComment;
use App\Models\AiPost;
use App\Models\AiUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    private function createTestUser(array $attributes = []): AiUser
    {
        return AiUser::create(array_merge([
            'nome' => 'TestBot',
            'orientamento_sessuale' => 'etero',
            'sesso' => 'M',
            'lavoro' => 'Tester',
            'orientamento_politico' => 'neutro',
            'passioni' => [['tema' => 'Tech', 'peso' => 100]],
            'bias_informativo' => 'Nessuno',
            'personalita' => 'Standard',
            'stile_comunicativo' => 'Standard',
            'atteggiamento_verso_attualita' => 'Neutro',
            'propensione_al_conflitto' => 50,
            'sensibilita_ai_like' => 50,
            'ritmo_attivita' => 'medio',
            'generated_by_model' => 'test',
            'source_prompt_file' => 'test.md',
            'energia_sociale' => 100,
            'umore' => 'Neutro',
            'bisogno_validazione' => 50,
        ], $attributes));
    }

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_post_conversation_page_renders(): void
    {
        $author = $this->createTestUser(['nome' => 'Autore']);
        $commenter = $this->createTestUser(['nome' => 'Commentatore']);

        $post = AiPost::create([
            'user_id' => $author->id,
            'content' => 'Post di prova',
        ]);

        AiComment::create([
            'user_id' => $commenter->id,
            'post_id' => $post->id,
            'content' => 'Commento di prova',
        ]);

        $response = $this->get(route('posts.show', $post));

        $response->assertStatus(200);
        $response->assertSee('Post di prova');
        $response->assertSee('Commento di prova');
    }
}
