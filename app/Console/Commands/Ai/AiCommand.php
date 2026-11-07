<?php

namespace App\Console\Commands\Ai;

use App\Models\AiMapping;
use App\Services\GeminiService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Shared behaviour for every `ai:*` command.
 *
 * Every AI command: supports --dry-run and --force, caches its decisions in
 * `ai_mappings` so re-runs are deterministic and human corrections stick, sets
 * an `ai_*` flag on what it touched, and logs a before/after sample.
 */
abstract class AiCommand extends Command
{
    protected int $fromCache = 0;

    protected int $fromApi = 0;

    protected int $failed = 0;

    /** @var array<int, array{label:string, before:string, after:string}> */
    protected array $samples = [];

    public function __construct(protected GeminiService $gemini)
    {
        parent::__construct();
    }

    protected function guardConfigured(): bool
    {
        if ($this->gemini->isConfigured()) {
            return true;
        }

        $this->warn('GEMINI_API_KEY is not set — skipping.');
        $this->line('This is not an error: every AI-backed field has a raw counterpart,');
        $this->line('so the site renders correctly without it.');

        return false;
    }

    /**
     * Cached lookup, then API. A human-reviewed mapping is never re-asked.
     *
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>|null
     */
    protected function askGemini(string $type, string $input, string $prompt, array $schema, ?string $system = null): ?array
    {
        if (! $this->option('force')) {
            $cached = AiMapping::lookup($type, $input);

            if ($cached !== null) {
                $this->fromCache++;

                return $cached;
            }
        }

        if ($this->option('dry-run')) {
            $this->line('  <fg=gray>[dry-run] would call Gemini for: '.Str::limit($input, 60).'</>');

            return null;
        }

        $result = $this->gemini->json($prompt, $schema, $system);

        if ($result === null) {
            $this->failed++;

            return null;
        }

        $this->fromApi++;

        AiMapping::remember($type, $input, $result, config('gemini.model'), (bool) $this->option('force'));

        return $result;
    }

    protected function sample(string $label, ?string $before, ?string $after): void
    {
        if (count($this->samples) < 10) {
            $this->samples[] = [
                'label' => $label,
                'before' => Str::limit((string) $before, 70) ?: '—',
                'after' => Str::limit((string) $after, 70) ?: '—',
            ];
        }
    }

    protected function reportAi(string $label, int $touched): int
    {
        $this->newLine();
        $this->line("  <fg=cyan>{$label}</>");
        $this->line(sprintf(
            '    %d record(s) updated   (%d from cache, %d from API, %d failed)',
            $touched, $this->fromCache, $this->fromApi, $this->failed,
        ));

        if ($this->failed > 0) {
            $this->line('    <fg=yellow>failed calls fell back to the raw value — no data was lost</>');
        }

        $usage = $this->gemini->usage();

        if ($usage['calls'] > 0) {
            $this->line(sprintf(
                '    tokens: %d prompt + %d output over %d call(s)',
                $usage['prompt_tokens'], $usage['output_tokens'], $usage['calls'],
            ));
        }

        if ($this->samples !== []) {
            $this->newLine();
            $this->line('  <fg=cyan>Before / after sample</>');

            foreach ($this->samples as $s) {
                $this->line("    <fg=gray>{$s['label']}</>");
                $this->line("      before: {$s['before']}");
                $this->line("      after:  {$s['after']}");
            }
        }

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->warn('  --dry-run: nothing was written.');
        }

        return self::SUCCESS;
    }
}
