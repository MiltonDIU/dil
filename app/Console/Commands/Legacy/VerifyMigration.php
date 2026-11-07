<?php

namespace App\Console\Commands\Legacy;

use App\Models\Block;
use App\Models\Category;
use App\Models\Course;
use App\Models\Form;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Post;
use App\Models\Redirect;
use App\Models\Setting;
use App\Models\Slide;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * The Phase 8 checklist, as numbers rather than assurances.
 *
 * Nothing here is a claim of success on its own — every section prints counts
 * that can be checked against the legacy database, and the command exits
 * non-zero if a hard parity check fails.
 */
class VerifyMigration extends Command
{
    protected $signature = 'legacy:verify
        {--url= : Base URL of a running site, to crawl for internal 404s}';

    protected $description = 'Report source-vs-target parity, dead media, published state and link health';

    private int $failures = 0;

    public function handle(): int
    {
        $this->parity();
        $this->publishedState();
        $this->media();
        $this->encoding();
        $this->redirects();
        $this->ai();
        $this->dynamic();
        $this->emptyContent();

        if ($this->option('url')) {
            $this->crawl(rtrim($this->option('url'), '/'));
        } else {
            $this->newLine();
            $this->line('  <fg=gray>Skipping link crawl. Pass --url=http://127.0.0.1:8000 to run it.</>');
        }

        $this->newLine();

        if ($this->failures > 0) {
            $this->error("  {$this->failures} check(s) FAILED — see above.");

            return self::FAILURE;
        }

        $this->info('  All parity checks passed.');

        return self::SUCCESS;
    }

    private function legacy(string $table)
    {
        return DB::connection(config('migration.connection'))->table($table);
    }

    private function heading(string $title): void
    {
        $this->newLine();
        $this->line("<fg=black;bg=cyan> {$title} </>");
    }

    private function check(string $label, int $source, int $target, string $note = ''): void
    {
        $ok = $source === $target;

        if (! $ok && $note === '') {
            $this->failures++;
        }

        $this->line(sprintf(
            '  %s %-26s source %-5d target %-5d %s',
            $ok ? '<fg=green>✓</>' : ($note ? '<fg=yellow>~</>' : '<fg=red>✗</>'),
            $label, $source, $target, $note ? "<fg=gray>{$note}</>" : '',
        ));
    }

    private function parity(): void
    {
        $this->heading('1. Row parity');

        $articles = $this->legacy('content')->count();
        $migrated = Page::count() + Course::count() + Post::count();

        $this->check('Articles (total)', $articles, $migrated);
        $this->check('  → pages', $this->legacy('content')->where('catid', 2)
            ->whereNotIn('id', config('migration.courses.legacy_ids'))->count(), Page::count());
        $this->check('  → courses', count(config('migration.courses.legacy_ids')), Course::count());
        $this->check('  → posts (news)', $this->legacy('content')->where('catid', 8)->count(), Post::count());

        $this->check('Menu items', $this->legacy('menu')->where('client_id', 0)->where('id', '>', 1)->count(), MenuItem::count());
        $this->check('Menus', $this->legacy('menu_types')->where('client_id', 0)->count(), Menu::count());
        $this->check('Users', $this->legacy('users')->count(), User::count());

        $this->check(
            'Categories',
            $this->legacy('categories')->count(),
            Category::count(),
            'legacy count includes 5 empty per-extension stubs + nested-set ROOT',
        );

        $this->check('Slides', 2, Slide::count(), 'from the SPPB carousel addon');
        $this->check('Homepage blocks', 8, Block::count(), 'testimonial dropped (JoomShaper demo data)');
    }

