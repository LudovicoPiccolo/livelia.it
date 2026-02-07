<?php

namespace Tests\Feature;

use App\Models\AiModel;
use App\Models\AiUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChangeUserModelsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_all_users_with_free_models(): void
    {
        AiModel::create([
            'model_id' => 'free-model-1',
            'name' => 'Free Model 1',
            'is_free' => true,
            'is_text' => true,
            'estimated_costs' => 0.0,
        ]);

        AiModel::create([
            'model_id' => 'free-model-2',
            'name' => 'Free Model 2',
            'is_free' => true,
            'is_text' => true,
            'estimated_costs' => 0.0,
        ]);

        AiModel::create([
            'model_id' => 'paid-model-1',
            'name' => 'Paid Model 1',
            'is_free' => false,
            'is_text' => true,
            'estimated_costs' => 0.001,
        ]);

        AiUser::factory()->count(5)->create([
            'generated_by_model' => 'paid-model-1',
            'is_pay' => true,
        ]);

        $this->artisan('change_user_models')->assertExitCode(0);

        $allowedModels = ['free-model-1', 'free-model-2'];
        $users = AiUser::query()->get();

        $this->assertCount(5, $users);

        foreach ($users as $user) {
            $this->assertContains($user->generated_by_model, $allowedModels);
            $this->assertFalse($user->is_pay);
        }
    }

    public function test_it_fails_when_no_free_models_exist(): void
    {
        AiModel::create([
            'model_id' => 'paid-model-1',
            'name' => 'Paid Model 1',
            'is_free' => false,
            'is_text' => true,
            'estimated_costs' => 0.001,
        ]);

        $user = AiUser::factory()->create([
            'generated_by_model' => 'paid-model-1',
            'is_pay' => true,
        ]);

        $this->artisan('change_user_models')->assertExitCode(1);

        $user->refresh();
        $this->assertSame('paid-model-1', $user->generated_by_model);
        $this->assertTrue($user->is_pay);
    }
}
