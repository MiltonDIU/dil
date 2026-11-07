<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/**
 * Build the deploy/ bundle so go-live is a file transfer, not improvisation.
 *
 * Ships the FINISHED database and media. The legacy Joomla database and its
 * 42 MB source tree have no business on production, and re-running the import
 * against live data is the one thing that can destroy editor changes.
 */
class BuildDeployBundle extends Command
{
    protected $signature = 'deploy:bundle
        {--skip-assets : Do not run npm build (assumes public/build is current)}';

    protected $description = 'Build deploy/ — release.zip, db.sql.gz, media.tar.gz, .env.production.example';

    public function handle(): int
    {
        $root = base_path();
        $out = base_path('deploy');

        File::ensureDirectoryExists($out);

        if (! $this->option('skip-assets')) {
            $this->line('Building front-end assets (shared hosting has no Node)…');

            $build = Process::path($root)->timeout(600)->run('npm run build');

            if (! $build->successful()) {
                $this->error('npm run build failed:');
                $this->line($build->errorOutput());

                return self::FAILURE;
            }
        }

        if (! is_dir("{$root}/public/build")) {
            $this->error('public/build is missing. Run `npm run build` before deploying.');

            return self::FAILURE;
        }

        $this->releaseZip($root, $out);
        $this->databaseDump($out);
        $this->mediaArchive($root, $out);
        $this->envExample($root, $out);

        $this->newLine();
        $this->line('<fg=cyan>deploy/ contents</>');

        foreach (File::files($out) as $file) {
            $this->line(sprintf('  %-28s %s', $file->getFilename(), $this->human($file->getSize())));
        }

        $this->newLine();
        $this->line('Follow <fg=yellow>PRODUCTION.md</> to install. Never run legacy:migrate on the server.');

        return self::SUCCESS;
    }

    private function releaseZip(string $root, string $out): void
    {
        $this->line('Packaging release.zip (app + vendor + built assets)…');

        $target = "{$out}/release.zip";
        File::delete($target);

        // Composer is not guaranteed on shared hosting, so vendor/ ships with
        // the release. node_modules, tests and local state do not.
        $exclude = [
            '*node_modules*', '*.git*', '*deploy/*', '*storage/app/public/media/*',
            '*storage/logs/*', '*storage/framework/cache/*', '*storage/framework/sessions/*',
            '*storage/framework/views/*', '*.env', '*tests/*', '*.DS_Store', '*dilold*',
        ];

        $args = implode(' ', array_map(fn ($p) => '-x '.escapeshellarg($p), $exclude));

        Process::path($root)->timeout(900)->run("zip -rq {$target} . {$args}");
    }

    private function databaseDump(string $out): void
    {
        $this->line('Dumping the finished application database…');

        $db = config('database.connections.mysql');
        $target = "{$out}/db.sql.gz";

        /*
         * --no-tablespaces keeps the dump importable by a cPanel MySQL user,
         * which lacks the PROCESS privilege mysqldump otherwise wants.
         */
        $command = sprintf(
            'mysqldump --no-tablespaces --default-character-set=utf8mb4 '.
            '--single-transaction --host=%s --port=%s --user=%s %s %s | gzip -9 > %s',
            escapeshellarg($db['host']),
            escapeshellarg((string) $db['port']),
            escapeshellarg($db['username']),
            $db['password'] ? '--password='.escapeshellarg($db['password']) : '',
            escapeshellarg($db['database']),
            escapeshellarg($target),
        );

        $result = Process::timeout(600)->run(['bash', '-c', $command]);

        if (! $result->successful() || ! is_file($target)) {
            $this->warn('  mysqldump is not available locally — falling back to the Docker container.');

            $docker = sprintf(
                'docker exec %s mysqldump --no-tablespaces --default-character-set=utf8mb4 '.
                '-u%s -p%s %s | gzip -9 > %s',
                escapeshellarg(env('DOCKER_MYSQL_CONTAINER', 'topupc-mysql-1')),
                escapeshellarg($db['username']),
                escapeshellarg($db['password']),
                escapeshellarg($db['database']),
                escapeshellarg($target),
            );

            Process::timeout(600)->run(['bash', '-c', $docker]);
        }
    }

    private function mediaArchive(string $root, string $out): void
    {
        $this->line('Archiving storage/app/public/media…');

        $media = "{$root}/storage/app/public/media";

        if (! is_dir($media)) {
            $this->warn('  no media directory — skipping.');

            return;
        }

        Process::path("{$root}/storage/app/public")->timeout(600)
            ->run('tar -czf '.escapeshellarg("{$out}/media.tar.gz").' media');
    }

    private function envExample(string $root, string $out): void
    {
        $this->line('Writing .env.production.example…');

        $source = is_file("{$root}/.env.example") ? "{$root}/.env.example" : "{$root}/.env";
        $lines = [];

        foreach (file($source, FILE_IGNORE_NEW_LINES) as $line) {
            // Every secret is blanked; nothing sensitive leaves the machine.
            if (preg_match('/^(APP_KEY|DB_PASSWORD|MAIL_PASSWORD|GEMINI_API_KEY|LEGACY_DB_PASSWORD)=/', $line, $m)) {
                $lines[] = $m[1].'=';

                continue;
            }

            $lines[] = $line;
        }

        $content = implode("\n", $lines);

        $content = preg_replace('/^APP_ENV=.*$/m', 'APP_ENV=production', $content);
        $content = preg_replace('/^APP_DEBUG=.*$/m', 'APP_DEBUG=false', $content);
        $content = preg_replace('/^APP_URL=.*$/m', 'APP_URL=https://your-domain.example', $content);
        $content = preg_replace('/^LOG_LEVEL=.*$/m', 'LOG_LEVEL=error', $content);
        $content = preg_replace('/^MAIL_MAILER=.*$/m', 'MAIL_MAILER=smtp', $content);

        /*
         * The legacy connection and Gemini key are development-only. They are
         * commented out rather than deleted so it is obvious they were
         * considered and deliberately excluded from production.
         */
        $content = preg_replace('/^(LEGACY_[A-Z_]+=.*)$/m', '# $1   # dev only — the migration never runs on production', $content);
        $content = preg_replace('/^(GEMINI_[A-Z_]+=.*)$/m', '# $1   # dev only', $content);

        File::put("{$out}/.env.production.example", rtrim($content)."\n");
    }

    private function human(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = $bytes > 0 ? min((int) floor(log($bytes, 1024)), 3) : 0;

        return round($bytes / (1024 ** $i), 1).' '.$units[$i];
    }
}
