<?php

namespace Tests\Feature;

use App\Models\AiEventLog;
use App\Models\AiLog;
use App\Models\AiUser;
use App\Models\ChatMessage;
use App\Models\ChatTopic;
use App\Services\AiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ChatTickCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_chat_tick_creates_message_after_threshold(): void
    {
        Config::set('livelia.chat.events_per_message', 30);

        $topic = ChatTopic::factory()->create([
            'from' => today()->toDateString(),
            'to' => today()->addDays(6)->toDateString(),
        ]);

        $user = AiUser::factory()->create([
            'energia_sociale' => 80,
            'generated_by_model' => 'test-model',
            'is_pay' => true,
        ]);

        $this->app->instance(AiService::class, new class extends AiService
        {
            private ?AiLog $lastLog = null;

            public function generateJson(string $prompt, string $modelId, ?string $promptPath = null): array
            {
                $this->lastLog = AiLog::create([
                    'model' => $modelId,
                    'input_prompt' => $prompt,
                    'output_content' => json_encode(['content' => str_repeat('a', 480)]),
                    'status_code' => 200,
                    'prompt_file' => $promptPath,
                ]);

                return ['content' => str_repeat('a', 480)];
            }

            public function getLastLog(): ?AiLog
            {
                return $this->lastLog;
            }
        });

        for ($i = 0; $i < 29; $i++) {
            AiEventLog::create([
                'user_id' => $user->id,
                'event_type' => 'COMMENT_POST',
                'meta_json' => ['status' => 'success'],
            ]);
        }

        Artisan::call('livelia:chat_tick');

        $this->assertDatabaseCount('chat_messages', 0);

        AiEventLog::create([
            'user_id' => $user->id,
            'event_type' => 'COMMENT_POST',
            'meta_json' => ['status' => 'success'],
        ]);

        Artisan::call('livelia:chat_tick');

        $this->assertDatabaseCount('chat_messages', 1);

        $message = ChatMessage::first();

        $this->assertSame($topic->id, $message->chat_topic_id);
        $this->assertSame($user->id, $message->user_id);
        $this->assertSame(str_repeat('a', 480), $message->content);
        $this->assertSame(AiEventLog::max('id'), $message->last_event_log_id);

        $user->refresh();
        $this->assertTrue($user->chat_cooldown_until->isFuture());
    }

    public function test_chat_tick_skips_when_only_free_users_available(): void
    {
        Config::set('livelia.chat.events_per_message', 5);

        $topic = ChatTopic::factory()->create([
            'from' => today()->toDateString(),
            'to' => today()->addDays(6)->toDateString(),
        ]);

        $user = AiUser::factory()->create([
            'energia_sociale' => 80,
            'generated_by_model' => 'test-model',
            'is_pay' => false,
        ]);

        $this->app->instance(AiService::class, new class extends AiService
        {
            private ?AiLog $lastLog = null;

            public function generateJson(string $prompt, string $modelId, ?string $promptPath = null): array
            {
                $this->lastLog = AiLog::create([
                    'model' => $modelId,
                    'input_prompt' => $prompt,
                    'output_content' => json_encode(['content' => 'test']),
                    'status_code' => 200,
                    'prompt_file' => $promptPath,
                ]);

                return ['content' => 'test'];
            }

            public function getLastLog(): ?AiLog
            {
                return $this->lastLog;
            }
        });

        for ($i = 0; $i < 5; $i++) {
            AiEventLog::create([
                'user_id' => $user->id,
                'event_type' => 'COMMENT_POST',
                'meta_json' => ['status' => 'success'],
            ]);
        }

        Artisan::call('livelia:chat_tick');

        $this->assertDatabaseCount('chat_messages', 0);
    }

    public function test_chat_tick_skips_user_with_active_chat_cooldown(): void
    {
        Config::set('livelia.chat.events_per_message', 5);

        ChatTopic::factory()->create([
            'from' => today()->toDateString(),
            'to' => today()->addDays(6)->toDateString(),
        ]);

        AiUser::factory()->create([
            'energia_sociale' => 80,
            'generated_by_model' => 'test-model',
            'is_pay' => true,
            'chat_cooldown_until' => now()->addHours(12),
        ]);

        for ($i = 0; $i < 5; $i++) {
            AiEventLog::create([
                'user_id' => 1,
                'event_type' => 'COMMENT_POST',
                'meta_json' => ['status' => 'success'],
            ]);
        }

        Artisan::call('livelia:chat_tick');

        $this->assertDatabaseCount('chat_messages', 0);
    }

    public function test_chat_tick_ignores_social_cooldown(): void
    {
        Config::set('livelia.chat.events_per_message', 5);

        $topic = ChatTopic::factory()->create([
            'from' => today()->toDateString(),
            'to' => today()->addDays(6)->toDateString(),
        ]);

        $user = AiUser::factory()->create([
            'energia_sociale' => 80,
            'generated_by_model' => 'test-model',
            'is_pay' => true,
            'cooldown_until' => now()->addHours(12), // cooldown sociale attivo
        ]);

        $this->app->instance(AiService::class, new class extends AiService
        {
            private ?AiLog $lastLog = null;

            public function generateJson(string $prompt, string $modelId, ?string $promptPath = null): array
            {
                $this->lastLog = AiLog::create([
                    'model' => $modelId,
                    'input_prompt' => $prompt,
                    'output_content' => json_encode(['content' => str_repeat('c', 480)]),
                    'status_code' => 200,
                    'prompt_file' => $promptPath,
                ]);

                return ['content' => str_repeat('c', 480)];
            }

            public function getLastLog(): ?AiLog
            {
                return $this->lastLog;
            }
        });

        for ($i = 0; $i < 5; $i++) {
            AiEventLog::create([
                'user_id' => $user->id,
                'event_type' => 'COMMENT_POST',
                'meta_json' => ['status' => 'success'],
            ]);
        }

        Artisan::call('livelia:chat_tick');

        $this->assertDatabaseCount('chat_messages', 1);
    }

    public function test_chat_tick_injects_phase_apertura_when_topic_just_started(): void
    {
        Config::set('livelia.chat.events_per_message', 1);

        // Topic iniziato oggi, dura 7 giorni → ratio ~0 → apertura
        ChatTopic::factory()->create([
            'from' => today()->toDateString(),
            'to' => today()->addDays(6)->toDateString(),
        ]);

        $user = AiUser::factory()->create([
            'energia_sociale' => 80,
            'generated_by_model' => 'test-model',
            'is_pay' => true,
        ]);

        $capturedPrompt = null;

        $this->app->instance(AiService::class, new class($capturedPrompt) extends AiService
        {
            private ?AiLog $lastLog = null;

            public function __construct(private mixed &$ref) {}

            public function generateJson(string $prompt, string $modelId, ?string $promptPath = null): array
            {
                $this->ref = $prompt;
                $this->lastLog = AiLog::create([
                    'model' => $modelId,
                    'input_prompt' => $prompt,
                    'output_content' => json_encode(['content' => str_repeat('x', 600)]),
                    'status_code' => 200,
                    'prompt_file' => $promptPath,
                ]);

                return ['content' => str_repeat('x', 600)];
            }

            public function getLastLog(): ?AiLog
            {
                return $this->lastLog;
            }
        });

        AiEventLog::create([
            'user_id' => $user->id,
            'event_type' => 'COMMENT_POST',
            'meta_json' => ['status' => 'success'],
        ]);

        Artisan::call('livelia:chat_tick');

        $this->assertDatabaseCount('chat_messages', 1);
        $this->assertStringContainsString('apertura', $capturedPrompt);
        $this->assertStringNotContainsString('sviluppo', $capturedPrompt);
        $this->assertStringNotContainsString('chiusura', $capturedPrompt);
    }

    public function test_chat_tick_injects_phase_sviluppo_when_topic_is_midway(): void
    {
        Config::set('livelia.chat.events_per_message', 1);

        // Topic iniziato 3 giorni fa, finisce tra 3 → ratio ~0.43 → sviluppo
        ChatTopic::factory()->create([
            'from' => today()->subDays(3)->toDateString(),
            'to' => today()->addDays(3)->toDateString(),
        ]);

        $user = AiUser::factory()->create([
            'energia_sociale' => 80,
            'generated_by_model' => 'test-model',
            'is_pay' => true,
        ]);

        $capturedPrompt = null;

        $this->app->instance(AiService::class, new class($capturedPrompt) extends AiService
        {
            private ?AiLog $lastLog = null;

            public function __construct(private mixed &$ref) {}

            public function generateJson(string $prompt, string $modelId, ?string $promptPath = null): array
            {
                $this->ref = $prompt;
                $this->lastLog = AiLog::create([
                    'model' => $modelId,
                    'input_prompt' => $prompt,
                    'output_content' => json_encode(['content' => str_repeat('y', 600)]),
                    'status_code' => 200,
                    'prompt_file' => $promptPath,
                ]);

                return ['content' => str_repeat('y', 600)];
            }

            public function getLastLog(): ?AiLog
            {
                return $this->lastLog;
            }
        });

        AiEventLog::create([
            'user_id' => $user->id,
            'event_type' => 'COMMENT_POST',
            'meta_json' => ['status' => 'success'],
        ]);

        Artisan::call('livelia:chat_tick');

        $this->assertDatabaseCount('chat_messages', 1);
        $this->assertStringContainsString('sviluppo', $capturedPrompt);
    }

    public function test_chat_tick_injects_phase_chiusura_when_topic_is_ending(): void
    {
        Config::set('livelia.chat.events_per_message', 1);

        // Topic iniziato 6 giorni fa, finisce oggi → ratio ~0.86 → chiusura
        ChatTopic::factory()->create([
            'from' => today()->subDays(6)->toDateString(),
            'to' => today()->toDateString(),
        ]);

        $user = AiUser::factory()->create([
            'energia_sociale' => 80,
            'generated_by_model' => 'test-model',
            'is_pay' => true,
        ]);

        $capturedPrompt = null;

        $this->app->instance(AiService::class, new class($capturedPrompt) extends AiService
        {
            private ?AiLog $lastLog = null;

            public function __construct(private mixed &$ref) {}

            public function generateJson(string $prompt, string $modelId, ?string $promptPath = null): array
            {
                $this->ref = $prompt;
                $this->lastLog = AiLog::create([
                    'model' => $modelId,
                    'input_prompt' => $prompt,
                    'output_content' => json_encode(['content' => str_repeat('z', 600)]),
                    'status_code' => 200,
                    'prompt_file' => $promptPath,
                ]);

                return ['content' => str_repeat('z', 600)];
            }

            public function getLastLog(): ?AiLog
            {
                return $this->lastLog;
            }
        });

        AiEventLog::create([
            'user_id' => $user->id,
            'event_type' => 'COMMENT_POST',
            'meta_json' => ['status' => 'success'],
        ]);

        Artisan::call('livelia:chat_tick');

        $this->assertDatabaseCount('chat_messages', 1);
        $this->assertStringContainsString('chiusura', $capturedPrompt);
    }

    public function test_chat_tick_allows_user_after_chat_cooldown_expires(): void
    {
        Config::set('livelia.chat.events_per_message', 5);
        Config::set('livelia.chat.cooldown_hours', 24);

        $topic = ChatTopic::factory()->create([
            'from' => today()->toDateString(),
            'to' => today()->addDays(6)->toDateString(),
        ]);

        $user = AiUser::factory()->create([
            'energia_sociale' => 80,
            'generated_by_model' => 'test-model',
            'is_pay' => true,
            'chat_cooldown_until' => now()->subHours(1),
        ]);

        $this->app->instance(AiService::class, new class extends AiService
        {
            private ?AiLog $lastLog = null;

            public function generateJson(string $prompt, string $modelId, ?string $promptPath = null): array
            {
                $this->lastLog = AiLog::create([
                    'model' => $modelId,
                    'input_prompt' => $prompt,
                    'output_content' => json_encode(['content' => str_repeat('b', 480)]),
                    'status_code' => 200,
                    'prompt_file' => $promptPath,
                ]);

                return ['content' => str_repeat('b', 480)];
            }

            public function getLastLog(): ?AiLog
            {
                return $this->lastLog;
            }
        });

        for ($i = 0; $i < 5; $i++) {
            AiEventLog::create([
                'user_id' => $user->id,
                'event_type' => 'COMMENT_POST',
                'meta_json' => ['status' => 'success'],
            ]);
        }

        Artisan::call('livelia:chat_tick');

        $this->assertDatabaseCount('chat_messages', 1);

        $user->refresh();
        $this->assertTrue($user->chat_cooldown_until->isFuture());
        $this->assertTrue($user->chat_cooldown_until->diffInMinutes(now(), true) >= 23 * 60);
    }
}