    private function publishedState(): void
    {
        $this->heading('2. Published-state parity');

        foreach ([
            'Pages' => [Page::class, 2, config('migration.courses.legacy_ids')],
            'Courses' => [Course::class, 2, null],
            'Posts' => [Post::class, 8, null],
        ] as $label => [$model, $catid, $excludeIds]) {
            $query = $this->legacy('content')->where('catid', $catid)->where('state', 1);

            if ($label === 'Pages') {
                $query->whereNotIn('id', $excludeIds);
            }

            if ($label === 'Courses') {
                $query = $this->legacy('content')
                    ->whereIn('id', config('migration.courses.legacy_ids'))->where('state', 1);
            }

            $this->check("{$label} published", $query->count(), $model::where('published', true)->count());
        }

        $trashed = $this->legacy('content')->where('state', -2)->count();
        $unpublished = Page::where('published', false)->count()
            + Course::where('published', false)->count()
            + Post::where('published', false)->count();

        $this->check('Trashed → unpublished', $trashed, $unpublished,
            'trashed content is imported unpublished, per the approved plan');

        $this->check(
            'Menu items published',
            $this->legacy('menu')->where('client_id', 0)->where('id', '>', 1)->where('published', 1)->count(),
            MenuItem::where('published', true)->count(),
        );
    }

    private function media(): void
    {
        $this->heading('3. Media');

        $root = trim((string) config('migration.media.root'), '/');
        $onDisk = count(Storage::disk('public')->allFiles($root));

        $this->line("  files on the public disk: {$onDisk}");

        $dead = 0;
        $checks = [
            [Slide::class, 'image'], [Page::class, 'featured_image'], [Page::class, 'og_image'],
            [Post::class, 'featured_image'], [Post::class, 'og_image'],
            [Course::class, 'featured_image'], [Course::class, 'og_image'], [Block::class, 'image'],
        ];

        foreach ($checks as [$model, $column]) {
            foreach ($model::whereNotNull($column)->get() as $record) {
                if ($record->uploadMissing($record->{$column})) {
                    $this->line("  <fg=red>dead</> {$model}#{$record->id}.{$column} = {$record->{$column}}");
                    $dead++;
                }
            }
        }

        foreach (Setting::where('type', 'image')->whereNotNull('value')->get() as $setting) {
            if (filled($setting->value) && ! Storage::disk('public')->exists($setting->value)) {
                $this->line("  <fg=red>dead</> setting '{$setting->key}' = {$setting->value}");
                $dead++;
            }
        }

        if ($dead > 0) {
            $this->failures++;
            $this->line("  <fg=red>✗ {$dead} dead image reference(s)</>");
        } else {
            $this->line('  <fg=green>✓</> no dead image references');
        }

        /*
         * Inline <img> inside stored HTML bodies.
         *
         * Split by published state, because only the published count is a
         * launch blocker. Recon found 102 dead references, every one of them
         * inside a Joomla-trashed article — those import unpublished, so they
         * are expected here and are not a failure. A broken image in PUBLISHED
         * content is a different matter and fails the run.
         */
        $brokenPublished = [];
        $brokenUnpublished = 0;

        foreach ([Page::class, Course::class, Post::class, Block::class] as $model) {
            foreach ($model::all() as $record) {
                $html = (string) ($record->body ?? '').($record->content ?? '').($record->curriculum_html ?? '');

                preg_match_all('~<img[^>]+src="(/storage/[^"]+)"~i', $html, $matches);

                foreach (array_unique($matches[1] ?? []) as $src) {
                    $path = rawurldecode(ltrim(str_replace('/storage/', '', $src), '/'));

                    if (Storage::disk('public')->exists($path)) {
                        continue;
                    }

                    if ($record->published ?? false) {
                        $brokenPublished[] = "{$model}#{$record->id}: {$src}";
                    } else {
                        $brokenUnpublished++;
                    }
                }
            }
        }

        if ($brokenPublished !== []) {
            $this->failures++;
            $this->line('  <fg=red>✗ '.count($brokenPublished).' broken inline image(s) in PUBLISHED content:</>');

            foreach ($brokenPublished as $line) {
                $this->line("      {$line}");
            }
        } else {
            $this->line('  <fg=green>✓</> no broken inline images in published content');
        }

        if ($brokenUnpublished > 0) {
            $this->line("  <fg=gray>({$brokenUnpublished} broken inline image(s) in unpublished/legacy-trashed content — expected)</>");
        }
    }

