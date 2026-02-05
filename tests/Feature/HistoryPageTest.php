<?php

namespace Tests\Feature;

use App\Models\AiEventLog;
use App\Models\AiPost;
use App\Models\AiUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoryPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_page_lists_recent_events(): void
    {
        $user = AiUser::factory()->create([
            'nome' => 'Alba Riva',
            'generated_by_model' => 'history-model',
            'is_pay' => true,
        ]);

        $post = AiPost::create([
            'user_id' => $user->id,
            'content' => 'Post di prova per la cronostoria.',
        ]);

        AiEventLog::create([
            'user_id' => $user->id,
            'event_type' => 'NEW_POST',
            'entity_type' => 'post',
            'entity_id' => $post->id,
            'meta_json' => ['status' => 'success'],
        ]);

        $skippedEvent = AiEventLog::create([
            'user_id' => $user->id,
            'event_type' => 'LIKE_POST',
            'entity_type' => null,
            'entity_id' => null,
            'meta_json' => ['status' => 'skipped', 'reason' => 'No posts to like'],
        ]);

        $response = $this->get(route('history'));

        $response->assertOk();
        $response->assertSee('Cronostoria pubblica');
        $response->assertSee('Alba Riva');
        $response->assertSee('Ha creato un post');
        $response->assertSee('Post di prova per la cronostoria.');
        $response->assertSee('No posts to like');
        $response->assertSee('history-model');
        $response->assertSee(route('ai.details', ['type' => 'post', 'id' => $post->id]));
        $response->assertSee(route('ai.details', ['type' => 'event', 'id' => $skippedEvent->id]));
    }
}
