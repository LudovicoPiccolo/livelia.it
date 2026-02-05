<?php

namespace Tests\Feature;

use App\Models\AiEventLog;
use App\Models\AiPost;
use App\Models\AiUser;
use App\Models\GenericNews;
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
        $aiServiceMock->shouldReceive('getLastLog')
            ->andReturn(null);

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

    public function test_social_tick_ignores_used_generic_news_id(): void
    {
        $user = AiUser::create([
            'nome' => 'TestBotNews',
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

        $usedNews = GenericNews::create([
            'title' => 'Notizia gia usata',
            'news_date' => now()->toDateString(),
            'category' => 'Economia',
            'summary' => 'Riassunto di test.',
            'source_name' => 'Fonte Test',
            'source_url' => 'https://example.test/news',
            'published_at' => now(),
            'social_post_id' => 999,
        ]);

        $aiServiceMock = Mockery::mock(AiService::class);
        $aiServiceMock->shouldReceive('generateJson')
            ->once()
            ->andReturn([
                'content' => 'Questo post tenta di usare una news gia usata.',
                'used_news_id' => $usedNews->id,
            ]);
        $aiServiceMock->shouldReceive('getLastLog')
            ->andReturn(null);
        $this->instance(AiService::class, $aiServiceMock);

        $deciderMock = Mockery::mock(AiActionDeciderService::class);
        $deciderMock->shouldReceive('decideAction')
            ->once()
            ->andReturn('NEW_POST');
        $this->instance(AiActionDeciderService::class, $deciderMock);

        $this->artisan('livelia:social_tick')
            ->assertExitCode(0);

        $post = AiPost::first();
        $this->assertNotNull($post);
        $this->assertNull($post->news_id);

        $usedNews->refresh();
        $this->assertEquals(999, $usedNews->social_post_id);
    }

    public function test_social_tick_forces_specific_news_id(): void
    {
        $user = AiUser::create([
            'nome' => 'ForcedNewsBot',
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
            'energia_sociale' => 1,
            'cooldown_until' => now()->addMinutes(10),
            'is_pay' => true,
        ]);

        $news = GenericNews::create([
            'title' => 'Notizia forzata',
            'news_date' => now()->subDays(5)->toDateString(),
            'category' => 'Tech',
            'summary' => 'Riassunto di test.',
            'source_name' => 'Fonte Test',
            'source_url' => 'https://example.test/news',
            'published_at' => now()->subDays(5),
        ]);

        $aiServiceMock = Mockery::mock(AiService::class);
        $aiServiceMock->shouldReceive('generateJson')
            ->once()
            ->andReturn(['content' => 'Post con notizia forzata.']);
        $aiServiceMock->shouldReceive('getLastLog')
            ->andReturn(null);
        $this->instance(AiService::class, $aiServiceMock);

        $deciderMock = Mockery::mock(AiActionDeciderService::class);
        $deciderMock->shouldReceive('decideAction')
            ->never();
        $this->instance(AiActionDeciderService::class, $deciderMock);

        $this->artisan('livelia:social_tick --ID='.$news->id)
            ->assertExitCode(0);

        $post = AiPost::first();
        $this->assertNotNull($post);
        $this->assertEquals($user->id, $post->user_id);
        $this->assertEquals($news->id, $post->news_id);
        $this->assertEquals('Post con notizia forzata.', $post->content);

        $news->refresh();
        $this->assertEquals($post->id, $news->social_post_id);
    }

    public function test_social_tick_skips_when_no_eligible_users()
    {
        // No users in DB → pickUser() returns null

        $aiServiceMock = Mockery::mock(AiService::class);
        $aiServiceMock->shouldReceive('generateJson')->never();
        $this->instance(AiService::class, $aiServiceMock);

        $this->artisan('livelia:social_tick')
            ->expectsOutput('No eligible users found for this tick. Skipping.')
            ->assertExitCode(0);

        // No event logged, no user created
        $this->assertEquals(0, AiUser::count());
        $this->assertEquals(0, AiEventLog::count());
    }

    public function test_social_tick_runs_multiple_times()
    {
        $user = AiUser::create([
            'nome' => 'MultiTickBot',
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

        $aiServiceMock = Mockery::mock(AiService::class);
        $aiServiceMock->shouldReceive('generateJson')->never();
        $aiServiceMock->shouldReceive('getLastLog')
            ->andReturn(null);
        $this->instance(AiService::class, $aiServiceMock);

        $deciderMock = Mockery::mock(AiActionDeciderService::class);
        $deciderMock->shouldReceive('decideAction')
            ->twice()
            ->andReturn('NOTHING');
        $this->instance(AiActionDeciderService::class, $deciderMock);

        $this->artisan('livelia:social_tick --times=2')
            ->assertExitCode(0);

        $this->assertEquals(2, AiEventLog::where('user_id', $user->id)->count());
    }
}
