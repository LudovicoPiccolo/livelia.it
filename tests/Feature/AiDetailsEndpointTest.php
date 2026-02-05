<?php

namespace Tests\Feature;

use App\Models\AiComment;
use App\Models\AiEventLog;
use App\Models\AiLog;
use App\Models\AiPost;
use App\Models\AiUser;
use App\Models\ChatMessage;
use App\Models\ChatTopic;
use App\Models\GenericNews;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiDetailsEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['livelia.software_version' => '2026.02.03']);
    }

    public function test_ai_details_returns_details_for_post(): void
    {
        $user = AiUser::factory()->create([
            'generated_by_model' => 'test-model',
        ]);

        $aiLog = AiLog::create([
            'model' => 'test-model',
            'input_prompt' => 'PROMPT_POST',
            'output_content' => 'Output',
            'full_response' => ['ok' => true],
            'status_code' => 200,
            'error_message' => null,
            'prompt_file' => 'create_post.md',
        ]);

        $post = AiPost::create([
            'user_id' => $user->id,
            'content' => 'Contenuto del post',
            'ai_log_id' => $aiLog->id,
            'source_type' => 'personal',
        ]);

        $response = $this->get(route('ai.details', ['type' => 'post', 'id' => $post->id]));

        $response->assertOk();
        $response->assertJsonFragment([
            'entity_type' => 'post',
            'model' => 'test-model',
        ]);
        $response->assertJsonMissingPath('prompt');
        $response->assertJsonMissingPath('prompt_file');
        $response->assertJsonPath('software_version', '2026.02.03');
        $response->assertJsonPath('source.type', 'personal');
    }

    public function test_ai_details_returns_details_for_comment(): void
    {
        $user = AiUser::factory()->create([
            'generated_by_model' => 'test-model',
        ]);

        $post = AiPost::create([
            'user_id' => $user->id,
            'content' => 'Post base',
        ]);

        $aiLog = AiLog::create([
            'model' => 'test-model',
            'input_prompt' => 'PROMPT_COMMENT',
            'output_content' => 'Output',
            'full_response' => ['ok' => true],
            'status_code' => 200,
            'error_message' => null,
            'prompt_file' => 'create_comment.md',
        ]);

        $comment = AiComment::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'content' => 'Commento di test',
            'ai_log_id' => $aiLog->id,
        ]);

        $response = $this->get(route('ai.details', ['type' => 'comment', 'id' => $comment->id]));

        $response->assertOk();
        $response->assertJsonFragment([
            'entity_type' => 'comment',
            'model' => 'test-model',
        ]);
        $response->assertJsonMissingPath('prompt');
        $response->assertJsonMissingPath('prompt_file');
        $response->assertJsonPath('software_version', '2026.02.03');
    }

    public function test_ai_details_returns_details_for_chat_message(): void
    {
        $user = AiUser::factory()->create([
            'generated_by_model' => 'test-model',
        ]);

        $topic = ChatTopic::factory()->create([
            'from' => today()->toDateString(),
            'to' => today()->addDays(6)->toDateString(),
        ]);

        $aiLog = AiLog::create([
            'model' => 'test-model',
            'input_prompt' => 'PROMPT_CHAT',
            'output_content' => 'Output',
            'full_response' => ['ok' => true],
            'status_code' => 200,
            'error_message' => null,
            'prompt_file' => 'create_chat_message.md',
        ]);

        $message = ChatMessage::factory()->create([
            'chat_topic_id' => $topic->id,
            'user_id' => $user->id,
            'ai_log_id' => $aiLog->id,
        ]);

        $response = $this->get(route('ai.details', ['type' => 'chat', 'id' => $message->id]));

        $response->assertOk();
        $response->assertJsonFragment([
            'entity_type' => 'chat',
            'model' => 'test-model',
        ]);
        $response->assertJsonMissingPath('prompt');
        $response->assertJsonMissingPath('prompt_file');
        $response->assertJsonPath('software_version', '2026.02.03');
    }

    public function test_ai_details_returns_model_for_event(): void
    {
        $user = AiUser::factory()->create([
            'generated_by_model' => 'event-model',
            'is_pay' => true,
        ]);

        $event = AiEventLog::create([
            'user_id' => $user->id,
            'is_pay' => true,
            'event_type' => 'LIKE_POST',
            'entity_type' => null,
            'entity_id' => null,
            'meta_json' => ['status' => 'skipped'],
        ]);

        $response = $this->get(route('ai.details', ['type' => 'event', 'id' => $event->id]));

        $response->assertOk();
        $response->assertJsonFragment([
            'entity_type' => 'event',
            'model' => 'event-model',
            'is_pay' => true,
        ]);
        $response->assertJsonPath('software_version', '2026.02.03');
        $response->assertJsonMissingPath('prompt');
        $response->assertJsonMissingPath('prompt_file');
    }

    public function test_ai_details_normalizes_unknown_source_type_to_generic_news(): void
    {
        $user = AiUser::factory()->create([
            'generated_by_model' => 'test-model',
        ]);

        $news = GenericNews::create([
            'title' => 'Notizia di test',
            'news_date' => now()->toDateString(),
            'category' => 'Economia',
            'summary' => 'Riassunto di test.',
            'why_it_matters' => 'Rilevanza di test.',
            'source_name' => 'Fonte Test',
            'source_url' => 'https://example.test/news',
            'published_at' => now(),
        ]);

        $post = AiPost::create([
            'user_id' => $user->id,
            'content' => 'Contenuto del post',
            'news_id' => $news->id,
            'source_type' => 'legacy',
        ]);

        $response = $this->get(route('ai.details', ['type' => 'post', 'id' => $post->id]));

        $response->assertOk();
        $response->assertJsonPath('source.type', 'generic_news');
    }
}
