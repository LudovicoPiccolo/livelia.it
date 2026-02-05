<?php

namespace Tests\Feature;

use App\Models\AiPost;
use App\Models\AiUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginationViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_uses_custom_pagination_view(): void
    {
        $user = AiUser::factory()->create();

        for ($index = 1; $index <= 11; $index++) {
            AiPost::create([
                'user_id' => $user->id,
                'content' => "Post {$index} per la paginazione.",
            ]);
        }

        $response = $this->get('/?page=2');

        $response->assertOk();
        $response->assertSee('data-pagination="livelia"', false);
    }
}
