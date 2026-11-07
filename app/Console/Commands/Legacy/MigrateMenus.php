<?php

namespace App\Console\Commands\Legacy;

use App\Models\Course;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Post;
use App\Support\Text;
use Illuminate\Support\Facades\DB;

class MigrateMenus extends LegacyCommand
{
    protected $signature = 'legacy:menus';

    protected $description = 'Import menus and menu items, resolving Joomla component links to real routes';

    /** @var array<int, string> legacy article id => "Model|id" */
    private array $articleIndex = [];

    /** @var array<int, string> unresolved links, for the report */
    private array $unresolved = [];

    public function handle(): int
    {
        $cfg = $this->cfg('menus');

        $this->buildArticleIndex();
        $this->importMenus($cfg);

        $source = $this->legacy($cfg);
        $sourceCount = (clone $source)->count();

        $menuByLegacyType = Menu::pluck('id', 'slug');

        foreach ($source->get() as $row) {
            $data = $this->mapRow($row, $cfg);

            $data['menu_id'] = $menuByLegacyType[$row->menutype] ?? $menuByLegacyType->first();
            $data['ordering'] = (int) $row->lft;
            $data['is_home'] = (bool) $row->home;

            $data += $this->resolveLink($row, $cfg);

            $this->upsert(MenuItem::class, $cfg['source'], $row->id, $data);
        }

        $this->remapParents($cfg);
        $this->reportLinks();

        return $this->report('Menu items', $sourceCount, MenuItem::count());
    }

    /**
     * One legacy table (a0cbr_content) became three native ones, so a legacy
     * article id has to be resolved against pages, courses and posts.
     */
    private function buildArticleIndex(): void
    {
        foreach ([Page::class, Course::class, Post::class] as $model) {
            foreach ($model::query()->whereNotNull('legacy_id')->get(['id', 'legacy_id']) as $record) {
                $this->articleIndex[(int) $record->legacy_id] ??= $model.'|'.$record->id;
            }
        }
    }

    private function importMenus(array $cfg): void
    {
        $types = DB::connection(config('migration.connection'))
            ->table($cfg['menu_table'])->where('client_id', 0)->get();

        foreach ($types as $type) {
            $cfg['menu_model']::updateOrCreate(
                ['legacy_source' => $cfg['menu_source'], 'legacy_id' => $type->id],
                [
                    'name' => Text::fixMojibake($type->title),
                    'slug' => Text::slug($type->menutype, 'menu-'.$type->id),
                    'description' => $type->description ?: null,
                ],
            );
        }
    }

    /**
     * Turn a Joomla component link into a real destination.
     *
     * Anything unresolvable is logged rather than silently dropped.
     *
     * @return array<string, mixed>
     */
    private function resolveLink(object $row, array $cfg): array
    {
        $none = [
            'type' => 'none', 'linkable_type' => null, 'linkable_id' => null,
            'route_name' => null, 'url' => null,
        ];

        // The homepage is SP Page Builder page 1, rendered by our home route.
        if ($row->home) {
            return [...$none, 'type' => 'route', 'route_name' => 'home'];
        }

        $link = trim((string) $row->link);

        // Dropdown parents: Joomla stored these as link = '#'.
        if ($link === '' || $link === $cfg['placeholder_link']) {
            return $none;
        }

        if ($row->type === 'url') {
            return [...$none, 'type' => 'url', 'url' => $link];
        }

        foreach ($cfg['link_patterns'] as $pattern => $kind) {
            if (! preg_match($pattern, $link, $m)) {
                continue;
            }

            $id = (int) ($m[1] ?? 0);

            if ($kind === 'article') {
                if (! isset($this->articleIndex[$id])) {
                    $this->unresolved[$row->id] = "{$row->title}: article {$id} not imported";

                    return $none;
                }

                [$model, $localId] = explode('|', $this->articleIndex[$id]);

                return [
                    ...$none,
                    'type' => match ($model) {
                        Course::class => 'course',
                        Post::class => 'post',
                        default => 'page',
                    },
                    'linkable_type' => $model,
                    'linkable_id' => (int) $localId,
                ];
            }

            if ($kind === 'category') {
                $route = $cfg['category_routes'][$id] ?? null;

                if ($route === null) {
                    $this->unresolved[$row->id] = "{$row->title}: no route for category {$id}";

                    return $none;
                }

                return [...$none, 'type' => 'route', 'route_name' => $route];
            }

            if ($kind === 'sppagebuilder') {
                return $id === (int) $cfg['home_sppagebuilder_id']
                    ? [...$none, 'type' => 'route', 'route_name' => 'home']
                    : $none;
            }

            if ($kind === 'login') {
                return [...$none, 'type' => 'route', 'route_name' => 'filament.admin.auth.login'];
            }
        }

        $this->unresolved[$row->id] = "{$row->title}: unrecognised link {$link}";

        return $none;
    }

    /**
     * Second pass — parents were stored as LEGACY ids. Joomla's menu root
     * (id 1) is a nested-set sentinel, so its children become top level.
     */
    private function remapParents(array $cfg): void
    {
        $byLegacy = MenuItem::query()
            ->where('legacy_source', $cfg['source'])
            ->pluck('id', 'legacy_id');

        foreach (MenuItem::whereNotNull('legacy_parent_id')->get() as $item) {
            $legacyParent = (int) $item->legacy_parent_id;

            $item->update([
                'parent_id' => $legacyParent === (int) $cfg['root_legacy_id']
                    ? null
                    : ($byLegacy[$legacyParent] ?? null),
            ]);
        }
    }

    private function reportLinks(): void
    {
        $byType = MenuItem::query()
            ->selectRaw('type, count(*) as n')->groupBy('type')->pluck('n', 'type');

        $this->newLine();
        $this->line('  <fg=cyan>Link resolution</>');

        foreach ($byType as $type => $count) {
            $note = $type === 'none' ? '  (dropdown parents — rendered non-clickable)' : '';
            $this->line(sprintf('    %-8s %2d%s', $type, $count, $note));
        }

        if ($this->unresolved !== []) {
            $this->newLine();
            $this->warn('  Unresolved links (imported as non-clickable, not dropped):');
            foreach ($this->unresolved as $legacyId => $reason) {
                $this->line("    · [{$legacyId}] {$reason}");
            }
        }

        $emptyTargets = MenuItem::published()->with('linkable')->get()
            ->filter(fn (MenuItem $i) => $i->pointsAtEmptyContent());

        if ($emptyTargets->isNotEmpty()) {
            $this->newLine();
            $this->warn("  {$emptyTargets->count()} published menu item(s) point at empty content:");
            foreach ($emptyTargets as $item) {
                $this->line("    · {$item->title}");
            }
        }
    }
}
