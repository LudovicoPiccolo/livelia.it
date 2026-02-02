<?php

namespace App\Console\Commands;

use App\Models\AiLog;
use App\Models\GenericNews;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchGenericNews extends Command
{
    protected $signature = 'livelia:fetch_generic_news';

    protected $description = 'Fetch generic Italian news using AI with web search (Grok)';

    public function handle(): int
    {
        $this->info('🚀 Fetching generic Italian news...');

        try {
            $newsData = $this->fetchNewsWithAI();

            if (! $newsData) {
                $this->error('❌ Unable to fetch news from AI');

                return Command::FAILURE;
            }

            $newsCount = $newsData['news_count'] ?? 0;
            $this->info("✅ Found {$newsCount} relevant news items");

            if ($newsCount === 0) {
                $this->warn('⚠️  No relevant news found');

                return Command::SUCCESS;
            }

            // Save news to database
            $saved = 0;
            foreach ($newsData['news'] as $newsItem) {
                try {
                    GenericNews::create([
                        'title' => $newsItem['title'],
                        'news_date' => $newsItem['news_date'],
                        'category' => $newsItem['category'],
                        'summary' => $newsItem['summary'],
                        'strategic_impact' => $newsItem['strategic_impact'] ?? null,
                        'why_it_matters' => $newsItem['why_it_matters'] ?? null,
                        'source_name' => $newsItem['source']['name'] ?? null,
                        'source_url' => $newsItem['source']['url'] ?? null,
                        'published_at' => now(),
                    ]);
                    $saved++;
                } catch (\Exception $e) {
                    $this->warn("⚠️  Failed to save news: {$newsItem['title']} - {$e->getMessage()}");
                }
            }

            $this->info("✅ Saved {$saved}/{$newsCount} news items to database");

            Log::info('Generic news fetched successfully', [
                'total_found' => $newsCount,
                'total_saved' => $saved,
                'categories' => array_unique(array_column($newsData['news'], 'category')),
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Error: {$e->getMessage()}");

            Log::error('Failed to fetch generic news', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    private function fetchNewsWithAI(): ?array
    {
        $prompt = $this->buildPrompt();

        $this->info('🤖 Querying AI with web search...');

        $response = $this->callAiWithWebSearch($prompt);

        if (! $response) {
            return null;
        }

        $content = $response['choices'][0]['message']['content'] ?? null;

        if (! $content) {
            return null;
        }

        // Log to AI logs table
        $usage = $response['usage'] ?? [];
        AiLog::create([
            'model' => 'x-ai/grok-4.1-fast',
            'input_prompt' => $prompt,
            'output_content' => $content,
            'full_response' => $response,
            'status_code' => 200,
            'error_message' => null,
            'prompt_file' => 'fetch_generic_news',
        ]);

        // Clean JSON response (remove markdown code blocks if present)
        $content = preg_replace('/^```json\s*\n?/m', '', $content);
        $content = preg_replace('/\n?```$/m', '', $content);
        $content = trim($content);

        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('⚠️  JSON parsing error: '.json_last_error_msg());
            Log::error('Failed to decode AI response', [
                'error' => json_last_error_msg(),
                'content' => $content,
            ]);

            return null;
        }

        return $data;
    }

    private function callAiWithWebSearch(string $prompt): ?array
    {
        try {
            $apiKey = config('services.ai.api_key');
            $baseUrl = config('services.ai.base_url');

            if (empty($apiKey)) {
                throw new \Exception('AI API Key is not configured');
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => config('app.url'),
                'X-Title' => config('app.name'),
            ])
                ->timeout(300)
                ->post("{$baseUrl}/chat/completions", [
                    'model' => 'x-ai/grok-4.1-fast',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 4000,
                    'plugins' => [
                        [
                            'id' => 'web',
                            'max_results' => 20,
                        ],
                    ],
                ]);

            if (! $response->successful()) {
                Log::error('OpenRouter API Error with web search', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error('AI Client Exception with web search', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    private function buildPrompt(): string
    {
        return <<<'PROMPT'
Agisci come un curatore editoriale che seleziona notizie italiane generiche di attualità.

Il tuo compito è:
- analizzare notizie italiane delle ultime 24 ore
- selezionare notizie varie e interessanti da diverse categorie
- eliminare clickbait estremi e fake news evidenti
- privilegiare fonti italiane autorevoli

Categorie da considerare (mix variegato):
- Politica italiana (Priorità 5)
- Economia e finanza (Priorità 4)
- Tecnologia e innovazione (Priorità 3)
- Cronaca nazionale (Priorità 5)
- Cultura e spettacolo (Priorità 4)
- Sport (Priorità 3)
- Scienza e salute (Priorità 4)
- Ambiente e sostenibilità (Priorità 3)

Criteri di selezione:
- notizie recenti (ultime 24 ore)
- rilevanza per il pubblico italiano
- varietà di argomenti (non concentrarti solo su una categoria)
- fonti verificabili

Privilegia:
- Testate italiane (ANSA, Corriere, Repubblica, Il Sole 24 Ore, etc.)
- Notizie con impatto sociale o culturale
- Storie che possano generare discussione

Scrivi in italiano.
Non usare preamboli.
Non usare testo fuori dallo schema richiesto.

Analizza il web e individua circa 20 notizie italiane interessanti delle ultime 24 ore.
Varia le categorie per avere un panorama completo dell'attualità italiana.

Restituisci SOLO il JSON seguente (senza markdown, senza spiegazioni):

{
  "date": "YYYY-MM-DD",
  "news_count": 0,
  "news": [
    {
      "title": "Titolo notizia",
      "news_date": "YYYY-MM-DD",
      "category": "politica | economia | tecnologia | cronaca | cultura | sport | scienza | ambiente",
      "summary": "Riassunto della notizia (100-200 caratteri)",
      "strategic_impact": "Perché è rilevante per gli italiani",
      "why_it_matters": "Contesto e implicazioni",
      "source": {
        "name": "Nome testata",
        "url": "URL fonte"
      }
    }
  ]
}
PROMPT;
    }
}
