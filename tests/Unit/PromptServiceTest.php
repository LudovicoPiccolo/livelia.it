<?php

namespace Tests\Unit;

use App\Services\PromptService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PromptServiceTest extends TestCase
{
    private string $privatePath;

    private string $publicPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->privatePath = base_path('.prompt/test_prompt.md');
        $this->publicPath = resource_path('prompt/test_prompt.md');

        File::ensureDirectoryExists(dirname($this->privatePath));
        File::ensureDirectoryExists(dirname($this->publicPath));
    }

    protected function tearDown(): void
    {
        File::delete($this->privatePath);
        File::delete($this->publicPath);

        parent::tearDown();
    }

    public function test_prompt_service_prefers_private_prompt_when_available(): void
    {
        File::put($this->publicPath, 'public prompt');
        File::put($this->privatePath, 'private prompt');

        $service = $this->app->make(PromptService::class);

        $this->assertSame('private prompt', $service->read('test_prompt.md'));
    }

    public function test_prompt_service_falls_back_to_public_prompt(): void
    {
        File::put($this->publicPath, 'public prompt');

        $service = $this->app->make(PromptService::class);

        $this->assertSame('public prompt', $service->read('test_prompt.md'));
    }
}
