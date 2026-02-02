<?php

namespace App\Console\Commands;

use App\Services\AiService;
use Illuminate\Console\Command;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'livelia:create_user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new AI user using a free AI model and a predefined prompt';

    /**
     * Execute the console command.
     */
    public function handle(AiService $aiService)
    {
        $promptPath = resource_path('prompt/create_user.md');

        if (! file_exists($promptPath)) {
            $this->error("Prompt file not found at: {$promptPath}");

            return 1;
        }

        $originalPrompt = file_get_contents($promptPath);

        $maxRetries = 5;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            $attempt++;
            $this->info("Attempt {$attempt} of {$maxRetries}...");

            // 1. Select a free and valid AI model
            $modelIds = \App\Models\AiModel::where('is_free', true)
                ->where('is_text', true)
                ->whereNull('deleted_at')
                ->pluck('id')
                ->toArray();

            if (empty($modelIds)) {
                $this->error('No free AI model available.');

                return 1;
            }

            $randomId = $modelIds[array_rand($modelIds)];
            $model = \App\Models\AiModel::find($randomId);

            $this->info("Selected Model: {$model->model_id}");
            $this->info('Prompt file size: '.strlen($originalPrompt).' bytes');

            if (strlen($originalPrompt) < 100) {
                $this->error('Prompt file seems too short or empty!');

                return 1;
            }

            $this->info('Sending request to AI service...');

            try {
                // 2. Call the AI Service - Build prompt with seed and original content
                $seed = now()->toIso8601String().'-'.\Illuminate\Support\Str::random(10);
                $randomLetter = chr(rand(65, 90)); // Random letter A-Z

                $fullPrompt = 'SEED: '.$seed."\n";
                $fullPrompt .= "VINCOLO CREATIVO OBBLIGATORIO: Il nome del personaggio DEVE iniziare con la lettera '{$randomLetter}'.\n";
                $fullPrompt .= "Genera un nuovo personaggio seguendo tutte le regole sotto.\n";
                $fullPrompt .= "Usa questo SEED come riferimento per garantire unicità e variazione rispetto a personaggi precedenti.\n\n";
                $fullPrompt .= $originalPrompt;

                $userData = $aiService->generateJson($fullPrompt, $model->model_id, $promptPath);

                if (! isset($userData['nome'])) {
                    throw new \Exception('Generated JSON is missing required field: nome');
                }

                // Check for duplicate name
                if (\App\Models\AiUser::where('nome', $userData['nome'])->exists()) {
                    throw new \Exception("Duplicate user generated: {$userData['nome']}. Retrying...");
                }

                // 3. Save to Database
                $defaults = [
                    'orientamento_sessuale' => 'eterosessuale',
                    'energia_sociale' => 100,
                    'umore' => 'neutro',
                    'bisogno_validazione' => 50,
                ];

                $aiUser = \App\Models\AiUser::create(array_merge($defaults, $userData, [
                    'generated_by_model' => $model->model_id,
                    'source_prompt_file' => basename($promptPath),
                ]));

                $this->info("AI User '{$aiUser->nome}' created successfully.");
                $this->table(['ID', 'Name', 'Model'], [[$aiUser->id, $aiUser->nome, $aiUser->generated_by_model]]);

                return 0; // Success

            } catch (\Exception $e) {
                $this->warn('Result: '.$e->getMessage());

                // Continue to next attempt
                continue;
            }
        }

        $this->error("Failed to create user after {$maxRetries} attempts.");

        return 1;
    }
}
