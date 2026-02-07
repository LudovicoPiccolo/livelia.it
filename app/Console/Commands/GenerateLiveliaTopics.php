<?php

namespace App\Console\Commands;

use App\Services\AiService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class GenerateLiveliaTopics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'livelia:generate_topics
                            {--max-days=10 : Genera topic finché il campo "to" massimo è entro questo numero di giorni da oggi}
                            {--dry-run : Mostra cosa verrebbe generato senza inserire record}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera topic filosofici per chat_topics usando AI e contesto dalle ultime 30 generic_news';

    public function __construct(private readonly AiService $aiService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! Schema::hasTable('chat_topics')) {
            $this->error('Tabella "chat_topics" non trovata.');

            return self::FAILURE;
        }

        $chatColumns = Schema::getColumnListing('chat_topics');
        $topicColumn = $this->resolveFirstExistingColumn($chatColumns, ['topic', 'question', 'prompt', 'title', 'argomento']);
        $fromColumn = $this->resolveFirstExistingColumn($chatColumns, ['from', 'starts_at', 'start_date', 'date_from']);
        $toColumn = $this->resolveFirstExistingColumn($chatColumns, ['to', 'ends_at', 'end_date', 'date_to']);

        if (! $topicColumn || ! $fromColumn || ! $toColumn) {
            $this->error('Impossibile trovare le colonne necessarie in "chat_topics" (topic/from/to).');

            return self::FAILURE;
        }

        $maxToRaw = DB::table('chat_topics')->max($toColumn);
        $currentMaxTo = $maxToRaw
            ? Carbon::parse($maxToRaw)->startOfDay()
            : Carbon::today()->subDay();

        if (! $maxToRaw) {
            $this->warn('Nessun record esistente in chat_topics: uso base "oggi - 1 giorno".');
        }

        $daysAhead = max((int) $this->option('max-days'), 0);
        $limitDate = Carbon::today()->addDays($daysAhead)->startOfDay();
        $dryRun = (bool) $this->option('dry-run');

        if ($currentMaxTo->gt($limitDate)) {
            $this->info(sprintf(
                'Nessuna generazione necessaria: max "%s" (%s) è già oltre il limite (%s).',
                $toColumn,
                $currentMaxTo->toDateString(),
                $limitDate->toDateString()
            ));

            return self::SUCCESS;
        }

        $newsContext = $this->buildGenericNewsContext();
        $recentTopicsContext = $this->buildRecentTopicsContext($topicColumn, $toColumn);
        $created = 0;

        while ($currentMaxTo->lte($limitDate)) {
            $fromDate = $currentMaxTo->copy()->addDay()->startOfDay();
            $toDate = $fromDate->copy()->addDays(4)->startOfDay();

            try {
                $topic = $this->generateTopic($newsContext, $recentTopicsContext);
            } catch (RuntimeException $e) {
                $this->error($e->getMessage());

                return self::FAILURE;
            }

            $payload = [
                $topicColumn => $topic,
                $fromColumn => $fromDate->toDateString(),
                $toColumn => $toDate->toDateString(),
            ];

            if (in_array('created_at', $chatColumns, true)) {
                $payload['created_at'] = now();
            }
            if (in_array('updated_at', $chatColumns, true)) {
                $payload['updated_at'] = now();
            }

            if ($dryRun) {
                $this->line(sprintf(
                    '[DRY-RUN] topic="%s" | %s=%s | %s=%s',
                    $topic,
                    $fromColumn,
                    $fromDate->toDateString(),
                    $toColumn,
                    $toDate->toDateString()
                ));
            } else {
                DB::table('chat_topics')->insert($payload);
                $this->info(sprintf(
                    'Creato topic: "%s" (%s: %s -> %s)',
                    $topic,
                    $fromColumn,
                    $fromDate->toDateString(),
                    $toDate->toDateString()
                ));
            }

            $recentTopicsContext = $this->appendTopicToRecentContext($recentTopicsContext, $topic);
            $currentMaxTo = $toDate;
            $created++;
        }

        $this->info("Completato. Topic generati: {$created}.");

        return self::SUCCESS;
    }

    private function buildGenericNewsContext(): string
    {
        if (! Schema::hasTable('generic_news')) {
            $this->warn('Tabella "generic_news" non trovata: procedo senza contesto news.');

            return '';
        }

        $columns = Schema::getColumnListing('generic_news');
        $orderColumn = $this->resolveFirstExistingColumn($columns, ['created_at', 'published_at', 'date', 'id']);
        $titleCandidates = ['title', 'headline', 'name', 'subject'];
        $textCandidates = ['content', 'body', 'summary', 'description', 'text'];

        $selectedColumns = array_values(array_unique(array_filter([
            $this->resolveFirstExistingColumn($columns, $titleCandidates),
            $this->resolveFirstExistingColumn($columns, $textCandidates),
            $orderColumn,
        ])));

        if (empty($selectedColumns)) {
            return '';
        }

        $query = DB::table('generic_news')->select($selectedColumns);
        if ($orderColumn) {
            $query->orderByDesc($orderColumn);
        }

        $rows = $query->limit(30)->get();
        $lines = [];

        foreach ($rows as $row) {
            $title = $this->firstNonEmptyValueFromObject($row, $titleCandidates);
            $body = $this->firstNonEmptyValueFromObject($row, $textCandidates);
            $text = trim(($title ? "{$title}. " : '').$body);
            $text = preg_replace('/\s+/u', ' ', $text ?? '') ?? '';
            $text = trim($text);

            if ($text === '') {
                continue;
            }

            $lines[] = '- '.Str::limit($text, 240, '...');
        }

        return implode("\n", array_slice($lines, 0, 30));
    }

    private function buildRecentTopicsContext(string $topicColumn, string $toColumn): string
    {
        $rows = DB::table('chat_topics')
            ->select([$topicColumn])
            ->orderByDesc($toColumn)
            ->limit(20)
            ->get();

        $topics = [];
        foreach ($rows as $row) {
            $value = trim((string) ($row->{$topicColumn} ?? ''));
            if ($value !== '') {
                $topics[] = '- '.$value;
            }
        }

        return implode("\n", $topics);
    }

    private function appendTopicToRecentContext(string $recentTopicsContext, string $topic): string
    {
        $lines = array_values(array_filter(array_map('trim', explode("\n", $recentTopicsContext))));
        array_unshift($lines, '- '.$topic);

        return implode("\n", array_slice($lines, 0, 10));
    }

    private function generateTopic(string $newsContext, string $recentTopicsContext): string
    {
        $baseUserPrompt = "Genera UNA sola domanda filosofica interessante in italiano.\n"
            ."Requisiti obbligatori:\n"
            ."- tra 50 e 100 caratteri (spazi inclusi)\n"
            ."- deve finire con il punto interrogativo\n"
            ."- niente numerazione, niente virgolette, niente spiegazioni\n"
            ."- DEVE essere completamente diversa e su un tema TOTALMENTE DIFFERENTE rispetto ai 10 topic precedenti\n\n"
            ."Output obbligatorio:\n"
            ."- restituisci SOLO JSON valido con schema {\"topic\":\"...\"}\n"
            ."- nessun testo extra fuori dal JSON\n\n"
            ."Contesto (ultime 30 generic_news):\n"
            .($newsContext !== '' ? $newsContext : '- Nessun contesto disponibile')
            ."\n\nUltimi 10 topic da NON ripetere (genera qualcosa COMPLETAMENTE DIVERSO):\n"
            .($recentTopicsContext !== '' ? $recentTopicsContext : '- Nessun tema recente');

        $lastTopic = '';
        $lastLength = 0;

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $attemptPrompt = $baseUserPrompt;
            if ($attempt > 1) {
                $attemptPrompt .= "\n\nLa risposta precedente era fuori vincolo ({$lastLength} caratteri).";
            }

            try {
                $response = $this->aiService->generateJson(
                    $attemptPrompt,
                    'x-ai/grok-4.1-fast',
                    'commands/generate-livelia-topics'
                );
            } catch (\Exception) {
                continue;
            }

            $content = trim((string) ($response['topic'] ?? ''));
            if ($content === '') {
                continue;
            }

            $topic = $this->normalizeTopic($content);
            $length = $this->stringLength($topic);

            $lastTopic = $topic;
            $lastLength = $length;

            if ($length >= 50 && $length <= 100) {
                return $topic;
            }
        }

        throw new RuntimeException(
            "Impossibile generare un topic valido tra 50 e 100 caratteri. Ultimo output: \"{$lastTopic}\" ({$lastLength} caratteri)."
        );
    }

    private function normalizeTopic(string $raw): string
    {
        $topic = trim(Str::before($raw, "\n"));
        $topic = preg_replace('/^["\'`«“]+|["\'`»”]+$/u', '', $topic) ?? $topic;
        $topic = preg_replace('/^\s*[\-\*\d\.\)\(]+\s*/u', '', $topic) ?? $topic;
        $topic = preg_replace('/\s+/u', ' ', $topic) ?? $topic;
        $topic = trim($topic);

        if ($topic === '') {
            return $topic;
        }

        if (! str_ends_with($topic, '?')) {
            $topic = rtrim($topic, ".!;: \t\n\r\0\x0B").'?';
        }

        return $topic;
    }

    private function stringLength(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    }

    private function resolveFirstExistingColumn(array $columns, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $columns, true)) {
                return $candidate;
            }
        }

        return null;
    }

    private function firstNonEmptyValueFromObject(object $row, array $candidates): string
    {
        foreach ($candidates as $candidate) {
            if (! property_exists($row, $candidate)) {
                continue;
            }

            $value = trim((string) ($row->{$candidate} ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }
}
