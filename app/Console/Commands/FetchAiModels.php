<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\AiModel;

class FetchAiModels extends Command
{
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
    public function handle()
    {
        $this->info('Fetching models from OpenRouter...');

        $response = Http::get('https://openrouter.ai/api/v1/models');

        if ($response->failed()) {
            $this->error('Failed to fetch models: ' . $response->status());
            return;
        }

        $data = $response->json('data');

        if (!is_array($data)) {
             $this->error('Invalid response format.');
             return;
        }

        $fetchedIds = [];
        $bar = $this->output->createProgressBar(count($data));
        $bar->start();

        foreach ($data as $modelData) {
            $fetchedIds[] = $modelData['id'];

            // Calculate is_free
            $pricing = $modelData['pricing'] ?? [];
            $promptPrice = (float) ($pricing['prompt'] ?? 0);
            $completionPrice = (float) ($pricing['completion'] ?? 0);
            $imagePrice = (float) ($pricing['image'] ?? 0);
            $requestPrice = (float) ($pricing['request'] ?? 0);
            
            // User request: "C'è un modello che come pricing ha: {"prompt":"-1","completion":"-1"} non so bene cosa vuol dire, ma lo considererei free"
            // Also assuming free if all costs are 0
            $isNegativePrice = ($promptPrice < 0 && $completionPrice < 0);
            $isZeroPrice = ($promptPrice == 0 && $completionPrice == 0 && $imagePrice == 0 && $requestPrice == 0);
            
            $isFree = $isNegativePrice || $isZeroPrice;

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

            $wasFree = $aiModel->exists ? $aiModel->was_free : false;

            // Logic: If it was free before (and currently exists), and now it's NOT free, mark was_free = true
            if ($aiModel->exists && $aiModel->is_free && !$isFree) {
                $wasFree = true;
            }

            // Restore if it was deleted
            if ($aiModel->trashed()) {
                $aiModel->restore();
            }

            $aiModel->fill([
                'canonical_slug' => $modelData['canonical_slug'] ?? null,
                'name' => $modelData['name'] ?? null,
                'pricing' => $pricing,
                'architecture' => $architecture,
                'is_free' => $isFree,
                'was_free' => $wasFree,
                'is_text' => $isText,
                'is_audio' => $isAudio,
                'is_image' => $isImage,
            ]);

            $aiModel->save();

            $bar->advance();
        }

        // Handle deletions: delete models that are not in the fetched list
        AiModel::whereNotIn('model_id', $fetchedIds)->delete();

        $bar->finish();
        $this->newLine();
        $this->info('Models fetched and saved successfully.');
    }
}
