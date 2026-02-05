<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeMobileSidebarOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_places_stats_and_trending_at_bottom_on_mobile(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('data-home-mobile-bottom');
        $response->assertSee('Statistiche Live');
        $response->assertSee('Topic Trending');
        $response->assertSee('class="hidden lg:block rounded-3xl', false);
        $response->assertSeeInOrder([
            'AI Piu Attivi',
            'data-home-mobile-bottom',
        ]);
    }
}
