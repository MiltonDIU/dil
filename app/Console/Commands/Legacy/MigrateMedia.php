<?php

namespace App\Console\Commands\Legacy;

use App\Models\Block;
use App\Models\Course;
use App\Models\Media;
use App\Models\Page;
use App\Models\Post;
use App\Models\Setting;
use App\Models\Slide;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class MigrateMedia extends LegacyCommand
{
    protected $signature = 'legacy:media {--dry-run : Report what would happen without copying anything}';

    protected $description = 'Copy legacy media onto the public disk and NULL dead references';

    private int $copied = 0;

    private int $bytes = 0;

    private int $deadRefs = 0;

    public function handle(): int
    {
        $site = rtrim((string) config('migration.site_path'), '/');

        if (! is_dir($site)) {
            $this->error("LEGACY_SITE_PATH does not exist: {$site}");

            return self::FAILURE;
        }

        $this->copyRoots($site);
        $this->catalogue();
        $this->nullDeadReferences();

        $this->newLine();
        $this->line('  <fg=cyan>Media</>');
        $this->line(sprintf('    copied %d file(s), %s', $this->copied, $this->human($this->bytes)));
        $this->line('    catalogued '.Media::count().' file(s)');
        $this->line(sprintf('    %s%d dead reference(s) NULLed</>',
            $this->deadRefs > 0 ? '<fg=yellow>' : '', $this->deadRefs));

        return self::SUCCESS;
    }

    /**
     * Bulk-copy only the roots that actually hold content.
     *
     * `dilold/media/` is deliberately NOT copied: it is 37 MB of Joomla core
     * JavaScript and CSS, none of which this application uses.
     */
    private function copyRoots(string $site): void
    {
        $root = trim((string) config('migration.media.root'), '/');
        $skipDirs = config('migration.media.skip_dirs', []);
        $skipFiles = config('migration.media.skip_files', []);

        foreach (config('migration.media.copy_dirs', []) as $dir) {
            $from = "{$site}/{$dir}";

            if (! is_dir($from)) {
                $this->warn("  legacy directory missing, skipped: {$dir}");

                continue;
            }

            foreach (File::allFiles($from) as $file) {
                $relative = $dir.'/'.str_replace('\\', '/', $file->getRelativePathname());

                if ($this->shouldSkip($relative, $skipDirs, $skipFiles)) {
                    $this->skip('excluded by config');

                    continue;
                }

                $target = "{$root}/{$relative}";

                if ($this->option('dry-run')) {
                    $this->copied++;
                    $this->bytes += $file->getSize();

                    continue;
                }

                // Re-runs must not re-copy unchanged files, and must never
                // overwrite something an editor has since replaced.
                if (Storage::disk('public')->exists($target)
                    && Storage::disk('public')->size($target) === $file->getSize()) {
                    continue;
                }

                Storage::disk('public')->put($target, File::get($file->getPathname()));

                $this->copied++;
                $this->bytes += $file->getSize();
            }
        }
    }

    private function shouldSkip(string $relative, array $skipDirs, array $skipFiles): bool
    {
        foreach ($skipDirs as $dir) {
            if (str_starts_with($relative, trim($dir, '/').'/')) {
                return true;
            }
        }

        foreach ($skipFiles as $file) {
            if ($relative === $file || basename($relative) === $file) {
                return true;
            }
        }

        return false;
    }

    /**
     * Catalogue what is on the disk, so the admin gets a media library and
     * Phase 8 can report with numbers.
     */
    private function catalogue(): void
    {
        if ($this->option('dry-run')) {
            return;
        }

        $root = trim((string) config('migration.media.root'), '/');

        foreach (Storage::disk('public')->allFiles($root) as $path) {
            $full = Storage::disk('public')->path($path);
            [$width, $height] = @getimagesize($full) ?: [null, null];

            Media::updateOrCreate(
                ['disk' => 'public', 'path' => $path],
                [
                    'filename' => basename($path),
                    'extension' => pathinfo($path, PATHINFO_EXTENSION) ?: null,
                    'mime' => Storage::disk('public')->mimeType($path) ?: null,
                    'size' => Storage::disk('public')->size($path),
                    'width' => $width,
                    'height' => $height,
                    'folder' => dirname($path),
                    'legacy_path' => ltrim(str_replace($root.'/', '', $path), '/'),
                ],
            );
        }
    }

    /**
     * NULL every upload column that points at a file which is not there, and
     * report the count. Silently rendering a broken <img> is worse than an
     * empty slot the admin can see and fix.
     */
    private function nullDeadReferences(): void
    {
        if ($this->option('dry-run')) {
            return;
        }

        $targets = [
            [Slide::class, 'image'],
            [Page::class, 'featured_image'],
            [Page::class, 'og_image'],
            [Post::class, 'featured_image'],
            [Post::class, 'og_image'],
            [Course::class, 'featured_image'],
            [Course::class, 'og_image'],
            [Block::class, 'image'],
        ];

        foreach ($targets as [$model, $column]) {
            foreach ($model::whereNotNull($column)->get() as $record) {
                if (! $record->uploadMissing($record->{$column})) {
                    continue;
                }

                $this->warn("  dead: {$model}#{$record->id}.{$column} = {$record->{$column}}");
                $record->update([$column => null]);
                $this->deadRefs++;
            }
        }

        // Settings that hold an image path (logo, favicon, og image).
        foreach (Setting::where('type', 'image')->whereNotNull('value')->get() as $setting) {
            if ($setting->value && ! Storage::disk('public')->exists($setting->value)) {
                $this->warn("  dead: setting '{$setting->key}' = {$setting->value}");
                $setting->update(['value' => null]);
                $this->deadRefs++;
            }
        }
    }

    private function human(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = $bytes > 0 ? min((int) floor(log($bytes, 1024)), 3) : 0;

        return round($bytes / (1024 ** $i), 1).' '.$units[$i];
    }
}
