<?php

namespace Tests\Feature;

use App\Models\AiLog;
use App\Models\AiModel;
use App\Models\AiUser;
use App\Services\AiModelHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiModelSuspensionTest extends TestCase
{
    use RefreshDatabase;

    protected AiModelHealthService $healthService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->healthService = app(AiModelHealthService::class);
    }

    public function test_it_suspends_model_after_15_errors_and_migrates_users()
    {
        // Create models
        $badModel = AiModel::create([
            'model_id' => 'bad-model', 
            'is_free' => true,
            'name' => 'Bad Model'
        ]);
        $goodModel = AiModel::create([
            'model_id' => 'good-model', 
            'is_free' => true,
            'name' => 'Good Model'
        ]);
        $paidModel = AiModel::create([
            'model_id' => 'paid-model', 
            'is_free' => false,
            'name' => 'Paid Model'
        ]);

        // Create users
        AiUser::unguard();
        for ($i=0; $i<5; $i++) {
            AiUser::create([
                'nome' => "User $i",
                'generated_by_model' => 'bad-model',
                'sesso' => 'M',
                'orientamento_sessuale' => 'Hetero',
                'lavoro' => 'Dev',
                'orientamento_politico' => 'Center',
                'passioni' => [['tema' => 'Tech', 'livello' => 10]],
                'bias_informativo' => 'None',
                'personalita' => 'Calm',
                'stile_comunicativo' => 'Direct',
                'atteggiamento_verso_attualita' => 'Interested',
                'propensione_al_conflitto' => 5,
                'sensibilita_ai_like' => 5,
                'ritmo_attivita' => 'High',
                'source_prompt_file' => 'test.md',
            ]);
        }
        AiUser::reguard();

        // Create 14 errors (should NOT suspend yet)
        for ($i = 0; $i < 14; $i++) {
            AiLog::create([
                'model' => 'bad-model',
                'input_prompt' => 'test',
                'error_message' => 'error',
                'created_at' => now(),
            ]);
        }

        $this->healthService->checkAndSuspendModels();

        $badModel->refresh();
        $this->assertNull($badModel->suspended_until);
        $this->assertEquals(5, AiUser::where('generated_by_model', 'bad-model')->count());

        // Create 2 more errors (Total 16 > 15)
        for ($i = 0; $i < 2; $i++) {
            AiLog::create([
                'model' => 'bad-model',
                'input_prompt' => 'test',
                'error_message' => 'error',
                'created_at' => now(),
            ]);
        }

        $this->healthService->checkAndSuspendModels();

        $badModel->refresh();
        $this->assertNotNull($badModel->suspended_until);
        $this->assertTrue($badModel->suspended_until->isFuture());

        // Check if users migrated to GOOD model (not paid)
        $migratedUsers = AiUser::where('generated_by_model', 'good-model')->count();
        $remainingUsers = AiUser::where('generated_by_model', 'bad-model')->count();
        $paidUsers = AiUser::where('generated_by_model', 'paid-model')->count();

        $this->assertEquals(5, $migratedUsers);
        $this->assertEquals(0, $remainingUsers);
        $this->assertEquals(0, $paidUsers);
    }
}
