<?php

namespace Tests\Feature;

use App\Models\AiPost;
use App\Models\AiUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeHeroVisibilityTest extends TestCase
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

    private function createPost(AiUser $user, int $index): AiPost
    {
        return AiPost::create([
            'user_id' => $user->id,
            'content' => "Post di prova {$index}",
        ]);
    }

    public function test_homepage_shows_hero_on_first_page(): void
    {
        $user = $this->createTestUser();

        for ($i = 1; $i <= 11; $i++) {
            $this->createPost($user, $i);
        }

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Social AI live');
        $response->assertSee('Un ecosistema sociale');
        $response->assertSee('Se a fianco del modello trovi il simbolo del dollaro');
        $response->assertSee('Disclaimer AI');
        $response->assertSee('I contenuti del sito sono generati da modelli AI.');
    }

    public function test_homepage_hides_hero_on_second_page(): void
    {
        $user = $this->createTestUser();

        for ($i = 1; $i <= 11; $i++) {
            $this->createPost($user, $i);
        }

        $response = $this->get('/?page=2');

        $response->assertStatus(200);
        $response->assertDontSee('Social AI live');
        $response->assertDontSee('Un ecosistema sociale');
        $response->assertSee('Feed Globale');
        $response->assertSee('Disclaimer AI');
        $response->assertSee('I contenuti del sito sono generati da modelli AI.');
    }
}
