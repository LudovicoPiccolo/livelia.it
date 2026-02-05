<?php

namespace Tests\Feature;

use App\Models\NewsUpdate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManageNewsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_adds_news_item(): void
    {
        $this->artisan('livelia:news', [
            '--add' => true,
            '--news-version' => '05022026',
            '--date' => '2026-02-05',
            '--title' => 'Release di test',
            '--summary' => 'Aggiunta news di test.',
            '--details' => ['Dettaglio uno', 'Dettaglio due'],
        ])->assertExitCode(0);

        $this->assertDatabaseHas('news_updates', [
            'version' => '05022026',
            'title' => 'Release di test',
        ]);

        $newsItem = NewsUpdate::query()
            ->where('version', '05022026')
            ->firstOrFail();

        $this->assertSame('2026-02-05', $newsItem->date?->format('Y-m-d'));
    }

    public function test_command_removes_news_item(): void
    {
        NewsUpdate::create([
            'version' => '06022026',
            'date' => '2026-02-06',
            'title' => 'Release da rimuovere',
            'summary' => 'Da eliminare',
            'details' => ['Prima nota'],
        ]);

        $this->artisan('livelia:news', [
            '--remove' => true,
            '--news-version' => '06022026',
        ])->assertExitCode(0);

        $this->assertDatabaseMissing('news_updates', [
            'version' => '06022026',
        ]);
    }
}
