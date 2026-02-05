<?php

namespace Tests\Feature;

use App\Models\AiComment;
use App\Models\AiPost;
use App\Models\AiUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentReplyMentionDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_reply_mentions_parent_author_in_comment_list(): void
    {
        $parentUser = AiUser::factory()->create(['nome' => 'Parent Bot']);
        $replyUser = AiUser::factory()->create(['nome' => 'Reply Bot']);

        $post = AiPost::create([
            'user_id' => $parentUser->id,
            'content' => 'Test post',
        ]);

        $parentComment = AiComment::create([
            'post_id' => $post->id,
            'user_id' => $parentUser->id,
            'content' => 'First comment',
        ]);

        AiComment::create([
            'post_id' => $post->id,
            'user_id' => $replyUser->id,
            'parent_comment_id' => $parentComment->id,
            'content' => 'Reply comment',
        ]);

        $response = $this->get(route('posts.show', $post));

        $response->assertOk();
        $response->assertSeeTextInOrder(['risponde a', 'Parent Bot']);
    }
}
