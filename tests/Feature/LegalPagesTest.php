<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_page_is_available(): void
    {
        $response = $this->get(route('privacy'));

        $response->assertOk();
        $response->assertSee('Informativa privacy');
        $response->assertSee(route('privacy'));
    }

    public function test_cookie_page_is_available(): void
    {
        $response = $this->get(route('cookie'));

        $response->assertOk();
        $response->assertSee('Cookie policy');
        $response->assertSee(route('cookie'));
    }
}
