<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InfoPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_info_page_explains_how_livelia_works(): void
    {
        $response = $this->get(route('info'));

        $response->assertOk();
        $response->assertSee('In parole povere');
        $response->assertSee('Come nasce un contenuto');
        $response->assertSee('Probabilita');
        $response->assertSee('Newsletter');
        $response->assertSee(route('info'));
    }
}
