<?php

namespace Tests\Feature;

use App\Models\AiLog;
use App\Models\AiUser;
use App\Models\ChatMessage;
use App\Models\ChatTopic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_shows_active_and_archived_topics_with_messages(): void
    {
        $user = AiUser::factory()->create([
            'nome' => 'Test AI',
            'energia_sociale' => 80,
            'generated_by_model' => 'test-model',
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

        $activeTopic = ChatTopic::factory()->create([
            'topic' => 'Futuro del lavoro',
            'from' => today()->toDateString(),
            'to' => today()->addDays(6)->toDateString(),
        ]);

        $archivedTopic = ChatTopic::factory()->create([
            'topic' => 'AI e creativita',
            'from' => today()->subDays(14)->toDateString(),
            'to' => today()->subDays(7)->toDateString(),
        ]);

        $futureTopic = ChatTopic::factory()->create([
            'topic' => 'Comunicazione aumentata',
            'from' => today()->addDays(7)->toDateString(),
            'to' => today()->addDays(13)->toDateString(),
        ]);

        $activeMessage = ChatMessage::factory()->create([
            'chat_topic_id' => $activeTopic->id,
            'user_id' => $user->id,
            'ai_log_id' => $aiLog->id,
            'content' => 'Messaggio attivo di prova.',
        ]);

        $archivedMessage = ChatMessage::factory()->create([
            'chat_topic_id' => $archivedTopic->id,
            'user_id' => $user->id,
            'content' => 'Messaggio archiviato di prova.',
        ]);

        $response = $this->get(route('chat'));

        $response->assertStatus(200);
        $response->assertSeeText($activeTopic->topic);
        $response->assertSeeText($archivedTopic->topic);
        $response->assertSeeText($futureTopic->topic);
        $response->assertSeeText($activeMessage->content);
        $response->assertSeeText($archivedMessage->content);
        $response->assertSeeText('test-model');
        $response->assertSee(route('ai.details', ['type' => 'chat', 'id' => $activeMessage->id]));
        $response->assertSee(route('contact', ['chat' => $activeMessage->id]));
        $response->assertSee(route('contact', ['chat' => $archivedMessage->id]));
        $response->assertSee('data-report-trigger');
        $response->assertSee('data-topic-header');
        $response->assertSee('sm:sticky');
        $response->assertSee('sm:top-16');
        $response->assertSee('text-[22px]');
        $response->assertDontSee('<p class="text-xs text-neutral-500">Messaggi</p>', false);
    }
}
