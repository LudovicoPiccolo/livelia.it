<?php

namespace Tests\Feature;

use App\Models\AiPost;
use App\Models\AiReaction;
use App\Models\AiUser;
use App\Models\User;
use App\Models\UserReaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LikesTooltipDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_like_tooltip_rendered_when_post_has_likes(): void
    {
        $author = AiUser::factory()->create();
        $aiReactor = AiUser::factory()->create();
        $humanUser = User::factory()->create();

        $post = AiPost::create([
            'user_id' => $author->id,
            'content' => 'Post con tooltip',
        ]);

        AiReaction::create([
            'user_id' => $aiReactor->id,
            'target_type' => 'post',
            'target_id' => $post->id,
            'reaction_type' => 'like',
        ]);

        UserReaction::create([
            'user_id' => $humanUser->id,
            'target_type' => 'post',
            'target_id' => $post->id,
            'reaction_type' => 'like',
        ]);

        $response = $this->get(route('posts.show', $post));

        $response->assertOk();
        $response->assertSee('data-like-tooltip');
        $response->assertSee('Mi piace AI: 1');
        $response->assertSee('Mi piace Umani: 1');
    }

    public function test_like_tooltip_not_rendered_when_post_has_zero_likes(): void
    {
        $author = AiUser::factory()->create();

        $post = AiPost::create([
            'user_id' => $author->id,
            'content' => 'Post senza like',
        ]);

        $response = $this->get(route('posts.show', $post));

        $response->assertOk();
        $response->assertDontSee('data-like-tooltip');
    }

    public function test_like_tooltip_shows_only_ai_likes_when_no_human_likes(): void
    {
        $author = AiUser::factory()->create();
        $aiReactor = AiUser::factory()->create();

        $post = AiPost::create([
            'user_id' => $author->id,
            'content' => 'Post solo AI like',
        ]);

        AiReaction::create([
            'user_id' => $aiReactor->id,
            'target_type' => 'post',
            'target_id' => $post->id,
            'reaction_type' => 'like',
        ]);

        $response = $this->get(route('posts.show', $post));

        $response->assertOk();
        $response->assertSee('data-like-tooltip');
        $response->assertSee('Mi piace AI: 1');
        $response->assertSee('Mi piace Umani: 0');
    }
}
