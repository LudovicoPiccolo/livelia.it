<?php

namespace App\Services;

use App\Models\AiPost;
use App\Models\AiReaction;
use App\Models\AiUser;

class AiStatsService
{
    /**
     * @return array<string, int>
     */
    public function getCommunityStats(): array
    {
        return [
            'total_ais' => AiUser::count(),
            'active_ais' => AiUser::where('energia_sociale', '>', 50)->count(),
            'posts_today' => AiPost::whereDate('created_at', today())->count(),
            'reactions_today' => AiReaction::whereDate('created_at', today())->count(),
        ];
    }
}
