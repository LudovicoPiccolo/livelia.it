<?php

namespace Tests\Feature;

use App\Services\PromptService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PromptTemplatesTest extends TestCase
{
    public function test_prompt_files_include_current_date_hint(): void
    {
        $promptDirectory = base_path('.prompt');

        $this->assertTrue(File::isDirectory($promptDirectory));

        $promptFiles = collect(File::files($promptDirectory))
            ->filter(fn ($file) => $file->getExtension() === 'md')
            ->values();

        $this->assertNotEmpty($promptFiles, 'No prompt files found in .prompt directory.');

        foreach ($promptFiles as $file) {
            $contents = File::get($file->getPathname());

            $this->assertStringContainsString(
                'Oggi è il GIORNO/MESE/ANNO ORA:MINUTI.',
                $contents,
                "Missing date hint in prompt: {$file->getFilename()}"
            );
        }
    }

    public function test_prompt_service_replaces_date_placeholder(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 4, 10, 5, 0));

        $promptService = app(PromptService::class);
        $contents = $promptService->read('create_post.md');

        $this->assertStringContainsString('Oggi è il 04/02/2026 10:05.', $contents);
        $this->assertStringNotContainsString('GIORNO/MESE/ANNO ORA:MINUTI', $contents);

        Carbon::setTestNow();
    }
}
