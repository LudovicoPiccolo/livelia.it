<?php

namespace App\Console\Commands;

use App\Models\AiModel;
use App\Models\AiUser;
use App\Services\AiService;
use App\Services\PromptService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

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
    public function handle(AiService $aiService, PromptService $promptService): int
    {
        $promptFile = 'create_user.md';

        try {
            $originalPrompt = $promptService->read($promptFile);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return 1;
        }

        $maxRetries = 5;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            $attempt++;
            $this->info("Attempt {$attempt} of {$maxRetries}...");

            $model = $this->freeModelsQuery()->inRandomOrder(rand())->first();

            if (! $model) {
                $this->error('No eligible free AI model available.');

                return 1;
            }

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
                $randomLetterSurname = chr(rand(65, 90)); // Random letter A-Z

                $fullPrompt = 'SEED: '.$seed."\n";
                $fullPrompt .= "VINCOLO CREATIVO OBBLIGATORIO: Il nome del personaggio DEVE iniziare con la lettera '{$randomLetter}'.\n";
                $fullPrompt .= "VINCOLO CREATIVO OBBLIGATORIO: Il cognome del personaggio DEVE iniziare con la lettera '{$randomLetterSurname}'.\n";
                $fullPrompt .= "Genera un nuovo personaggio seguendo tutte le regole sotto.\n";
                $fullPrompt .= "Usa questo SEED come riferimento per garantire unicità e variazione rispetto a personaggi precedenti.\n\n";
                $fullPrompt .= $originalPrompt;

                $userData = $aiService->generateJson($fullPrompt, $model->model_id, $promptFile);

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

                $aiUser = AiUser::create(array_merge($defaults, $userData, [
                    'generated_by_model' => $model->model_id,
                    'is_pay' => false,
                    'source_prompt_file' => $promptFile,
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

    private function freeModelsQuery(): Builder
    {
        return AiModel::query()
            ->where('is_text', true)
            ->whereNull('deleted_at')
            ->where('is_free', true);
    }
}
