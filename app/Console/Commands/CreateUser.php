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
    protected $signature = 'livelia:create_user {--free : Force the command to use a free AI model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new AI user using a free or low-cost AI model and a predefined prompt';

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
        $forceFreeModel = (bool) $this->option('free');
        $attempt = 0;

        while ($attempt < $maxRetries) {
            $attempt++;
            $this->info("Attempt {$attempt} of {$maxRetries}...");

            $maxEstimatedCost = (float) config('livelia.ai_models.max_estimated_cost', 0.002);

            if ($forceFreeModel) {
                $model = $this->freeModelsQuery()->inRandomOrder(rand())->first();
            } else {
                $shouldCreatePaidUser = $this->shouldCreatePaidUser();
                $model = $this->selectModelForNewUser($shouldCreatePaidUser, $maxEstimatedCost);
            }

            if (! $model) {
                $this->error('No eligible AI model available.');

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

                $isPayModel = ! $model->is_free
                    && $model->estimated_costs !== null
                    && (float) $model->estimated_costs > 0;

                $aiUser = AiUser::create(array_merge($defaults, $userData, [
                    'generated_by_model' => $model->model_id,
                    'is_pay' => $isPayModel,
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

    private function shouldCreatePaidUser(): bool
    {
        $freeUsers = AiUser::query()->where('is_pay', false)->count();
        $paidUsers = AiUser::query()->where('is_pay', true)->count();

        if ($freeUsers === $paidUsers) {
            return (bool) random_int(0, 1);
        }

        return $paidUsers < $freeUsers;
    }

    private function selectModelForNewUser(bool $shouldCreatePaidUser, float $maxEstimatedCost): ?AiModel
    {
        $preferredQuery = $shouldCreatePaidUser
            ? $this->paidModelsQuery($maxEstimatedCost)
            : $this->freeModelsQuery();

        $model = $preferredQuery->inRandomOrder(rand())->first();

        if ($model) {
            return $model;
        }

        $fallbackQuery = $shouldCreatePaidUser
            ? $this->freeModelsQuery()
            : $this->paidModelsQuery($maxEstimatedCost);

        return $fallbackQuery->inRandomOrder(rand())->first();
    }

    private function freeModelsQuery(): Builder
    {
        return AiModel::query()
            ->where('is_text', true)
            ->whereNull('deleted_at')
            ->where('is_free', true);
    }

    private function paidModelsQuery(float $maxEstimatedCost): Builder
    {
        return AiModel::query()
            ->where('is_text', true)
            ->whereNull('deleted_at')
            ->where('is_free', false)
            ->whereNotNull('estimated_costs')
            ->where('estimated_costs', '>', 0)
            ->where('estimated_costs', '<=', $maxEstimatedCost);
    }
}
