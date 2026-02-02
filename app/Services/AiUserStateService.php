<?php

namespace App\Services;

use App\Models\AiUser;

class AiUserStateService
{
    /**
     * Consume energy from a user after an action.
     */
    public function consumeEnergy(AiUser $user, int $amount): void
    {
        // First regenerate energy based on time elapsed since last action
        $this->regenerateEnergy($user);

        $newEnergy = max(0, $user->energia_sociale - $amount);
        $user->energia_sociale = $newEnergy;
        $user->last_action_at = now();
        $user->save();
    }

    /**
     * Regenerate energy based on time passed.
     * This should be called before consuming or periodically.
     */
    public function regenerateEnergy(AiUser $user): void
    {
        if (! $user->last_action_at) {
            $user->energia_sociale = config('livelia.energy.max', 100);

            return;
        }

        $hoursPassed = $user->last_action_at->diffInHours(now());

        if ($hoursPassed >= 1) {
            $regenAmount = $hoursPassed * config('livelia.energy.regen_per_hour', 5);
            $newEnergy = min(config('livelia.energy.max', 100), $user->energia_sociale + $regenAmount);

            // Should we save here? Yes, to checkpoint regeneration
            if ($newEnergy != $user->energia_sociale) {
                $user->energia_sociale = $newEnergy;
                // Note: we don't update last_action_at here, purely energy update
                // But wait, if we consume immediately after, it's fine.
            }
        }
    }

    /**
     * Set cooldown for a user.
     */
    public function setCooldown(AiUser $user, int $minutes): void
    {
        $user->cooldown_until = now()->addMinutes($minutes);
        $user->save();
    }

    /**
     * Check if user represents a valid actor right now.
     */
    public function canAct(AiUser $user): bool
    {
        // Check cooldown
        if ($user->cooldown_until && $user->cooldown_until->isFuture()) {
            return false;
        }

        // Check energy (if too low, maybe force rest? or just lower probability?)
        // For now, let's say hard limit at 5 energy?
        if ($user->energia_sociale < 5) {
            return false;
        }

        return true;
    }

    /**
     * Update mood based on recent events (placeholder).
     */
    public function updateMood(AiUser $user): void
    {
        // Logic to change mood based on reactions received, arguments, etc.
        // For now, keep it simple.
        // Maybe random fluctuation?
        $moods = ['neutro', 'felice', 'triste', 'arrabbiato', 'annoiato', 'polemico'];

        // 5% chance to change mood randomly if nothing specific happened
        if (rand(1, 100) <= 5) {
            $user->umore = $moods[array_rand($moods)];
            $user->save();
        }
    }
}
