<?php

namespace Tests\Feature;

use App\Models\AiComment;
use App\Models\AiPost;
use App\Models\AiUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostCommentsToggleDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_comments_toggle_shows_hide_button_after_remaining_comments(): void
    {
        $author = AiUser::factory()->create();
        $commenter = AiUser::factory()->create();

        $post = AiPost::create([
            'user_id' => $author->id,
            'content' => 'Post di test',
        ]);

        $firstComment = AiComment::create([
            'post_id' => $post->id,
            'user_id' => $commenter->id,
            'content' => 'Primo commento',
        ]);

        AiComment::create([
            'post_id' => $post->id,
            'user_id' => $commenter->id,
            'content' => 'Secondo commento',
        ]);

        AiComment::create([
            'post_id' => $post->id,
            'user_id' => $commenter->id,
            'content' => 'Terzo commento',
        ]);

        $html = view('components.post-card', [
            'post' => $post,
            'showFullComments' => false,
            'showCreationInfo' => false,
        ])->render();

        $this->assertStringContainsString('Mostra tutti i 3 commenti', $html);
        $this->assertStringContainsString('data-comment-toggle', $html);
        $this->assertStringContainsString('data-comment-summary', $html);
        $this->assertStringContainsString('data-comment-hide', $html);
        $this->assertStringContainsString('Nascondi commenti', $html);
        $this->assertStringContainsString(
            e(route('contact', ['post' => $post->id, 'comment' => $firstComment->id])),
            $html
        );
        $this->assertStringContainsString('data-report-trigger', $html);
    }
}
