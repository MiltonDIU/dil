<?php

namespace App\Console\Commands\Legacy;

use App\Models\Course;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Post;
use App\Models\Redirect;
use Illuminate\Support\Facades\Route;

/**
 * Build the 301 map. This is the difference between keeping and losing the
 * site's search ranking on cutover day.
 *
 * Joomla ran with sef=true and sef_rewrite=false, so every public page was
 * reachable as BOTH:
 *     /index.php/courses/french-language          (SEF path)
 *     /index.php?option=com_content&view=article&id=17   (raw component URL)
 * Both forms are captured.
 */
class MigrateRedirects extends LegacyCommand
{
    protected $signature = 'legacy:redirects';

    protected $description = 'Build the legacy URL -> new URL 301 map';

    private int $unresolved = 0;

    public function handle(): int
    {
        $sources = 0;

        $sources += $this->fromMenuItems();
        $sources += $this->fromArticles();

        $this->newLine();
        $this->line('  <fg=cyan>Redirect map</>');
        $this->line('    total published redirects: '.Redirect::published()->count());

        if ($this->unresolved > 0) {
            $this->line("    <fg=yellow>{$this->unresolved}</> legacy URL(s) had no destination (dropdown parents / trashed targets)");
        }

        return $this->report('Redirects', $sources, Redirect::count());
    }

    /**
     * Every SEF menu path. Trailing-slash variants are handled by the
     * middleware's normalisation, not by duplicate rows.
     */
    private function fromMenuItems(): int
    {
        $items = MenuItem::query()->whereNotNull('legacy_path')->get();

        foreach ($items as $item) {
            $target = $item->is_home ? '/' : $this->targetFor($item);

            if ($target === null) {
                $this->unresolved++;

                continue;
            }

            foreach ($this->legacyPathVariants($item) as $source) {
                $this->put($source, $target, "menu item #{$item->legacy_id}");
            }
        }

        return $items->count();
    }

    /** @return array<int, string> */
    private function legacyPathVariants(MenuItem $item): array
    {
        if ($item->is_home) {
            return ['/index.php', '/index.php/home'];
        }

        $path = trim((string) $item->legacy_path, '/');

        return array_filter([
            '/index.php/'.$path,
            '/'.$path,          // in case sef_rewrite is enabled on the new host
        ]);
    }

    private function targetFor(MenuItem $item): ?string
    {
        $url = $item->resolvedUrl();

        if ($url === null) {
            return null;
        }

        // Store paths, not absolute URLs, so the map survives a domain change.
        $path = parse_url($url, PHP_URL_PATH);

        return is_string($path) && $path !== '' ? $path : '/';
    }

    /**
     * Raw component URLs for every migrated article, including the ones that
     * became courses or posts.
     */
    private function fromArticles(): int
    {
        $source = $this->cfg('pages')['source'];
        $count = 0;

        $map = [
            Page::class => fn (Page $p) => '/'.$p->slug,
            Course::class => fn (Course $c) => '/courses/'.$c->slug,
            Post::class => fn (Post $p) => '/news/'.$p->slug,
        ];

        foreach ($map as $model => $toPath) {
            foreach ($model::query()->where('legacy_source', $source)->get() as $record) {
                $count++;

                $this->put(
                    "/index.php?option=com_content&view=article&id={$record->legacy_id}",
                    $toPath($record),
                    'article '.$record->legacy_id,
                    /*
                     * A 301 pointing at a 404 is worse for SEO than a plain
                     * 404, so redirects to unpublished (legacy-trashed)
                     * content ship disabled. Re-running this command after an
                     * editor publishes the page turns the redirect back on.
                     */
                    enabled: (bool) $record->published,
                );
            }
        }

        // The news category listing.
        foreach ($this->cfg('menus')['category_routes'] as $legacyCatId => $route) {
            if (Route::has($route)) {
                $count++;
                $this->put(
                    "/index.php?option=com_content&view=category&id={$legacyCatId}",
                    parse_url(route($route), PHP_URL_PATH) ?: '/',
                    'category '.$legacyCatId,
                );
            }
        }

        return $count;
    }

    /**
     * Idempotent insert. A redirect an editor has retargeted is left alone,
     * but the enabled flag is kept in step with whether the target is
     * published — so publishing a page in Filament and re-running this command
     * is all it takes to switch its legacy URL back on.
     */
    private function put(string $source, string $target, string $note, bool $enabled = true): void
    {
        $source = Redirect::normalize($source);

        if ($source === Redirect::normalize($target)) {
            return; // would loop
        }

        $existing = Redirect::where('source_hash', hash('sha256', $source))->first();

        if ($existing) {
            if ($existing->target === $target && $existing->published !== $enabled) {
                $existing->update(['published' => $enabled]);
            }

            $this->updated++;

            return;
        }

        Redirect::create([
            'source' => $source,
            'target' => $target,
            'status_code' => 301,
            'published' => $enabled,
            'notes' => 'imported from '.$note
                .($enabled ? '' : ' — disabled: target is unpublished'),
        ]);

        $this->created++;
    }
}
