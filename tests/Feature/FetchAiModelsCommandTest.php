<?php

namespace Tests\Feature;

use App\Models\AiLog;
use App\Models\AiModel;
use App\Models\AiUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FetchAiModelsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_estimated_costs_from_ai_logs(): void
    {
        Http::fake([
            'https://openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        'id' => 'test/model',
                        'canonical_slug' => 'test-model',
                        'name' => 'Test Model',
                        'pricing' => [
                            'prompt' => 0.002,
                            'completion' => 0.004,
                            'image' => 0,
                            'request' => 0,
                        ],
                        'architecture' => [
                            'modality' => 'text->text',
                            'input_modalities' => ['text'],
                            'output_modalities' => ['text'],
                        ],
                    ],
                    [
                        'id' => 'test/model-without-logs',
                        'canonical_slug' => 'test-model-without-logs',
                        'name' => 'Test Model Without Logs',
                        'pricing' => [
                            'prompt' => 0.01,
                            'completion' => 0.02,
                            'image' => 0,
                            'request' => 0,
                        ],
                        'architecture' => [
                            'modality' => 'text->text',
                            'input_modalities' => ['text'],
                            'output_modalities' => ['text'],
                        ],
                    ],
                ],
            ], 200),
        ]);

        AiLog::create([
            'model' => 'test/model',
            'input_prompt' => 'Test prompt',
            'output_content' => 'Test output',
            'full_response' => [
                'usage' => [
                    'prompt_tokens' => 100,
                    'completion_tokens' => 50,
                ],
            ],
            'status_code' => 200,
        ]);

        AiLog::create([
            'model' => 'test/model',
            'input_prompt' => 'Another prompt',
            'output_content' => 'Another output',
            'full_response' => [
                'usage' => [
                    'prompt_tokens' => 200,
                    'completion_tokens' => 150,
                ],
            ],
            'status_code' => 200,
        ]);

        Artisan::call('fetch:ai-models');

        $model = AiModel::where('model_id', 'test/model')->first();
        $modelWithoutLogs = AiModel::where('model_id', 'test/model-without-logs')->first();

        $this->assertNotNull($model);
        $this->assertEquals(0.7, (float) $model->estimated_costs);
        $this->assertNotNull($modelWithoutLogs);
        $this->assertEquals(3.5, (float) $modelWithoutLogs->estimated_costs);
    }

    public function test_it_migrates_users_when_model_becomes_paid(): void
    {
        config(['livelia.ai_models.max_estimated_cost' => 0.002]);

        AiModel::create([
            'model_id' => 'free-model',
            'name' => 'Free Model',
            'is_free' => true,
            'is_text' => true,
        ]);

        AiModel::create([
            'model_id' => 'replacement-model',
            'name' => 'Replacement Model',
            'is_free' => true,
            'is_text' => true,
        ]);

        AiUser::factory()->create([
            'generated_by_model' => 'free-model',
        ]);

        Http::fake([
            'https://openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        'id' => 'free-model',
                        'canonical_slug' => 'free-model',
                        'name' => 'Free Model',
                        'pricing' => [
                            'prompt' => 0.001,
                            'completion' => 0.001,
                            'image' => 0,
                            'request' => 0,
                        ],
                        'architecture' => [
                            'modality' => 'text->text',
                            'input_modalities' => ['text'],
                            'output_modalities' => ['text'],
                        ],
                    ],
                    [
                        'id' => 'replacement-model',
                        'canonical_slug' => 'replacement-model',
                        'name' => 'Replacement Model',
                        'pricing' => [
                            'prompt' => 0,
                            'completion' => 0,
                            'image' => 0,
                            'request' => 0,
                        ],
                        'architecture' => [
                            'modality' => 'text->text',
                            'input_modalities' => ['text'],
                            'output_modalities' => ['text'],
                        ],
                    ],
                ],
            ], 200),
        ]);

        Artisan::call('fetch:ai-models');

        $this->assertSame(0, AiUser::where('generated_by_model', 'free-model')->count());
        $this->assertSame(1, AiUser::where('generated_by_model', 'replacement-model')->count());
    }

    public function test_it_migrates_users_when_model_cost_exceeds_threshold(): void
    {
        config(['livelia.ai_models.max_estimated_cost' => 0.002]);

        AiModel::create([
            'model_id' => 'expensive-model',
            'name' => 'Expensive Model',
            'is_free' => false,
            'is_text' => true,
            'estimated_costs' => 0.001,
        ]);

        AiModel::create([
            'model_id' => 'affordable-model',
            'name' => 'Affordable Model',
            'is_free' => true,
            'is_text' => true,
        ]);

        AiUser::factory()->create([
            'generated_by_model' => 'expensive-model',
        ]);

        AiLog::create([
            'model' => 'expensive-model',
            'input_prompt' => 'Test prompt',
            'output_content' => 'Test output',
            'full_response' => [
                'usage' => [
                    'prompt_tokens' => 100,
                    'completion_tokens' => 100,
                ],
            ],
            'status_code' => 200,
        ]);

        Http::fake([
            'https://openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    [
                        'id' => 'expensive-model',
                        'canonical_slug' => 'expensive-model',
                        'name' => 'Expensive Model',
                        'pricing' => [
                            'prompt' => 0.00002,
                            'completion' => 0.00002,
                            'image' => 0,
                            'request' => 0,
                        ],
                        'architecture' => [
                            'modality' => 'text->text',
                            'input_modalities' => ['text'],
                            'output_modalities' => ['text'],
                        ],
                    ],
                    [
                        'id' => 'affordable-model',
                        'canonical_slug' => 'affordable-model',
                        'name' => 'Affordable Model',
                        'pricing' => [
                            'prompt' => 0,
                            'completion' => 0,
                            'image' => 0,
                            'request' => 0,
                        ],
                        'architecture' => [
                            'modality' => 'text->text',
                            'input_modalities' => ['text'],
                            'output_modalities' => ['text'],
                        ],
                    ],
                ],
            ], 200),
        ]);

        Artisan::call('fetch:ai-models');

        $this->assertSame(0, AiUser::where('generated_by_model', 'expensive-model')->count());
        $this->assertSame(1, AiUser::where('generated_by_model', 'affordable-model')->count());
    }
}
