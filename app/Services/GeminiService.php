<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * One place every AI command goes through.
 *
 * Contract:
 *   - structured output only (responseSchema), so callers never regex prose;
 *   - retries with exponential backoff on 429/5xx;
 *   - returns NULL on failure, so a caller always falls back to the raw value.
 *
 * A failed AI call must never destroy data or abort a migration.
 */
class GeminiService
{
    private int $promptTokens = 0;

    private int $outputTokens = 0;

    private int $calls = 0;

    public function isConfigured(): bool
    {
        return filled(config('gemini.key'));
    }

    /**
     * Ask for a JSON object matching $schema. Returns null on any failure.
     *
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>|null
     */
    public function json(string $prompt, array $schema, ?string $systemInstruction = null): ?array
    {
        if (! $this->isConfigured()) {
            Log::warning('Gemini: no API key configured, returning null.');

            return null;
        }

        $payload = [
            'contents' => [[
                'role' => 'user',
                'parts' => [['text' => $prompt]],
            ]],
            'generationConfig' => array_merge(config('gemini.generation'), [
                'responseMimeType' => 'application/json',
                'responseSchema' => $schema,
            ]),
        ];

        if ($systemInstruction !== null) {
            $payload['systemInstruction'] = ['parts' => [['text' => $systemInstruction]]];
        }

        $url = sprintf(
            '%s/%s:generateContent',
            rtrim((string) config('gemini.endpoint'), '/'),
            config('gemini.model'),
        );

        $attempts = max(1, (int) config('gemini.retries'));

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $response = Http::timeout((int) config('gemini.timeout'))
                    ->withHeaders(['x-goog-api-key' => config('gemini.key')])
                    ->acceptJson()
                    ->post($url, $payload);

                if ($response->successful()) {
                    return $this->decode($response->json());
                }

                // 4xx other than 429 will not improve on retry.
                if ($response->status() !== 429 && $response->status() < 500) {
                    Log::error('Gemini: non-retryable error', [
                        'status' => $response->status(),
                        'body' => mb_substr($response->body(), 0, 500),
                    ]);

                    return null;
                }

                Log::warning('Gemini: retryable error', [
                    'status' => $response->status(),
                    'attempt' => $attempt,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Gemini: request threw', [
                    'attempt' => $attempt,
                    'message' => $e->getMessage(),
                ]);
            }

            if ($attempt < $attempts) {
                sleep((int) config('gemini.retry_base_delay') * (2 ** ($attempt - 1)));
            }
        }

        Log::error("Gemini: giving up after {$attempts} attempts.");

        return null;
    }

    /**
     * @param  array<string, mixed>|null  $body
     * @return array<string, mixed>|null
     */
    private function decode(?array $body): ?array
    {
        $usage = $body['usageMetadata'] ?? [];
        $this->promptTokens += (int) ($usage['promptTokenCount'] ?? 0);
        $this->outputTokens += (int) ($usage['candidatesTokenCount'] ?? 0);
        $this->calls++;

        $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (! is_string($text)) {
            Log::error('Gemini: response had no text part', [
                'finishReason' => $body['candidates'][0]['finishReason'] ?? null,
            ]);

            return null;
        }

        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            Log::error('Gemini: response was not valid JSON', [
                'sample' => mb_substr($text, 0, 300),
            ]);

            return null;
        }

        return $decoded;
    }

    /** @return array{calls:int, prompt_tokens:int, output_tokens:int} */
    public function usage(): array
    {
        return [
            'calls' => $this->calls,
            'prompt_tokens' => $this->promptTokens,
            'output_tokens' => $this->outputTokens,
        ];
    }
}
