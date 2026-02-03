<?php

namespace Tests\Feature;

use App\Models\AiComment;
use App\Models\AiPost;
use App\Models\AiUser;
use App\Services\AiActionDeciderService;
use App\Services\AiService;
use App\Services\AiTargetSelectorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AiSocialLogicTest extends TestCase
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

    public function test_post_rate_limit_forces_like_instead()
    {
        $user = $this->createTestUser([
            'energia_sociale' => 100,
            'cooldown_until' => null,
        ]);

        // 1. Create a recent post for this user (simulating 10 mins ago)
        AiPost::create([
            'user_id' => $user->id,
            'content' => 'Recent post',
        ]);

        // 2. Mock Decider to force NEW_POST
        $deciderMock = Mockery::mock(AiActionDeciderService::class);
        $deciderMock->shouldReceive('decideAction')->andReturn('NEW_POST');
        $this->instance(AiActionDeciderService::class, $deciderMock);

        // 3. Mock AI Service to fail if called (should not be called for post)
        $aiServiceMock = Mockery::mock(AiService::class);
        $aiServiceMock->shouldReceive('generateJson')->never();
        $this->instance(AiService::class, $aiServiceMock);

        // 4. Mock Target Selector (for fallback like)
        // We need a post to like
        $otherUser = $this->createTestUser(['nome' => 'OtherUser', 'energia_sociale' => 0]);
        $otherPost = AiPost::create(['user_id' => $otherUser->id, 'content' => 'Other post']);

        // 5. Run command
        $this->artisan('livelia:social_tick')
            ->assertExitCode(0);

        // 6. Assertions
        $this->assertCount(1, AiPost::where('user_id', $user->id)->get());

        $this->assertDatabaseHas('ai_reactions', [
            'user_id' => $user->id,
            'target_id' => $otherPost->id,
            'reaction_type' => 'like',
        ]);
    }

    public function test_consecutive_comments_are_prevented()
    {
        $user = $this->createTestUser(['nome' => 'U1']);
        $otherUser = $this->createTestUser(['nome' => 'U2']);

        // Post by other user
        $post = AiPost::create(['user_id' => $otherUser->id, 'content' => 'Topic']);

        // Comment by OUR user (last comment)
        AiComment::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'content' => 'My last word',
        ]);

        // Service should NOT return this post for commenting
        $service = app(AiTargetSelectorService::class);
        $candidates = $service->findPostsToComment($user);

        $this->assertFalse($candidates->contains('id', $post->id));
    }

    public function test_nothing_action_falls_back_to_like()
    {
        $user = $this->createTestUser(['nome' => 'U3', 'energia_sociale' => 50]);
        // Make other user ineligible (energy=0) so only U3 is picked
        $otherUser = $this->createTestUser(['nome' => 'U4', 'energia_sociale' => 0]);
        $otherPost = AiPost::create(['user_id' => $otherUser->id, 'content' => 'Target']);
        AiComment::create(['user_id' => $otherUser->id, 'post_id' => $otherPost->id, 'content' => 'C']);

        $deciderMock = Mockery::mock(AiActionDeciderService::class);
        $deciderMock->shouldReceive('decideAction')->andReturn('NOTHING');
        $this->instance(AiActionDeciderService::class, $deciderMock);

        // Run tick
        $this->artisan('livelia:social_tick');

        // Should have reacted
        // Should have reacted
        $this->assertDatabaseHas('ai_reactions', [
            'user_id' => $user->id,
            // 'target_type' => 'post' // Could be post or comment now
        ]);
    }

    public function test_reply_includes_thread_history()
    {
        $user = $this->createTestUser(['nome' => 'Replier']);
        $op = $this->createTestUser(['nome' => 'OP']);
        $commenter = $this->createTestUser(['nome' => 'Commenter']);

        $post = AiPost::create(['user_id' => $op->id, 'content' => 'Root Post']);
        $c1 = AiComment::create(['user_id' => $commenter->id, 'post_id' => $post->id, 'content' => 'First level']);
        // Reply to C1 (simulating thread: Post -> C1 -> C2)
        $c2 = AiComment::create([
            'user_id' => $op->id,
            'post_id' => $post->id,
            'parent_comment_id' => $c1->id,
            'content' => 'Second level', // We will reply to this
        ]);

        // Mock Target Selector to pick C2
        $targetSelectorMock = Mockery::mock(AiTargetSelectorService::class);
        $eloquentCollection = new \Illuminate\Database\Eloquent\Collection([$c2]);
        $targetSelectorMock->shouldReceive('findCommentsToReply')->andReturn($eloquentCollection);
        $targetSelectorMock->shouldReceive('findPostsToLike')->andReturn(new \Illuminate\Database\Eloquent\Collection([])); // Fallback avoidance
        $this->instance(AiTargetSelectorService::class, $targetSelectorMock);

        // Mock Decider to force REPLY
        $deciderMock = Mockery::mock(AiActionDeciderService::class);
        $deciderMock->shouldReceive('decideAction')->andReturn('REPLY');
        $this->instance(AiActionDeciderService::class, $deciderMock);

        // Mock AI Service to verify prompt contains history
        $aiServiceMock = Mockery::mock(AiService::class);
        $aiServiceMock->shouldReceive('generateJson')
            ->once()
            ->withArgs(function ($prompt) {
                // Check if prompt contains thread history
                return str_contains($prompt, 'First level') &&
                       str_contains($prompt, 'Commenter: "First level"');
            })
            ->andReturn(['content' => 'My Reply']);
        $this->instance(AiService::class, $aiServiceMock);

        $this->artisan('livelia:social_tick');
    }

    public function test_post_includes_user_history()
    {
        $user = $this->createTestUser(['nome' => 'Poster']);

        // Create past history (Must be older than 1h to pass rate limit check)
        $p1 = AiPost::create([
            'user_id' => $user->id,
            'content' => 'Old Post 1',
        ]);
        AiPost::whereKey($p1->id)->update([
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ]);
        $reactor = $this->createTestUser(['nome' => 'Reactor', 'energia_sociale' => 0]);
        AiComment::create(['user_id' => $reactor->id, 'post_id' => $p1->id, 'content' => 'Reaction to old post']);

        // Mock Decider -> NEW_POST
        $deciderMock = Mockery::mock(AiActionDeciderService::class);
        $deciderMock->shouldReceive('decideAction')->andReturn('NEW_POST');
        $this->instance(AiActionDeciderService::class, $deciderMock);

        // Mock Target Selector fallback avoidance
        $targetSelectorMock = Mockery::mock(AiTargetSelectorService::class);
        $targetSelectorMock->shouldReceive('findPostsToLike')->andReturn(new \Illuminate\Database\Eloquent\Collection([]));
        $this->instance(AiTargetSelectorService::class, $targetSelectorMock);

        // Mock AI Service
        $aiServiceMock = Mockery::mock(AiService::class);
        $aiServiceMock->shouldReceive('generateJson')
            ->once()
            ->withArgs(function ($prompt) {
                return str_contains($prompt, 'Old Post 1') &&
                       str_contains($prompt, 'Reaction to old post');
            })
            ->andReturn(['content' => 'New Post Content']);
        $this->instance(AiService::class, $aiServiceMock);

        // Check count to debug
        // dump(AiPost::where('created_at', '>=', now()->subHour())->count());
        $this->artisan('livelia:social_tick');
    }

    public function test_force_new_post_after_20_null_events()
    {
        $user = $this->createTestUser(['nome' => 'StuckBot', 'energia_sociale' => 100]);

        // 1. Create 20 skipped events
        for ($i = 0; $i < 20; $i++) {
            \App\Models\AiEventLog::create([
                'user_id' => $user->id,
                'event_type' => 'LIKE_POST',
                'meta_json' => ['status' => 'skipped', 'reason' => 'No posts'],
            ]);
        }

        // 2. Simulate User hitting Global Rate Limit (create a post 5 mins ago)
        $otherUser = $this->createTestUser(['nome' => 'Other', 'energia_sociale' => 0]);
        AiPost::create([
            'user_id' => $otherUser->id,
            'content' => 'Existing post',
        ]);

        // 3. Mock Decider to return NOTHING or LIKE_POST (should be overridden)
        $deciderMock = Mockery::mock(AiActionDeciderService::class);
        $deciderMock->shouldReceive('decideAction')->andReturn('NOTHING');
        // Note: The command logic checks for null events BEFORE calling decideAction,
        // so decideAction might not be called at all if forced.
        // But if I mock it, it's safer to not expect it, or allow it if my logic allows.
        // In my implementation: "if (! $forcedPost) { decider->decideAction... }"
        // So decider should NOT be called.
        $this->instance(AiActionDeciderService::class, $deciderMock);

        // 4. Mock AI Service to succeed
        $aiServiceMock = Mockery::mock(AiService::class);
        $aiServiceMock->shouldReceive('generateJson')
            ->once()
            ->andReturn(['content' => 'Forced Post Content']);
        $this->instance(AiService::class, $aiServiceMock);

        // 5. Run command
        $this->artisan('livelia:social_tick')
            ->expectsOutput("User {$user->id} has 20 consecutive null events. Forcing NEW_POST.")
            ->assertExitCode(0);

        // 6. Assertions
        $this->assertCount(1, AiPost::where('user_id', $user->id)->where('content', 'Forced Post Content')->get());
    }
}
