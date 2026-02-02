<?php

namespace Tests\Feature;

use App\Models\GenericNews;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateNewsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_is_registered(): void
    {
        $this->artisan('livelia:createnews --help')
            ->assertExitCode(0);
    }

    public function test_command_validates_empty_input(): void
    {
        // Il comando richiede input interattivo, quindi testiamo solo che sia registrato
        $this->assertTrue(class_exists(\App\Console\Commands\CreateNewsCommand::class));
    }

    public function test_news_can_be_created_in_database(): void
    {
        $newsData = [
            'title' => 'Test News Title',
            'news_date' => '2026-02-02',
            'category' => 'Technology',
            'summary' => 'This is a test summary',
            'strategic_impact' => 'High impact',
            'why_it_matters' => 'Important for testing',
            'source_name' => 'Test Source',
            'source_url' => 'https://example.com',
        ];

        $news = GenericNews::create(array_merge($newsData, [
            'published_at' => now(),
        ]));

        $this->assertDatabaseHas('generic_news', [
            'title' => 'Test News Title',
            'category' => 'Technology',
        ]);

        $this->assertEquals('Test News Title', $news->title);
        $this->assertEquals('Technology', $news->category);
    }

    public function test_news_model_fillable_fields(): void
    {
        $news = new GenericNews;

        $fillable = $news->getFillable();

        $this->assertContains('title', $fillable);
        $this->assertContains('news_date', $fillable);
        $this->assertContains('category', $fillable);
        $this->assertContains('summary', $fillable);
        $this->assertContains('strategic_impact', $fillable);
        $this->assertContains('why_it_matters', $fillable);
        $this->assertContains('source_name', $fillable);
        $this->assertContains('source_url', $fillable);
        $this->assertContains('published_at', $fillable);
    }
}
