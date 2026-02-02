<?php

namespace Tests\Feature;

use App\Models\AiComment;
use App\Models\AiPost;
use App\Models\AiUser;
use App\Services\AiTargetSelectorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiLikeCommentConstraintsTest extends TestCase
{
    use RefreshDatabase;

    private function createTestUser(array $attributes = []): AiUser
    {
        return AiUser::create(array_merge([
            'nome' => 'TestBot',
            'orientamento_sessuale' => 'etero',
            'sesso' => 'M', // or 'F' or 'NB'
            'lavoro' => 'Tester',
            'orientamento_politico' => 'neutro',
            'passioni' => [['tema' => 'Tech', 'peso' => 100]],
            'bias_informativo' => 'Nessuno',
            'personalita' => 'Standard',
            'stile_comunicativo' => 'Standard',
            'atteggiamento_verso_attualita' => 'Neutro',
            'propensione_al_conflitto' => 50,
            'sensibilita_ai_like' => 50,
            'ritmo_attivita' => 'medio',
            'generated_by_model' => 'test',
            'source_prompt_file' => 'test.md',
            'energia_sociale' => 100,
            'umore' => 'Neutro',
            'bisogno_validazione' => 50,
        ], $attributes));
    }

    public function test_user_cannot_like_own_post()
    {
        // Setup
        $user = $this->createTestUser(['nome' => 'Me']);
        $myPost = AiPost::create([
            'user_id' => $user->id,
            'content' => 'My own post',
        ]);

        $otherUser = $this->createTestUser(['nome' => 'Other']);
        $otherPost = AiPost::create([
            'user_id' => $otherUser->id,
            'content' => 'Other post',
        ]);

        // Mock AffinityService (dependency of TargetSelector)
        // We can just use the real one or a partial mock, but let's just resolve the real one for now as it's logic is fine.
        // Actually, let's just make sure affinity calc doesn't crash.
        // Or we can mock it to always return high affinity so sorting doesn't hide things.

        $targetSelector = app(AiTargetSelectorService::class);

        // Action
        $postsToLike = $targetSelector->findPostsToLike($user);

        // Assert
        $this->assertFalse($postsToLike->contains('id', $myPost->id), 'List of posts to like contains my own post!');
        $this->assertTrue($postsToLike->contains('id', $otherPost->id), 'List of posts to like DOES NOT contain other user post.');
    }

    public function test_user_cannot_comment_directly_on_own_post()
    {
        // Setup
        $user = $this->createTestUser(['nome' => 'Me']);
        $myPost = AiPost::create([
            'user_id' => $user->id,
            'content' => 'My own post',
        ]);

        $otherUser = $this->createTestUser(['nome' => 'Other']);
        $otherPost = AiPost::create([
            'user_id' => $otherUser->id,
            'content' => 'Other post',
        ]);

        $targetSelector = app(AiTargetSelectorService::class);

        // Action: Find posts to comment (top-level)
        $postsToComment = $targetSelector->findPostsToComment($user);

        // Assert
        $this->assertFalse($postsToComment->contains('id', $myPost->id), 'List of posts to comment contains my own post!');
        $this->assertTrue($postsToComment->contains('id', $otherPost->id), 'List of posts to comment DOES NOT contain other user post.');
    }

    public function test_user_can_reply_to_comment_on_own_post()
    {
        // Setup
        $me = $this->createTestUser(['nome' => 'Me']);
        $myPost = AiPost::create([
            'user_id' => $me->id,
            'content' => 'My own post',
        ]);

        $otherUser = $this->createTestUser(['nome' => 'Other']);

        // Someone else commented on MY post
        $commentOnMyPost = AiComment::create([
            'user_id' => $otherUser->id,
            'post_id' => $myPost->id,
            'content' => 'Nice post!',
        ]);

        $targetSelector = app(AiTargetSelectorService::class);

        // Action: Find comments to reply to
        $commentsToReply = $targetSelector->findCommentsToReply($me);

        // Assert
        $this->assertTrue($commentsToReply->contains('id', $commentOnMyPost->id), 'Should be able to reply to a comment on my own post (if made by someone else).');
    }

    public function test_user_cannot_reply_to_own_comment()
    {
        // Setup
        $me = $this->createTestUser(['nome' => 'Me']);
        $otherUser = $this->createTestUser(['nome' => 'Other']);

        $post = AiPost::create([
            'user_id' => $otherUser->id,
            'content' => 'Some post',
        ]);

        // I commented
        $myComment = AiComment::create([
            'user_id' => $me->id,
            'post_id' => $post->id,
            'content' => 'My comment',
        ]);

        $targetSelector = app(AiTargetSelectorService::class);

        // Action
        $commentsToReply = $targetSelector->findCommentsToReply($me);

        // Assert
        $this->assertFalse($commentsToReply->contains('id', $myComment->id), 'Should NOT be able to reply to my own comment.');
    }
}
