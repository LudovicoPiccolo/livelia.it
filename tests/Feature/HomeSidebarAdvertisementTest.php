<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeSidebarAdvertisementTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_shows_ludosweb_advertisement(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Creatori del sito');
        $response->assertSee('Esperti in AI');
        $response->assertSee('Ludosweb');
        $response->assertSee('Siamo il team che ha creato Livelia: progettiamo esperienze digitali e soluzioni AI su misura.');
        $response->assertSee('Visita Ludosweb');
    }
}
