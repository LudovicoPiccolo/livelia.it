<?php

namespace App\Services;

use App\Models\AiLog;
use App\Models\AiModel;
use App\Models\AiUser;
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
        $this->migrateUsersFromModel($modelId);
    }

    private function migrateUsersFromModel(string $oldModelId): void
    {
        $usersCount = AiUser::where('generated_by_model', $oldModelId)->count();

        if ($usersCount === 0) {
            return;
        }

        Log::info("Migrating {$usersCount} users from suspended model {$oldModelId}.");

        // Find available free models
        $availableModels = AiModel::where('is_free', true)
            ->where(function ($q) {
                $q->whereNull('suspended_until')
                    ->orWhere('suspended_until', '<', now());
            })
            ->where('model_id', '!=', $oldModelId) // Just in case
            ->pluck('model_id');

        if ($availableModels->isEmpty()) {
            Log::error('No available free models to migrate users to!');

            return;
        }

        // Migrate users in chunks
        AiUser::where('generated_by_model', $oldModelId)->chunk(50, function ($users) use ($availableModels) {
            foreach ($users as $user) {
                $newModel = $availableModels->random();
                $user->update(['generated_by_model' => $newModel]);
            }
        });

        Log::info('Migration completed.');
    }
}
