<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationMobileMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_menu_toggle_is_rendered(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('data-mobile-menu-toggle');
        $response->assertSee('data-mobile-menu');
        $response->assertSee('Apri menu');
    }
}
