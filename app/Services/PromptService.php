<?php

namespace App\Services;

use RuntimeException;

class PromptService
{
    private const DATE_HINT_PLACEHOLDER = 'Oggi è il GIORNO/MESE/ANNO ORA:MINUTI';

    public function read(string $fileName): string
    {
        $path = $this->resolvePath($fileName);

        if (! is_file($path)) {
            throw new RuntimeException("Prompt file not found for: {$fileName}");
        }

        $content = file_get_contents($path);

        if ($content === false) {
            throw new RuntimeException("Unable to read prompt file for: {$fileName}");
        }

        return $this->replaceDateHint($content);
    }

    public function resolvePath(string $fileName): string
    {
        $privatePath = $this->buildPrivatePath($fileName);
        if (is_file($privatePath)) {
            return $privatePath;
        }

        return $this->buildPublicPath($fileName);
    }

    private function buildPrivatePath(string $fileName): string
    {
        $basePath = (string) config('livelia.prompts.private_path');

        return rtrim($basePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$fileName;
    }

    private function buildPublicPath(string $fileName): string
    {
        $basePath = (string) config('livelia.prompts.public_path');

        return rtrim($basePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$fileName;
    }

    private function replaceDateHint(string $content): string
    {
        $formattedDate = now()->format('d/m/Y H:i');
        $replacement = "Oggi è il {$formattedDate}.";

        return str_replace(
            [self::DATE_HINT_PLACEHOLDER.'.', self::DATE_HINT_PLACEHOLDER],
            [$replacement, $replacement],
            $content
        );
    }
}
