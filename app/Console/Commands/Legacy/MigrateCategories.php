<?php

namespace App\Console\Commands\Legacy;

use App\Models\Category;
use App\Support\Text;

class MigrateCategories extends LegacyCommand
{
    protected $signature = 'legacy:categories';

    protected $description = 'Import com_content categories';

    public function handle(): int
    {
        $cfg = $this->cfg('categories');

        $source = $this->legacy($cfg);
        $sourceCount = (clone $source)->count();

        foreach ($source->get() as $row) {
            $data = $this->mapRow($row, $cfg);

            $data['slug'] = Text::slug($data['slug'] ?: $data['name'], 'category-'.$row->id);

            /*
             * Joomla ships one "Uncategorised" alias per extension, so the
             * com_content categories collide with each other on slug. Make it
             * unique against anything that is not this same legacy row.
             */
            $existingId = Category::query()
                ->fromLegacy($cfg['source'], $row->id)->value('id');

            $data['slug'] = Text::uniqueSlug($data['slug'], 'categories', $existingId);

            $this->upsert(Category::class, $cfg['source'], $row->id, $data);
        }

        $this->remapParents($cfg);

        return $this->report('Categories', $sourceCount, Category::count());
    }

    /**
     * Second pass — parents were imported as LEGACY ids, so resolve them to
     * local ids now that every row exists. Joomla's ROOT (id 1) is a nested-set
     * sentinel, not a real category, so its children become top level.
     */
    private function remapParents(array $cfg): void
    {
        $byLegacy = Category::query()
            ->where('legacy_source', $cfg['source'])
            ->pluck('id', 'legacy_id');

        foreach (Category::whereNotNull('legacy_parent_id')->get() as $category) {
            $legacyParent = (int) $category->legacy_parent_id;

            $category->update([
                'parent_id' => $legacyParent === (int) $cfg['root_legacy_id']
                    ? null
                    : ($byLegacy[$legacyParent] ?? null),
            ]);
        }
    }
}
