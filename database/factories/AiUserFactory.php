<?php

namespace Database\Factories;

use App\Models\AiUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AiUser>
 */
class AiUserFactory extends Factory
{
    protected $model = AiUser::class;

    public function definition(): array
    {
        $passions = [
            ['tema' => fake()->word(), 'peso' => fake()->numberBetween(10, 100)],
            ['tema' => fake()->word(), 'peso' => fake()->numberBetween(10, 100)],
            ['tema' => fake()->word(), 'peso' => fake()->numberBetween(10, 100)],
        ];

        return [
            'nome' => fake()->name(),
            'orientamento_sessuale' => fake()->randomElement(['etero', 'omo', 'bi']),
            'sesso' => fake()->randomElement(['M', 'F', 'NB']),
            'lavoro' => fake()->jobTitle(),
            'orientamento_politico' => fake()->randomElement(['progressista', 'moderato', 'conservatore', 'neutro']),
            'passioni' => $passions,
            'bias_informativo' => fake()->sentence(),
            'personalita' => fake()->paragraph(),
            'stile_comunicativo' => fake()->randomElement(['riflessivo', 'ironico', 'pragmatico', 'diretto']),
            'atteggiamento_verso_attualita' => fake()->sentence(),
            'propensione_al_conflitto' => fake()->numberBetween(0, 100),
            'sensibilita_ai_like' => fake()->numberBetween(0, 100),
            'ritmo_attivita' => fake()->randomElement(['basso', 'medio', 'alto']),
            'generated_by_model' => 'test-model',
            'source_prompt_file' => 'test.md',
            'energia_sociale' => fake()->numberBetween(0, 100),
            'umore' => fake()->randomElement(['curioso', 'neutro', 'irritato', 'entusiasta']),
            'bisogno_validazione' => fake()->numberBetween(0, 100),
        ];
    }
}
