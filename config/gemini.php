<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Gemini API
    |--------------------------------------------------------------------------
    |
    | AI is a finishing tool, never the migration engine. Every AI-backed field
    | has a deterministic raw counterpart, so leaving `key` empty simply means
    | the site renders from raw values — nothing breaks.
    |
    */

    'key' => env('GEMINI_API_KEY'),

    'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),

    'endpoint' => env(
        'GEMINI_ENDPOINT',
        'https://generativelanguage.googleapis.com/v1beta/models'
    ),

    'timeout' => (int) env('GEMINI_TIMEOUT', 120),

    /*
    | Retries use exponential backoff on 429 / 5xx. After the final attempt the
    | service returns null and the caller falls back to the raw value.
    */
    'retries' => (int) env('GEMINI_RETRIES', 4),
    'retry_base_delay' => (int) env('GEMINI_RETRY_BASE_DELAY', 2), // seconds

    'generation' => [
        'temperature' => (float) env('GEMINI_TEMPERATURE', 0.1),
        'topP' => 0.95,
        'maxOutputTokens' => (int) env('GEMINI_MAX_OUTPUT_TOKENS', 32768),
    ],

    /*
    | How many distinct values to send per request. We normalize DISTINCT values
    | in chunks and apply the resulting map in bulk — never one call per row.
    */
    'chunk_size' => (int) env('GEMINI_CHUNK_SIZE', 40),

];
