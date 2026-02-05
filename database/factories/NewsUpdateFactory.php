<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NewsUpdate>
 */
class NewsUpdateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = fake()->dateTimeBetween('-30 days', 'now');

        return [
            'version' => $date->format('dmY'),
            'date' => $date->format('Y-m-d'),
            'title' => fake()->sentence(6),
            'summary' => fake()->sentence(12),
            'details' => [
                fake()->sentence(8),
                fake()->sentence(8),
            ],
        ];
    }
}
