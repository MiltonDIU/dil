<?php

namespace App\Console\Commands\Legacy;

use Illuminate\Console\Command;

/**
 * Orchestrator — runs every import step in dependency order.
 *
 * Every step is idempotent, so this whole command is safe to re-run at any
 * time while the mapping is being tuned.
 *
 * NEVER run this on production. Production receives the finished database and
 * media; a re-run there against live data is the one thing that can destroy
 * editor changes.
 */
class MigrateAll extends Command
{
    protected $signature = 'legacy:migrate
        {--with-ai       : Also run the Gemini normalization commands}
        {--skip-media    : Do not copy or re-check media files}
        {--only=*        : Run only these steps (e.g. --only=pages --only=menus)}
        {--dry-run       : Pass --dry-run to steps that support it}';

    protected $description = 'Run the full legacy import in dependency order';

    /**
     * Dependency order matters:
     *   settings  — no deps
     *   users     — no deps
     *   categories— no deps (parents remapped in its own second pass)
     *   pages / courses / posts — need categories
     *   menus     — need pages + courses + posts for link resolution
     *   blocks    — need pages + courses to resolve button links
     *   slides    — independent, but images are checked by media
     *   forms     — need settings (notify email)
     *   redirects — need menus + all content
     *   media     — last, so every image column exists to be checked
     *
     * @var array<string, string>
     */
    private const STEPS = [
        'settings' => 'legacy:settings',
        'users' => 'legacy:users',
        'categories' => 'legacy:categories',
        'pages' => 'legacy:pages',
        'courses' => 'legacy:courses',
        'posts' => 'legacy:posts',
        'menus' => 'legacy:menus',
        'blocks' => 'legacy:blocks',
        'slides' => 'legacy:slides',
        'forms' => 'legacy:forms',
        'redirects' => 'legacy:redirects',
        'media' => 'legacy:media',
    ];

    /** @var array<string, string> */
    private const AI_STEPS = [
        'ai-courses' => 'ai:extract-courses',
        'ai-seo' => 'ai:generate-seo',
        'ai-alt' => 'ai:generate-alt',
    ];

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('legacy:migrate must never run on production.');
            $this->line('Production receives the finished DB + media. See PRODUCTION.md.');

            return self::FAILURE;
        }

        $only = (array) $this->option('only');
        $steps = self::STEPS;

        if ($this->option('skip-media')) {
            unset($steps['media']);
        }

        if ($this->option('with-ai')) {
            $steps = [...$steps, ...self::AI_STEPS];
        }

        if ($only !== []) {
            $unknown = array_diff($only, array_keys([...self::STEPS, ...self::AI_STEPS]));

            if ($unknown !== []) {
                $this->error('Unknown step(s): '.implode(', ', $unknown));
                $this->line('Available: '.implode(', ', array_keys([...self::STEPS, ...self::AI_STEPS])));

                return self::FAILURE;
            }

            $steps = array_intersect_key([...self::STEPS, ...self::AI_STEPS], array_flip($only));
        }

        $started = microtime(true);
        $failed = [];

        foreach ($steps as $name => $command) {
            $this->newLine();
            $this->line("<fg=black;bg=cyan> {$name} </> <fg=gray>{$command}</>");

            $options = [];

            if ($this->option('dry-run') && $this->supportsDryRun($command)) {
                $options['--dry-run'] = true;
            }

            $exit = $this->call($command, $options);

            if ($exit !== self::SUCCESS) {
                $failed[] = $name;
                $this->error("  step '{$name}' failed (exit {$exit})");
            }
        }

        $this->newLine();
        $this->line(sprintf(
            '<fg=cyan>Finished %d step(s) in %.1fs</>',
            count($steps), microtime(true) - $started,
        ));

        if ($failed !== []) {
            $this->error('Failed steps: '.implode(', ', $failed));

            return self::FAILURE;
        }

        $this->newLine();
        $this->line('Run <fg=yellow>php artisan legacy:verify</> for the full parity report.');

        return self::SUCCESS;
    }

    private function supportsDryRun(string $command): bool
    {
        return str_starts_with($command, 'ai:') || $command === 'legacy:media';
    }
}
