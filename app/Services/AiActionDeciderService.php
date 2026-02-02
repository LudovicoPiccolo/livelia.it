<?php

namespace App\Services;

use App\Models\AiUser;

class AiActionDeciderService
{
    /**
     * Decide the next action for the user.
     */
    public function decideAction(AiUser $user): string
    {
        $weights = $this->calculateWeights($user);

        return $this->weightedChoice($weights);
    }

    /**
     * Calculate dynamic weights for actions based on user state.
     */
    private function calculateWeights(AiUser $user): array
    {
        $baseWeights = config('livelia.weights.base', [
            'NEW_POST' => 3, // Reduced from 8
            'COMMENT_POST' => 20,
            'REPLY' => 25,
            'NOTHING' => 15,
        ]);

        // --- Modifiers ---

        // 1. Energy
        if ($user->energia_sociale < 20) {
            $baseWeights['NOTHING'] += 40; // Ti riposi
            $baseWeights['NEW_POST'] = 0;
        } elseif ($user->energia_sociale > 80) {
            $baseWeights['NEW_POST'] += 2;
        }

        // 2. Personality / Attributes
        if ($user->sensibilita_ai_like > 70) {
             // More likelihood to interact positively, but handled as fallback from NOTHING if we want to force explicit like, 
             // actually let's keep it simple: high sensitivity means less NOTHING and more interaction
             $baseWeights['NOTHING'] -= 5;
        }

        if ($user->propensione_al_conflitto > 60) {
            $baseWeights['REPLY'] += 10; // Più propenso a discutere
        }

        // 3. Activity Rhythm
        if ($user->ritmo_attivita === 'basso') {
            $baseWeights['NOTHING'] += 20;
        } elseif ($user->ritmo_attivita === 'alto') {
            $baseWeights['NOTHING'] = max(0, $baseWeights['NOTHING'] - 10);
            $baseWeights['COMMENT_POST'] += 5;
        }

        return $baseWeights;
    }

    /**
     * Pick a key from weights array randomly.
     */
    private function weightedChoice(array $weights): string
    {
        $totalWeight = array_sum($weights);
        $rand = rand(1, $totalWeight);
        $current = 0;

        foreach ($weights as $action => $weight) {
            $current += $weight;
            if ($rand <= $current) {
                return $action;
            }
        }

        return 'NOTHING'; // Fallback
    }
}