    private function encoding(): void
    {
        $this->heading('4. Encoding spot-check (non-ASCII rows)');

        $found = 0;
        $mojibake = 0;

        foreach ([Page::class, Course::class, Post::class] as $model) {
            foreach ($model::all() as $record) {
                $text = $record->title.' '.strip_tags((string) $record->body);

                if (! preg_match('~[^\x00-\x7F]~u', $text)) {
                    continue;
                }

                $found++;

                if (preg_match('~(Ã[\x80-\xBF]|â€™|â€œ|Â[\xA0-\xBF])~u', $text)) {
                    $mojibake++;
                    $this->line("  <fg=red>mojibake</> {$model}#{$record->id}: ".mb_substr($record->title, 0, 50));
                } elseif ($found <= 3) {
                    $this->line('  <fg=gray>sample</> '.mb_substr(trim(preg_replace('~\s+~u', ' ', $text) ?? ''), 0, 80));
                }
            }
        }

        $this->line("  rows containing non-ASCII text: {$found}");

        if ($mojibake > 0) {
            $this->failures++;
            $this->line("  <fg=red>✗ {$mojibake} row(s) show mojibake</>");
        } else {
            $this->line('  <fg=green>✓</> no mojibake detected');
        }
    }

    private function redirects(): void
    {
        $this->heading('5. Redirect map');

        $total = Redirect::published()->count();
        $this->line("  published redirects: {$total}");

        $loops = Redirect::all()->filter(
            fn (Redirect $r) => Redirect::normalize($r->source) === Redirect::normalize($r->target)
        );

        if ($loops->isNotEmpty()) {
            $this->failures++;
            $this->line("  <fg=red>✗ {$loops->count()} redirect(s) point at themselves</>");
        } else {
            $this->line('  <fg=green>✓</> no self-referencing redirects');
        }

        // Every published menu item's legacy URL must have a mapping.
        $missing = MenuItem::published()->whereNotNull('legacy_path')->get()
            ->filter(function (MenuItem $item) {
                if ($item->resolvedUrl() === null) {
                    return false;   // dropdown parents have no destination by design
                }

                $source = Redirect::normalize('/index.php/'.trim((string) $item->legacy_path, '/'));

                return ! Redirect::where('source_hash', hash('sha256', $source))->exists();
            });

        if ($missing->isNotEmpty()) {
            $this->failures++;
            $this->line("  <fg=red>✗ {$missing->count()} menu item(s) have no redirect:</>");
            foreach ($missing as $item) {
                $this->line("      {$item->legacy_path}");
            }
        } else {
            $this->line('  <fg=green>✓</> every linkable menu item has a redirect');
        }
    }

    private function ai(): void
    {
        $this->heading('6. AI sample review');

        $normalized = Course::where('ai_normalized', true)->count();
        $this->line("  courses with AI-extracted fields: {$normalized}/".Course::count());

        foreach (Course::where('ai_normalized', true)->take(30)->get() as $course) {
            $this->line(sprintf(
                '    %-30s %-8s %-8s fee %-8s %s',
                mb_substr($course->title, 0, 30),
                $course->language ?? '—',
                implode(',', $course->cefr_levels ?? []) ?: '—',
                $course->formatFee('regular') ?? '—',
                $course->duration_months ? $course->duration_months.'mo' : '',
            ));
        }

        $meta = collect([Page::class, Course::class, Post::class])
            ->sum(fn ($m) => $m::where('ai_meta_generated', true)->count());

        $this->line("  records with AI-generated meta descriptions: {$meta}");
        $this->line('  <fg=yellow>These need a human to review them before launch.</>');
    }

