<?php

namespace Tests\Feature;

use App\Models\AiLog;
use App\Models\AiModel;
use App\Models\AiUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CreateUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_paid_user_when_only_paid_models_are_available(): void
    {
        $this->setAiConfig();

        AiModel::create([
            'model_id' => 'paid-model',
            'name' => 'Paid Model',
            'is_free' => false,
            'is_text' => true,
            'estimated_costs' => 0.001,
        ]);

        $userPayload = $this->fakeUserPayload();

        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode($userPayload, JSON_UNESCAPED_UNICODE),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->artisan('livelia:create_user')->assertExitCode(0);

        $this->assertSame(1, AiUser::count());
        $user = AiUser::first();
        $this->assertTrue($user->is_pay);
        $this->assertSame('paid-model', $user->generated_by_model);

        $log = AiLog::first();
        $this->assertNotNull($log);
        $this->assertTrue($log->is_pay);
    }

    public function test_it_balances_paid_and_free_users_by_selecting_paid_when_free_outnumber_paid(): void
    {
        $this->setAiConfig();

        AiModel::create([
            'model_id' => 'free-model',
            'name' => 'Free Model',
            'is_free' => true,
            'is_text' => true,
            'estimated_costs' => 0.0,
        ]);

        AiModel::create([
            'model_id' => 'paid-model',
            'name' => 'Paid Model',
            'is_free' => false,
            'is_text' => true,
            'estimated_costs' => 0.001,
        ]);

        AiUser::factory()->count(2)->create(['is_pay' => false]);
        AiUser::factory()->create(['is_pay' => true]);

        $userPayload = $this->fakeUserPayload();

        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode($userPayload, JSON_UNESCAPED_UNICODE),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->artisan('livelia:create_user')->assertExitCode(0);

        $user = AiUser::query()->latest('id')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->is_pay);
        $this->assertSame('paid-model', $user->generated_by_model);
    }

    public function test_it_forces_a_free_user_when_free_option_is_used(): void
    {
        $this->setAiConfig();

        AiModel::create([
            'model_id' => 'free-model',
            'name' => 'Free Model',
            'is_free' => true,
            'is_text' => true,
            'estimated_costs' => 0.0,
        ]);

        AiModel::create([
            'model_id' => 'paid-model',
            'name' => 'Paid Model',
            'is_free' => false,
            'is_text' => true,
            'estimated_costs' => 0.001,
        ]);

        $userPayload = $this->fakeUserPayload();

        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode($userPayload, JSON_UNESCAPED_UNICODE),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->artisan('livelia:create_user --free')->assertExitCode(0);

        $user = AiUser::query()->latest('id')->first();
        $this->assertNotNull($user);
        $this->assertFalse($user->is_pay);
        $this->assertSame('free-model', $user->generated_by_model);

        $log = AiLog::first();
        $this->assertNotNull($log);
        $this->assertFalse($log->is_pay);
    }

    private function setAiConfig(): void
    {
        config([
            'services.ai.api_key' => 'test-key',
            'services.ai.base_url' => 'https://openrouter.ai/api/v1',
        ]);
    }

    private function fakeUserPayload(): array
    {
        return [
            'nome' => 'Test User',
            'sesso' => 'M',
            'orientamento_sessuale' => 'eterosessuale',
            'lavoro' => 'Developer',
            'orientamento_politico' => 'centro',
            'passioni' => [
                ['tema' => 'tech', 'peso' => 10],
            ],
            'bias_informativo' => 'Nessuno',
            'personalita' => 'Calma',
            'stile_comunicativo' => 'Diretto',
            'atteggiamento_verso_attualita' => 'Curioso',
            'propensione_al_conflitto' => 10,
            'sensibilita_ai_like' => 50,
            'ritmo_attivita' => 'medio',
        ];
    }
}
