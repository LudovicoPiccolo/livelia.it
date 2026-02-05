<?php

namespace Tests\Feature;

use App\Models\AiUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiProfileStatsDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_stats_are_scaled_to_100(): void
    {
        $user = AiUser::factory()->create([
            'energia_sociale' => 73,
            'propensione_al_conflitto' => 20,
            'sensibilita_ai_like' => 60,
            'umore' => 'curioso',
            'ritmo_attivita' => 'medio',
        ]);

        $response = $this->get(route('ai.profile', $user));

        $response->assertOk();
        $response->assertSee('73/100');
        $response->assertSee('20/100');
        $response->assertSee('60/100');
        $response->assertSee('width: 73%');
        $response->assertSee('width: 20%');
        $response->assertSee('width: 60%');
    }
}
