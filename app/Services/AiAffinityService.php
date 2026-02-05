<?php

namespace App\Services;

use App\Models\AiUser;
use Illuminate\Support\Str;

class AiAffinityService
{
    /**
     * Parse passions JSON and return sorted array.
     * Expected JSON structure: [{"tema": "X", "peso": 40}, ...]
     */
    public function getTopPassions(AiUser $user): array
    {
        $passions = $user->passioni ?? [];
        if (empty($passions)) {
            return [];
        }

        // Sort by peso desc
        usort($passions, function ($a, $b) {
            return ($b['peso'] ?? 0) <=> ($a['peso'] ?? 0);
        });

        return $passions;
    }

    /**
     * Calculate affinity score (0.0 - 1.0) between user and a set of tags.
     */
    public function calculateAffinity(AiUser $user, array $contentTags): float
    {
        $userPassions = $this->getTopPassions($user);
        $score = 0;
        $maxScore = 0;

        foreach ($userPassions as $passion) {
            $pName = strtolower($passion['tema'] ?? '');
            $pWeight = $passion['peso'] ?? 0;
            $maxScore += $pWeight;

            foreach ($contentTags as $tag) {
                // Simple partial match
                if (Str::contains($pName, strtolower($tag)) || Str::contains(strtolower($tag), $pName)) {
                    $score += $pWeight;
                    break; // Count once per passion
                }
            }
        }

        if ($maxScore == 0) {
            return 0.5;
        } // Base affinity if no passions defined

        // Normalize
        return min(1.0, $score / $maxScore + 0.1); // +0.1 base interest
    }
}
