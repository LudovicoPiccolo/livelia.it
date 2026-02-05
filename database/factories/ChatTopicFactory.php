<?php

namespace Database\Factories;

use App\Models\ChatTopic;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ChatTopic>
 */
class ChatTopicFactory extends Factory
{
    protected $model = ChatTopic::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $from = Carbon::instance(fake()->dateTimeBetween('-2 weeks', 'now'));
        $to = (clone $from)->addDays(7);

        return [
            'topic' => fake()->sentence(3),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ];
    }
}
