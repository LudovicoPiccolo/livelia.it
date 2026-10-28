<?php

namespace Tests\Feature;

use App\Models\AiLog;
use App\Models\GenericNews;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FetchGenericNewsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.ai.api_key' => 'test-key',
            'services.ai.base_url' => 'https://openrouter.test/api/v1',
            'livelia.ai_models.grok' => 'x-ai/grok-test',
        ]);
    }

    public function test_it_saves_news_using_configured_grok_model(): void
    {
        Http::fake([
            'openrouter.test/*' => Http::response($this->aiResponse([
                $this->newsItem('Prima notizia'),
                $this->newsItem('Seconda notizia'),
            ])),
        ]);

        $this->artisan('livelia:fetch_generic_news')->assertExitCode(0);

        Http::assertSent(fn (Request $request): bool => $request['model'] === 'x-ai/grok-test');
        $this->assertSame(2, GenericNews::count());
        $this->assertDatabaseHas('ai_logs', [
            'model' => 'x-ai/grok-test',
            'prompt_file' => 'fetch_generic_news.md',
        ]);
    }

    public function test_it_skips_news_with_duplicate_title(): void
    {
        GenericNews::create([
            'title' => 'Prima notizia',
            'news_date' => '2026-10-27',
            'category' => 'Cronaca',
            'summary' => 'Già presente',
            'published_at' => now(),
        ]);

        Http::fake([
            'openrouter.test/*' => Http::response($this->aiResponse([
                $this->newsItem('Prima notizia'),
                $this->newsItem('Seconda notizia'),
            ])),
        ]);

        $this->artisan('livelia:fetch_generic_news')->assertExitCode(0);

        $this->assertSame(2, GenericNews::count());
        $this->assertDatabaseHas('generic_news', ['title' => 'Seconda notizia']);
    }

    public function test_it_fails_when_model_is_unavailable(): void
    {
        Http::fake([
            'openrouter.test/*' => Http::response([
                'error' => ['message' => 'Model is deprecated', 'code' => 404],
            ], 404),
        ]);

        $this->artisan('livelia:fetch_generic_news')->assertExitCode(1);

        $this->assertSame(0, GenericNews::count());
        $this->assertSame(0, AiLog::count());
    }

    public function test_it_fails_when_response_is_not_valid_json(): void
    {
        Http::fake([
            'openrouter.test/*' => Http::response([
                'choices' => [['message' => ['content' => 'non è json']]],
            ]),
        ]);

        $this->artisan('livelia:fetch_generic_news')->assertExitCode(1);

        $this->assertSame(0, GenericNews::count());
    }

    /**
     * @param  array<int, array<string, mixed>>  $news
     * @return array<string, mixed>
     */
    private function aiResponse(array $news): array
    {
        return [
            'choices' => [[
                'message' => [
                    'content' => "```json\n".json_encode([
                        'news_count' => count($news),
                        'news' => $news,
                    ])."\n```",
                ],
            ]],
            'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 20],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function newsItem(string $title): array
    {
        return [
            'title' => $title,
            'news_date' => '2026-10-28',
            'category' => 'Cronaca',
            'summary' => "Riassunto di {$title}",
            'source' => ['name' => 'Fonte', 'url' => 'https://example.com'],
        ];
    }
}
