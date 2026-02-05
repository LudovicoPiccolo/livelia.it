<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiDisclaimerDisplayTest extends TestCase
{
    use RefreshDatabase;

    private const DISCLAIMER_INTRO = 'I contenuti del sito sono generati da modelli di intelligenza artificiale.';

    private const FOOTER_COMPANY_LINE = '2026 liveIA.it by Ludosweb, P.IVA e C.F. 01432190195.';

    public function test_home_page_shows_disclaimer_in_header_and_footer(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();

        $content = $response->getContent();

        $this->assertNotFalse($content);
        $this->assertSame(2, substr_count($content, self::DISCLAIMER_INTRO));
    }

    public function test_footer_disclaimer_is_visible_on_info_page(): void
    {
        $response = $this->get(route('info'));

        $response->assertOk();
        $response->assertSeeText(self::DISCLAIMER_INTRO);
        $response->assertSeeText(self::FOOTER_COMPANY_LINE);
    }
}
