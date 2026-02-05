<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ManageNewsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'livelia:news
                            {--add : Aggiungi una news}
                            {--remove : Rimuovi una news}
                            {--news-version= : Versione (es. 04022026)}
                            {--date= : Data ISO (YYYY-MM-DD)}
                            {--title= : Titolo}
                            {--summary= : Riassunto}
                            {--details=* : Dettagli (puoi ripeterlo piu volte)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gestisci le news di Livelia (aggiungi o rimuovi dal JSON)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $shouldAdd = (bool) $this->option('add');
        $shouldRemove = (bool) $this->option('remove');

        if ($shouldAdd === $shouldRemove) {
            $this->error('Specifica esattamente una azione: --add oppure --remove.');

            return 1;
        }

        if ($shouldAdd) {
            return $this->handleAdd();
        }

        return $this->handleRemove();
    }

    private function handleAdd(): int
    {
        $version = trim((string) $this->option('news-version'));
        $date = trim((string) $this->option('date'));
        $title = trim((string) $this->option('title'));
        $summary = trim((string) $this->option('summary'));
        $details = $this->normalizeDetails($this->option('details'));

        if ($version === '' || $date === '' || $title === '' || $summary === '') {
            $this->error('Per aggiungere una news servono: --news-version, --date, --title, --summary.');

            return 1;
        }

        if (! $this->isValidDate($date)) {
            $this->error('La data deve essere nel formato YYYY-MM-DD.');

            return 1;
        }

        if ($this->hasVersion($version)) {
            $this->error("La versione {$version} esiste gia.");

            return 1;
        }

        \App\Models\NewsUpdate::query()->create([
            'version' => $version,
            'date' => $date,
            'title' => $title,
            'summary' => $summary,
            'details' => $details,
        ]);

        $this->info("News aggiunta: versione {$version}.");

        return 0;
    }

    private function handleRemove(): int
    {
        $version = trim((string) $this->option('news-version'));

        if ($version === '') {
            $this->error('Per rimuovere una news serve: --news-version.');

            return 1;
        }

        $newsItem = \App\Models\NewsUpdate::query()
            ->where('version', $version)
            ->first();

        if (! $newsItem) {
            $this->error("Nessuna news trovata con versione {$version}.");

            return 1;
        }

        $newsItem->delete();
        $this->info("News rimossa: versione {$version}.");

        return 0;
    }

    private function hasVersion(string $version): bool
    {
        return \App\Models\NewsUpdate::query()
            ->where('version', $version)
            ->exists();
    }

    private function isValidDate(string $date): bool
    {
        $parsed = \DateTime::createFromFormat('Y-m-d', $date);

        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }

    /**
     * @param  array<int, string>|string|null  $details
     * @return array<int, string>
     */
    private function normalizeDetails(array|string|null $details): array
    {
        if ($details === null) {
            return [];
        }

        if (is_string($details)) {
            $details = [$details];
        }

        if (count($details) === 1 && str_contains($details[0], ';')) {
            $details = array_map('trim', explode(';', $details[0]));
        }

        return array_values(array_filter(array_map('trim', $details), fn (string $value): bool => $value !== ''));
    }
}
