<?php

namespace Tests\Unit;

use Tests\TestCase;

class PromptContentTest extends TestCase
{
    public function test_create_post_prompt_deprioritizes_environment_topics(): void
    {
        $content = file_get_contents(base_path('.prompt/create_post.md'));

        $this->assertIsString($content);
        $this->assertStringContainsString('Bilanciamento temi', $content);
        $this->assertStringContainsString('ambiente/sostenibilita/risparmio energetico/ecologia', $content);
    }

    public function test_fetch_generic_news_prompt_caps_environment_items(): void
    {
        $content = file_get_contents(base_path('.prompt/fetch_generic_news.md'));

        $this->assertIsString($content);
        $this->assertStringContainsString('Massimo 1-2 notizie', $content);
        $this->assertStringContainsString('Ambiente e sostenibilita', $content);
    }

    public function test_create_chat_message_claude_prompt_requires_argument_quality(): void
    {
        $content = file_get_contents(base_path('.prompt/create_chat_message_claude.md'));

        $this->assertIsString($content);
        $this->assertStringContainsString('Qualità argomentativa', $content);
        $this->assertStringContainsString('premessa implicita', $content);
    }

    public function test_create_comment_claude_prompt_requires_argument_move(): void
    {
        $content = file_get_contents(base_path('.prompt/create_comment_claude.md'));

        $this->assertIsString($content);
        $this->assertStringContainsString('Mossa argomentativa minima', $content);
        $this->assertStringContainsString('trade-off', $content);
    }

    public function test_create_post_claude_prompt_requires_min_structure(): void
    {
        $content = file_get_contents(base_path('.prompt/create_post_claude.md'));

        $this->assertIsString($content);
        $this->assertStringContainsString('STRUTTURA MINIMA', $content);
        $this->assertStringContainsString('2-5 frasi', $content);
    }

    public function test_create_user_claude_prompt_requires_posizione_intellettuale(): void
    {
        $content = file_get_contents(base_path('.prompt/create_user_claude.md'));

        $this->assertIsString($content);
        $this->assertStringContainsString('posizione_intellettuale', $content);
        $this->assertStringContainsString('tesi testabile', $content);
    }

    public function test_fetch_generic_news_claude_prompt_requires_tradeoff_angle(): void
    {
        $content = file_get_contents(base_path('.prompt/fetch_generic_news_claude.md'));

        $this->assertIsString($content);
        $this->assertStringContainsString('trade-off', $content);
        $this->assertStringContainsString('qualità è prioritaria', $content);
    }

    public function test_summarize_news_claude_prompt_emphasizes_depth(): void
    {
        $content = file_get_contents(base_path('.prompt/summarize_news_claude.md'));

        $this->assertIsString($content);
        $this->assertStringContainsString('depth_potential', $content);
        $this->assertStringContainsString('pensiero genuino', $content);
    }
}
