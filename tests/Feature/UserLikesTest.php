<?php

namespace Tests\Feature;

use App\Models\AiPost;
use App\Models\AiReaction;
use App\Models\AiUser;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserLikesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_like_and_unlike_post(): void
    {
        $user = User::factory()->create();
        $author = AiUser::factory()->create();

        $post = AiPost::create([
            'user_id' => $author->id,
            'content' => 'Post da like',
        ]);

        $response = $this->actingAs($user)->post(route('likes.posts.toggle', $post));

        $response->assertStatus(302);
        $this->assertDatabaseHas('user_reactions', [
            'user_id' => $user->id,
            'target_type' => 'post',
            'target_id' => $post->id,
            'reaction_type' => 'like',
        ]);

        $likesPage = $this->actingAs($user)->get(route('account.likes'));
        $likesPage->assertStatus(200);
        $likesPage->assertSee('Post da like');

        $this->actingAs($user)->post(route('likes.posts.toggle', $post));

        $this->assertDatabaseMissing('user_reactions', [
            'user_id' => $user->id,
            'target_type' => 'post',
            'target_id' => $post->id,
            'reaction_type' => 'like',
        ]);
    }

    public function test_user_can_like_and_unlike_chat_message(): void
    {
        $user = User::factory()->create();
        $message = ChatMessage::factory()->create([
            'content' => 'Messaggio chat da like',
        ]);

        $response = $this->actingAs($user)->post(route('likes.chat.toggle', $message));

        $response->assertStatus(302);
        $this->assertDatabaseHas('user_reactions', [
            'user_id' => $user->id,
            'target_type' => 'chat',
            'target_id' => $message->id,
            'reaction_type' => 'like',
        ]);

        $likesPage = $this->actingAs($user)->get(route('account.likes'));
        $likesPage->assertStatus(200);
        $likesPage->assertSee('Messaggio chat da like');
        $likesPage->assertSee('Discussioni');

        $this->actingAs($user)->post(route('likes.chat.toggle', $message));

        $this->assertDatabaseMissing('user_reactions', [
            'user_id' => $user->id,
            'target_type' => 'chat',
            'target_id' => $message->id,
            'reaction_type' => 'like',
        ]);
    }

    public function test_post_like_toggle_returns_json_with_separated_counts(): void
    {
        $user = User::factory()->create();
        $author = AiUser::factory()->create();
        $aiReactor = AiUser::factory()->create();

        $post = AiPost::create([
            'user_id' => $author->id,
            'content' => 'Post con like AI e umano',
        ]);

        AiReaction::create([
            'user_id' => $aiReactor->id,
            'target_type' => 'post',
            'target_id' => $post->id,
            'reaction_type' => 'like',
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('likes.posts.toggle', $post));

        $response->assertStatus(200)
            ->assertJson([
                'liked' => true,
                'human_likes_count' => 1,
                'ai_likes_count' => 1,
            ]);

        $response = $this->actingAs($user)
            ->postJson(route('likes.posts.toggle', $post));

        $response->assertStatus(200)
            ->assertJson([
                'liked' => false,
                'human_likes_count' => 0,
                'ai_likes_count' => 1,
            ]);
    }

    public function test_chat_like_toggle_returns_json_with_human_count_only(): void
    {
        $user = User::factory()->create();
        $message = ChatMessage::factory()->create([
            'content' => 'Messaggio chat JSON',
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('likes.chat.toggle', $message));

        $response->assertStatus(200)
            ->assertJson([
                'liked' => true,
                'human_likes_count' => 1,
            ]);

        $this->assertArrayNotHasKey('ai_likes_count', $response->json());

        $response = $this->actingAs($user)
            ->postJson(route('likes.chat.toggle', $message));

        $response->assertStatus(200)
            ->assertJson([
                'liked' => false,
                'human_likes_count' => 0,
            ]);
    }

    public function test_ai_likes_are_unaffected_by_human_like_toggle(): void
    {
        $user = User::factory()->create();
        $author = AiUser::factory()->create();
        $aiReactor1 = AiUser::factory()->create();
        $aiReactor2 = AiUser::factory()->create();

        $post = AiPost::create([
            'user_id' => $author->id,
            'content' => 'Post isolamento conteggio AI',
        ]);

        AiReaction::create([
            'user_id' => $aiReactor1->id,
            'target_type' => 'post',
            'target_id' => $post->id,
            'reaction_type' => 'like',
        ]);

        AiReaction::create([
            'user_id' => $aiReactor2->id,
            'target_type' => 'post',
            'target_id' => $post->id,
            'reaction_type' => 'like',
        ]);

        $like = $this->actingAs($user)
            ->postJson(route('likes.posts.toggle', $post));

        $like->assertJson(['ai_likes_count' => 2, 'human_likes_count' => 1]);

        $unlike = $this->actingAs($user)
            ->postJson(route('likes.posts.toggle', $post));

        $unlike->assertJson(['ai_likes_count' => 2, 'human_likes_count' => 0]);
    }
}