    private function dynamic(): void
    {
        $this->heading('7. Everything-dynamic check');

        $rows = [
            'Settings' => Setting::count(),
            'Menus / items' => Menu::count().' / '.MenuItem::count(),
            'Homepage blocks' => Block::count(),
            'Slides' => Slide::count(),
            'Forms / fields' => Form::count().' / '.DB::table('form_fields')->count(),
            'Redirects' => Redirect::count(),
        ];

        foreach ($rows as $label => $count) {
            $this->line(sprintf('  %-20s %s', $label, $count));
        }

        $blank = Setting::whereNull('value')->orWhere('value', '')->pluck('key');

        if ($blank->isNotEmpty()) {
            $this->line("  <fg=yellow>~ {$blank->count()} setting(s) still need a value from the client:</>");
            $this->line('      '.$blank->implode(', '));
        }
    }

    private function emptyContent(): void
    {
        $this->heading('8. Published but empty (needs editor content)');

        $empty = collect();

        foreach (Page::published()->get() as $page) {
            if ($page->isEmpty()) {
                $empty->push(['Page', $page->title, '/'.$page->slug]);
            }
        }

        foreach (Course::published()->get() as $course) {
            if ($course->isEmpty()) {
                $empty->push(['Course', $course->title, '/courses/'.$course->slug]);
            }
        }

        if ($empty->isEmpty()) {
            $this->line('  <fg=green>✓</> nothing published is empty');

            return;
        }

        // Not a failure: these were empty in Joomla too. But they must be
        // visible rather than discovered on launch day.
        $this->line("  <fg=yellow>{$empty->count()} published record(s) were already empty in the legacy site:</>");

        // resolvedUrl() returns an absolute URL; compare paths, not strings.
        $menuPaths = MenuItem::published()->with('linkable')->get()
            ->map(fn (MenuItem $i) => $i->resolvedUrl())
            ->filter()
            ->map(fn (string $u) => parse_url($u, PHP_URL_PATH) ?: '/')
            ->all();

        foreach ($empty as [$type, $title, $url]) {
            $linked = in_array($url, $menuPaths, true);

            $this->line(sprintf('    %-7s %-34s %-34s %s',
                $type, mb_substr($title, 0, 34), $url,
                $linked ? '<fg=red>LINKED FROM THE MENU</>' : ''));
        }
    }

    private function crawl(string $base): void
    {
        $this->heading('9. Internal link crawl');

        $urls = collect(['/'])
            ->merge(Page::published()->pluck('slug')->map(fn ($s) => "/{$s}"))
            ->merge(['/courses'])
            ->merge(Course::published()->pluck('slug')->map(fn ($s) => "/courses/{$s}"))
            ->merge(Post::published()->exists() ? ['/news'] : [])
            ->merge(Post::published()->pluck('slug')->map(fn ($s) => "/news/{$s}"))
            ->merge(['/sitemap.xml'])
            ->unique();

        $bad = 0;

        foreach ($urls as $path) {
            try {
                $status = Http::timeout(15)->withoutRedirecting()->get($base.$path)->status();
            } catch (\Throwable $e) {
                $this->line("  <fg=red>ERR </> {$path} — {$e->getMessage()}");
                $bad++;

                continue;
            }

            if ($status >= 400) {
                $this->line("  <fg=red>{$status}</> {$path}");
                $bad++;
            }
        }

        $this->line(sprintf('  crawled %d page(s)', $urls->count()));

        if ($bad > 0) {
            $this->failures++;
            $this->line("  <fg=red>✗ {$bad} internal error(s)</>");
        } else {
            $this->line('  <fg=green>✓</> no internal 4xx/5xx');
        }

        // Every redirect target must itself resolve.
        $badTargets = 0;

        foreach (Redirect::published()->get() as $redirect) {
            try {
                $status = Http::timeout(15)->withoutRedirecting()->get($base.$redirect->target)->status();
            } catch (\Throwable) {
                $badTargets++;

                continue;
            }

            if ($status >= 400) {
                $this->line("  <fg=red>{$status}</> redirect target {$redirect->target} (from {$redirect->source})");
                $badTargets++;
            }
        }

        if ($badTargets > 0) {
            $this->failures++;
            $this->line("  <fg=red>✗ {$badTargets} redirect target(s) do not resolve</>");
        } else {
            $this->line('  <fg=green>✓</> every redirect target resolves');
        }
    }
}
