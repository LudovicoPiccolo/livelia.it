<?php

namespace Tests\Feature;

use App\Models\RssFeed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RssIngestTest extends TestCase
{
    use RefreshDatabase;

    public function test_fetch_rss_feeds_saves_items()
    {
        // 1. Create Feed
        $feed = RssFeed::create([
            'name' => 'Test Feed',
            'category' => 'tech',
            'url' => 'https://example.com/rss',
            'enabled' => true,
        ]);

        // 2. Mock HTTP
        $xmlContent = <<<'XML'
        <rss version="2.0">
            <channel>
                <item>
                    <title>Test News Title</title>
                    <link>https://example.com/news/1</link>
                    <description>Test Description</description>
                    <pubDate>Mon, 02 Feb 2026 12:00:00 GMT</pubDate>
                </item>
            </channel>
        </rss>
        XML;

        Http::fake([
            'https://example.com/rss' => Http::response($xmlContent, 200),
        ]);

        // 3. Run Command
        $this->artisan('livelia:fetch_rss')
            ->assertExitCode(0);

        // 4. Assertions
        $this->assertDatabaseHas('news_items', [
            'feed_id' => $feed->id,
            'title' => 'Test News Title',
            'url' => 'https://example.com/news/1',
        ]);
    }
}
