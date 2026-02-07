<?php

namespace Tests\Feature;

use App\Models\ChatTopic;
use App\Models\GenericNews;
use App\Services\AiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerateLiveliaTopicsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_a_topic_using_ai_service_dependency(): void
    {
        ChatTopic::create([
            'topic' => 'Topic precedente?',
            'from' => today()->subDays(5)->toDateString(),
            'to' => today()->subDay()->toDateString(),
        ]);

        GenericNews::create([
            'title' => 'Titolo test',
            'news_date' => today()->toDateString(),
            'category' => 'ai',
            'summary' => 'Sintesi di prova per il contesto AI.',
            'strategic_impact' => 'Impatto test',
            'why_it_matters' => 'Motivo test',
            'source_name' => 'Fonte test',
            'source_url' => 'https://example.com/news',
            'published_at' => now(),
        ]);

        $this->app->instance(AiService::class, new class extends AiService
        {
            public function generateJson(string $prompt, string $modelId, ?string $promptPath = null): array
            {
                return ['topic' => 'Quanta libertà ci resta quando scegliamo per abitudine?'];
            }
        });

        $this->artisan('livelia:generate_topics --max-days=0')
            ->assertExitCode(0);

        $this->assertDatabaseHas('chat_topics', [
            'topic' => 'Quanta libertà ci resta quando scegliamo per abitudine?',
            'from' => today()->toDateString(),
            'to' => today()->addDays(4)->toDateString(),
        ]);
    }
}
