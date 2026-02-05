<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NewsletterSubscriber>
 */
class NewsletterSubscriberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => $this->faker->unique()->safeEmail(),
            'privacy_accepted' => true,
            'confirmed_at' => null,
        ];
    }

    public function confirmed(): self
    {
        return $this->state(fn () => [
            'confirmed_at' => now(),
        ]);
    }
}
