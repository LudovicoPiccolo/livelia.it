<?php

namespace App\Services;

use App\Models\AiLog;
use Exception;
use Illuminate\Support\Facades\Http;

class AiService
{
    /**
     * Generate JSON content using an AI model.
     *
     * @throws Exception
     */
    public function generateJson(string $prompt, string $modelId, ?string $promptPath = null): array
    {
        $apiKey = config('services.ai.api_key');
        $baseUrl = config('services.ai.base_url');

        if (empty($apiKey)) {
            throw new Exception('AI API Key is not configured.');
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'HTTP-Referer' => config('app.url'),
                'X-Title' => config('app.name'),
                'Content-Type' => 'application/json',
            ])->timeout(60)->post($baseUrl.'/chat/completions', [
                'model' => $modelId,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            $statusCode = $response->status();
            $fullResponse = $response->json();
            $content = $fullResponse['choices'][0]['message']['content'] ?? null;
            $errorMessage = null;

            if ($response->failed()) {
                $errorMessage = 'API Request failed: '.$response->body();
            } elseif (empty($content)) {
                $errorMessage = 'Received empty content from AI.';
            }

            // Log the attempt
            $log = AiLog::create([
                'model' => $modelId,
                'input_prompt' => $prompt,
                'output_content' => $content,
                'full_response' => $fullResponse ?? ['raw_body' => $response->body()],
                'status_code' => $statusCode,
                'error_message' => $errorMessage,
                'prompt_file' => $promptPath,
            ]);

            if ($errorMessage) {
                throw new Exception($errorMessage);
            }

            // Clean up content
            $jsonString = str_replace(['```json', '```'], '', $content);
            $data = json_decode($jsonString, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $parseError = 'Failed to parse JSON response: '.json_last_error_msg();
                $log->update(['error_message' => $parseError]);
                throw new Exception($parseError.' Raw: '.$content);
            }

            return $data;

        } catch (Exception $e) {
            // If it's an exception we haven't logged yet (e.g. connection error BEFORE getting a response)
            // We should log it if we can, but if it came from the logic above, it might be double logged or logged differently.
            // Actually, the logic above logs HTTP failures.
            // If Http::post throws (e.g. timeout), we catch it here.

            // Allow re-throwing logic errors from above
            if (isset($log) && $log->error_message) {
                throw $e;
            }

            // Log unexpected exceptions (connection, timeout)
            if (! isset($log)) {
                AiLog::create([
                    'model' => $modelId,
                    'input_prompt' => $prompt,
                    'output_content' => null,
                    'full_response' => null,
                    'status_code' => 0,
                    'error_message' => $e->getMessage(),
                    'prompt_file' => $promptPath,
                ]);
            }

            throw $e;
        }
    }
}
