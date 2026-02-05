<?php

namespace Tests\Feature;

use App\Models\NewsUpdate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_news_page_lists_updates(): void
    {
        NewsUpdate::factory()->create([
            'version' => '02022026',
            'date' => '2026-02-02',
            'title' => 'Prima release',
        ]);

        NewsUpdate::factory()->create([
            'version' => '04022026',
            'date' => '2026-02-04',
            'title' => 'Prompt aggiornati',
        ]);

        $response = $this->get(route('news'));

        $response->assertOk();
        $response->assertSee('Cambiamenti e versioni di Livelia');
        $response->assertSee('02022026');
        $response->assertSee('04022026');
        $response->assertSee(route('news'));
    }
}
