<?php

namespace Tests\Feature;

use App\Models\AiUser;
use App\Services\AiActionDeciderService;
use App\Services\AiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AiSocialTickTest extends TestCase
{
    use RefreshDatabase;

    public function test_social_tick_creates_post()
    {
        $user = AiUser::create([
            'nome' => 'TestBot',
            'orientamento_sessuale' => 'eterosessuale',
            'sesso' => 'non_binario',
            'lavoro' => 'Tester',
            'orientamento_politico' => 'neutro',
            'passioni' => [['tema' => 'Tech', 'peso' => 100]],
            'bias_informativo' => 'Nessuno',
            'personalita' => 'Test Personality',
            'stile_comunicativo' => 'Direct',
            'atteggiamento_verso_attualita' => 'Interested',
            'propensione_al_conflitto' => 50,
            'sensibilita_ai_like' => 50,
            'ritmo_attivita' => 'alto',
            'generated_by_model' => 'test-model',
            'source_prompt_file' => 'test.md',
            'energia_sociale' => 100,
        ]);

        // 2. Mock AiService to return content
        $aiServiceMock = Mockery::mock(AiService::class);
        $aiServiceMock->shouldReceive('generateJson')
            ->once() // Expect one call (post creation)
            ->andReturn(['content' => 'This is a test post content.']);

        $this->instance(AiService::class, $aiServiceMock);

        // 3. Mock Decider to force NEW_POST
        $deciderMock = Mockery::mock(AiActionDeciderService::class);
        $deciderMock->shouldReceive('decideAction')
            ->once()
            ->andReturn('NEW_POST');

        $this->instance(AiActionDeciderService::class, $deciderMock);

        // 4. Run command
        $this->artisan('livelia:social_tick')
            ->assertExitCode(0);

        // 5. Assertions
        $this->assertDatabaseHas('ai_posts', [
            'user_id' => $user->id,
            'content' => 'This is a test post content.',
        ]);

        $this->assertDatabaseHas('ai_events_log', [
            'user_id' => $user->id,
            'event_type' => 'NEW_POST',
        ]);

        // Assert energy consumed (default 25)
        $user->refresh();
        $this->assertEquals(75, $user->energia_sociale);
    }
}
