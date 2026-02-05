<?php

namespace App\Console\Commands;

use App\Models\GenericNews;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

use function Laravel\Prompts\textarea;

class CreateNewsCommand extends Command
{
    protected $signature = 'livelia:createnews';

    protected $description = 'Create news from pasted text using Grok AI to parse and format the data';

    public function handle(): int
    {
        // Gestione interruzione con Ctrl+C
        if (function_exists('pcntl_signal')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGINT, function () {
                $this->newLine();
                $this->warn('⚠️  Operazione annullata dall\'utente.');
                exit(self::FAILURE);
            });
        }

        $newsText = textarea(
            label: 'Incolla il testo con l\'elenco delle news',
            placeholder: 'Incolla qui il testo con le news...',
            hint: 'Puoi incollare più righe. Premi Ctrl+D quando hai finito. Premi Ctrl+C per annullare.',
            required: true,
        );

        if (empty($newsText)) {
            $this->error('Nessun testo fornito.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('📡 Elaborazione del testo con Grok AI...');
        $this->line('   Premi Ctrl+C per interrompere');
        $this->newLine();

        $newsData = $this->parseNewsWithGrok($newsText);

        if (empty($newsData)) {
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('💾 Inserimento delle news nel database...');

        $inserted = $this->insertNews($newsData);

        if ($inserted === 0) {
            return self::FAILURE;
        }

        $this->newLine();
        $this->components->success("✓ {$inserted} news inserite con successo!");

        return self::SUCCESS;
    }

    protected function parseNewsWithGrok(string $text): array
    {
        $baseUrl = config('services.ai.base_url');
        $apiKey = config('services.ai.api_key');

        $systemPrompt = <<<'PROMPT'
Sei un assistente che converte testo non strutturato in JSON strutturato.

Riceverai un elenco di news in formato libero. Devi convertirlo in un array JSON con questa struttura:

[
  {
    "title": "Titolo della news",
    "news_date": "YYYY-MM-DD",
    "category": "Categoria",
    "summary": "Breve riassunto della news",
    "strategic_impact": "Analisi dell'impatto strategico (opzionale)",
    "why_it_matters": "Perché è importante (opzionale)",
    "source_name": "Nome della fonte (opzionale)",
    "source_url": "URL della fonte (opzionale)"
  }
]

REGOLE:
1. Estrai TUTTE le news dal testo
2. Se la data non è specificata, usa la data odierna
3. Se la categoria non è chiara, prova a dedurla dal contenuto o usa "Generale"
4. Crea un summary conciso e informativo
5. strategic_impact e why_it_matters sono opzionali ma consigliati
6. Se non ci sono source_name o source_url, omettili
7. Rispondi SOLO con il JSON, senza testo aggiuntivo

IMPORTANTE: La risposta deve essere un JSON valido parsabile.
PROMPT;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => config('app.url'),
                'X-Title' => config('app.name'),
            ])
                ->timeout(60)
                ->post("{$baseUrl}/chat/completions", [
                    'model' => 'x-ai/grok-4.1-fast',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $systemPrompt,
                        ],
                        [
                            'role' => 'user',
                            'content' => $text,
                        ],
                    ],
                    'temperature' => 0.3,
                ]);

            if (! $response->successful()) {
                $this->error('Errore HTTP '.$response->status());
                $this->line('Risposta: '.$response->body());

                return [];
            }

            $body = $response->json();

            if (! isset($body['choices'][0]['message']['content'])) {
                $this->error('Risposta API non valida.');
                $this->line('Risposta completa: '.json_encode($body, JSON_PRETTY_PRINT));

                return [];
            }

            $content = $body['choices'][0]['message']['content'];

            // Rimuovi eventuali markdown code blocks
            $content = preg_replace('/^```json\s*\n?/m', '', $content);
            $content = preg_replace('/\n?```$/m', '', $content);
            $content = trim($content);

            $newsData = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('Errore nel parsing JSON: '.json_last_error_msg());
                $this->line('Contenuto ricevuto: '.$content);

                return [];
            }

            if (! is_array($newsData)) {
                $this->error('Risposta di Grok non è un array JSON valido.');
                $this->line('Tipo ricevuto: '.gettype($newsData));

                return [];
            }

            $this->info('✓ JSON parsato correttamente. News trovate: '.count($newsData));

            return $newsData;
        } catch (\Exception $e) {
            $this->error('Errore nella chiamata a Grok: '.$e->getMessage());

            return [];
        }
    }

    protected function insertNews(array $newsData): int
    {
        $inserted = 0;

        DB::beginTransaction();

        try {
            foreach ($newsData as $news) {
                // Validazione base dei campi obbligatori
                if (empty($news['title']) || empty($news['summary'])) {
                    $this->warn('News saltata per mancanza di campi obbligatori: '.json_encode($news));

                    continue;
                }

                GenericNews::create([
                    'title' => $news['title'],
                    'news_date' => $news['news_date'] ?? now()->toDateString(),
                    'category' => $news['category'] ?? 'Generale',
                    'summary' => $news['summary'],
                    'strategic_impact' => $news['strategic_impact'] ?? null,
                    'why_it_matters' => $news['why_it_matters'] ?? null,
                    'source_name' => $news['source_name'] ?? null,
                    'source_url' => $news['source_url'] ?? null,
                    'published_at' => now(),
                ]);

                $inserted++;
            }

            DB::commit();

            return $inserted;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Errore nell\'inserimento delle news: '.$e->getMessage());

            return 0;
        }
    }
}
