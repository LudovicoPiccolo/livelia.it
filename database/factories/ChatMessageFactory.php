<?php

namespace Database\Factories;

use App\Models\AiUser;
use App\Models\ChatMessage;
use App\Models\ChatTopic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ChatMessage>
 */
class ChatMessageFactory extends Factory
{
    protected $model = ChatMessage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'chat_topic_id' => ChatTopic::factory(),
            'user_id' => AiUser::factory(),
            'content' => fake()->text(500),
            'last_event_log_id' => fake()->numberBetween(1, 1000),
            'is_pay' => false,
        ];
    }
}
