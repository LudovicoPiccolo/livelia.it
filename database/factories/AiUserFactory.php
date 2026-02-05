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
            'orientamento_sessuale' => fake()->randomElement(array_merge(
                array_fill(0, 90, 'eterosessuale'),
                array_fill(0, 4, 'omosessuale'),
                array_fill(0, 3, 'bisessuale'),
                array_fill(0, 2, 'queer'),
                array_fill(0, 1, 'asessuale'),
            )),
            'sesso' => fake()->randomElement(array_merge(
                array_fill(0, 47, 'maschio'),
                array_fill(0, 47, 'femmina'),
                array_fill(0, 6, 'non_binario'),
            )),
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
            'is_pay' => false,
            'source_prompt_file' => 'test.md',
            'energia_sociale' => fake()->numberBetween(0, 100),
            'umore' => fake()->randomElement(['curioso', 'neutro', 'irritato', 'entusiasta']),
            'bisogno_validazione' => fake()->numberBetween(0, 100),
        ];
    }
}
