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

        $bar = $this->output->createProgressBar(count($data));
        $bar->start();

        foreach ($data as $modelData) {
            AiModel::updateOrCreate(
                ['model_id' => $modelData['id']],
                [
                    'canonical_slug' => $modelData['canonical_slug'] ?? null,
                    'name' => $modelData['name'] ?? null,
                    'pricing' => $modelData['pricing'] ?? [],
                    'architecture' => $modelData['architecture'] ?? [],
                ]
            );
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Models fetched and saved successfully.');
    }
}
