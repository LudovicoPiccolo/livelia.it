<?php

namespace App\Services;

use App\Models\AiLog;
use App\Models\AiModel;
use App\Models\AiUser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AiModelHealthService
{
    /**
     * Check models for excessive errors and suspend them if necessary.
     */
    public function checkAndSuspendModels(): void
    {
        // 1. Identify models with > 15 errors in the last 24 hours
        // We look at 'model' column in ai_logs. This corresponds to model_id in ai_models usually.

        $badModels = AiLog::query()
            ->select('model')
            ->where('created_at', '>=', now()->subHours(24))
            ->whereNotNull('error_message')
            // You might want to filter specific errors, but "error_message IS NOT NULL" is a good catch-all for now
            // excluding success
            ->groupBy('model')
            ->havingRaw('count(*) > 15')
            ->pluck('model');

        foreach ($badModels as $modelId) {
            $this->suspendModel($modelId);
        }
    }

    public function suspendModel(string $modelId): void
    {
        // Find the model
        $aiModel = AiModel::where('model_id', $modelId)->first();

        // If explicitly found in our DB, check if already suspended
        if ($aiModel) {
            if ($aiModel->suspended_until && $aiModel->suspended_until->isFuture()) {
                // Already suspended, maybe extend? Or just skip.
                // Requirement: "mettilo in sospeso per 12 ore". If already suspended, maybe we leave it or reset timer.
                // Let's assume we don't spam updates if already suspended recently.
                // But if it keeps erroring, maybe it WASN'T suspended or just came back.
                // If it's suspended, it shouldn't be generating errors ideally (unless used by manual override).
                return;
            }

            Log::warning("Suspending AI Model {$modelId} due to excessive errors.");
            $aiModel->update(['suspended_until' => now()->addHours(12)]);
        } else {
            // Model might be deleted or not in our sync table yet, but users might be using it by string ID
            Log::warning("Model {$modelId} flagged for errors but not found in ai_models table.");
        }

        // 3. Move users to other random free models
        $this->migrateUsersFromModel($modelId, true);
    }

    public function migrateUsersFromModelToAffordable(string $oldModelId): void
    {
        $this->migrateUsersFromModel($oldModelId, false);
    }

    private function migrateUsersFromModel(string $oldModelId, bool $onlyFree): void
    {
        $usersCount = AiUser::where('generated_by_model', $oldModelId)->count();

        if ($usersCount === 0) {
            return;
        }

        Log::info("Migrating {$usersCount} users from model {$oldModelId}.");

        // Find available models
        $availableModels = $this->getAvailableModels($oldModelId, $onlyFree);

        if ($availableModels->isEmpty()) {
            Log::error('No available models to migrate users to!');

            return;
        }

        // Migrate users in chunks
        AiUser::where('generated_by_model', $oldModelId)->chunk(50, function ($users) use ($availableModels) {
            foreach ($users as $user) {
                $newModel = $availableModels->random();
                $user->update([
                    'generated_by_model' => $newModel->model_id,
                    'is_pay' => $this->resolveIsPay($newModel),
                ]);
            }
        });

        Log::info('Migration completed.');
    }

    private function getAvailableModels(string $oldModelId, bool $onlyFree): Collection
    {
        $maxEstimatedCost = (float) config('livelia.ai_models.max_estimated_cost', 0.002);

        return AiModel::query()
            ->select(['model_id', 'is_free', 'estimated_costs'])
            ->where(function ($query) use ($onlyFree, $maxEstimatedCost) {
                $query->where('is_free', true);

                if (! $onlyFree) {
                    $query->orWhere(function ($query) use ($maxEstimatedCost) {
                        $query->where('estimated_costs', '>', 0)
                            ->where('estimated_costs', '<=', $maxEstimatedCost);
                    });
                }
            })
            ->where(function ($query) {
                $query->whereNull('suspended_until')
                    ->orWhere('suspended_until', '<', now());
            })
            ->where('model_id', '!=', $oldModelId)
            ->get();
    }

    private function resolveIsPay(AiModel $model): bool
    {
        if ($model->is_free) {
            return false;
        }

        if ($model->estimated_costs === null) {
            return false;
        }

        return (float) $model->estimated_costs > 0;
    }
}
