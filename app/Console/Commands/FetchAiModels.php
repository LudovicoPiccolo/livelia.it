<?php

namespace App\Console\Commands;

use App\Models\AiLog;
use App\Models\AiModel;
use App\Services\AiModelHealthService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchAiModels extends Command
{
    public function __construct(protected AiModelHealthService $healthService)
    {
        parent::__construct();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:ai-models';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch models from OpenRouter API and save to database';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Fetching models from OpenRouter...');

        $response = Http::get('https://openrouter.ai/api/v1/models');

        if ($response->failed()) {
            $this->error('Failed to fetch models: '.$response->status());

            return;
        }

        $data = $response->json('data');

        if (! is_array($data)) {
            $this->error('Invalid response format.');

            return;
        }

        $fetchedIds = [];
        $modelsToMigrate = [];
        $bar = $this->output->createProgressBar(count($data));
        $bar->start();
        $usageAverages = $this->calculateAverageTokenUsage();
        $maxEstimatedCost = (float) config('livelia.ai_models.max_estimated_cost', 0.002);

        foreach ($data as $modelData) {
            $fetchedIds[] = $modelData['id'];

            // Calculate is_free
            $pricing = $modelData['pricing'] ?? [];
            $promptPrice = (float) ($pricing['prompt'] ?? 0);
            $completionPrice = (float) ($pricing['completion'] ?? 0);
            $imagePrice = (float) ($pricing['image'] ?? 0);
            $requestPrice = (float) ($pricing['request'] ?? 0);

            $isFree = ($promptPrice == 0 && $completionPrice == 0 && $imagePrice == 0 && $requestPrice == 0);

            // Calculate modalities
            $architecture = $modelData['architecture'] ?? [];
            $modality = $architecture['modality'] ?? '';
            $inputModalities = $architecture['input_modalities'] ?? [];
            $outputModalities = $architecture['output_modalities'] ?? [];

            // is_text
            $hasTextInput = in_array('text', $inputModalities) || str_contains($modality, 'text->');
            $hasTextOutput = in_array('text', $outputModalities) || str_contains($modality, '->text');
            $isText = $hasTextInput && $hasTextOutput;

            // is_audio
            $hasAudioInput = in_array('audio', $inputModalities) || str_contains($modality, 'audio->');
            $hasAudioOutput = in_array('audio', $outputModalities) || str_contains($modality, '->audio');
            // Considering a model "audio" if it interacts with audio in any way (input or output)
            $isAudio = $hasAudioInput || $hasAudioOutput;

            // is_image
            // Some models might have 'image' in input (analysis) or output (generation)
            $hasImageInput = in_array('image', $inputModalities) || str_contains($modality, 'image->');
            $hasImageOutput = in_array('image', $outputModalities) || str_contains($modality, '->image');
            // Considering a model "image" if it interacts with images in any way
            $isImage = $hasImageInput || $hasImageOutput;

            // Find existing model (checking soft deleted ones too) to handle was_free logic
            $aiModel = AiModel::withTrashed()->firstOrNew(['model_id' => $modelData['id']]);
            $wasExisting = $aiModel->exists;
            $previousIsFree = $wasExisting ? (bool) $aiModel->is_free : null;
            $previousEstimatedCosts = $wasExisting && $aiModel->estimated_costs !== null
                ? (float) $aiModel->estimated_costs
                : null;

            $wasFree = $aiModel->exists ? $aiModel->was_free : false;

            // Logic: If it was free before (and currently exists), and now it's NOT free, mark was_free = true
            if ($aiModel->exists && $aiModel->is_free && ! $isFree) {
                $wasFree = true;
            }

            // Restore if it was deleted
            if ($aiModel->trashed()) {
                $aiModel->restore();
            }

            $estimatedCosts = $this->estimateCosts($usageAverages, $modelData['id'], $pricing);

            $aiModel->fill([
                'canonical_slug' => $modelData['canonical_slug'] ?? null,
                'name' => $modelData['name'] ?? null,
                'pricing' => $pricing,
                'estimated_costs' => $estimatedCosts,
                'architecture' => $architecture,
                'is_free' => $isFree,
                'was_free' => $wasFree,
                'is_text' => $isText,
                'is_audio' => $isAudio,
                'is_image' => $isImage,
            ]);

            $aiModel->save();

            $becamePaid = $wasExisting && $previousIsFree && ! $isFree;
            $exceededThreshold = $estimatedCosts !== null
                && (float) $estimatedCosts > $maxEstimatedCost
                && ($previousEstimatedCosts === null || $previousEstimatedCosts <= $maxEstimatedCost);

            if ($becamePaid || $exceededThreshold) {
                $modelsToMigrate[] = $aiModel->model_id;
            }

            $bar->advance();
        }

        // Handle deletions: delete models that are not in the fetched list
        AiModel::whereNotIn('model_id', $fetchedIds)->delete();

        foreach (array_unique($modelsToMigrate) as $modelId) {
            $this->healthService->migrateUsersFromModelToAffordable($modelId);
        }

        $bar->finish();
        $this->newLine();
        $this->info('Models fetched and saved successfully.');
    }

    private function calculateAverageTokenUsage(): array
    {
        $totals = [];
        $globalTotals = [
            'prompt_total' => 0,
            'prompt_count' => 0,
            'completion_total' => 0,
            'completion_count' => 0,
        ];

        AiLog::query()
            ->select(['model', 'full_response'])
            ->whereNotNull('full_response')
            ->chunk(500, function ($logs) use (&$totals, &$globalTotals) {
                foreach ($logs as $log) {
                    $usage = data_get($log->full_response, 'usage');

                    if (! is_array($usage)) {
                        continue;
                    }

                    $modelId = $log->model;

                    if (! isset($totals[$modelId])) {
                        $totals[$modelId] = [
                            'prompt_total' => 0,
                            'prompt_count' => 0,
                            'completion_total' => 0,
                            'completion_count' => 0,
                        ];
                    }

                    $promptTokens = data_get($usage, 'prompt_tokens');
                    if (is_numeric($promptTokens)) {
                        $totals[$modelId]['prompt_total'] += (int) $promptTokens;
                        $totals[$modelId]['prompt_count']++;
                        $globalTotals['prompt_total'] += (int) $promptTokens;
                        $globalTotals['prompt_count']++;
                    }

                    $completionTokens = data_get($usage, 'completion_tokens');
                    if (is_numeric($completionTokens)) {
                        $totals[$modelId]['completion_total'] += (int) $completionTokens;
                        $totals[$modelId]['completion_count']++;
                        $globalTotals['completion_total'] += (int) $completionTokens;
                        $globalTotals['completion_count']++;
                    }
                }
            });

        $averages = [];

        foreach ($totals as $modelId => $stats) {
            $avgPrompt = $stats['prompt_count'] > 0
                ? $stats['prompt_total'] / $stats['prompt_count']
                : null;
            $avgCompletion = $stats['completion_count'] > 0
                ? $stats['completion_total'] / $stats['completion_count']
                : null;

            if ($avgPrompt === null && $avgCompletion === null) {
                continue;
            }

            $averages[$modelId] = [
                'avg_prompt_tokens' => $avgPrompt,
                'avg_completion_tokens' => $avgCompletion,
            ];
        }

        $globalPrompt = $globalTotals['prompt_count'] > 0
            ? $globalTotals['prompt_total'] / $globalTotals['prompt_count']
            : null;
        $globalCompletion = $globalTotals['completion_count'] > 0
            ? $globalTotals['completion_total'] / $globalTotals['completion_count']
            : null;

        if ($globalPrompt !== null || $globalCompletion !== null) {
            $averages['__global__'] = [
                'avg_prompt_tokens' => $globalPrompt,
                'avg_completion_tokens' => $globalCompletion,
            ];
        }

        return $averages;
    }

    private function estimateCosts(array $usageAverages, string $modelId, array $pricing): ?float
    {
        $averages = $usageAverages[$modelId] ?? $usageAverages['__global__'] ?? null;

        if (! $averages) {
            return null;
        }

        $avgPromptTokens = $averages['avg_prompt_tokens'] ?? null;
        $avgCompletionTokens = $averages['avg_completion_tokens'] ?? null;

        if ($avgPromptTokens === null && $avgCompletionTokens === null) {
            return null;
        }

        $promptPrice = (float) ($pricing['prompt'] ?? 0);
        $completionPrice = (float) ($pricing['completion'] ?? 0);

        return (($avgPromptTokens ?? 0) * $promptPrice) + (($avgCompletionTokens ?? 0) * $completionPrice);
    }
}
