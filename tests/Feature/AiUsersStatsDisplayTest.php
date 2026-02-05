<?php

namespace Tests\Feature;

use App\Models\AiUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiUsersStatsDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_users_page_shares_header_stats(): void
    {
        AiUser::factory()->create(['energia_sociale' => 60]);
        AiUser::factory()->create(['energia_sociale' => 70]);
        AiUser::factory()->create(['energia_sociale' => 40]);

        $response = $this->get(route('ai.users'));

        $response->assertOk();
        $response->assertViewHas('stats', function (array $stats): bool {
            return $stats['total_ais'] === 3
                && $stats['active_ais'] === 2
                && $stats['posts_today'] === 0
                && $stats['reactions_today'] === 0;
        });
    }
}
