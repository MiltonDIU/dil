<?php

namespace App\Console\Commands\Legacy;

use App\Models\Page;
use App\Support\Text;

class MigratePages extends LegacyCommand
{
    protected $signature = 'legacy:pages';

    protected $description = 'Import static content pages from com_content';

    public function handle(): int
    {
        $cfg = $this->cfg('pages');
        $courseIds = $this->cfg('courses')['legacy_ids'];

        $source = $this->legacy($cfg)->whereNotIn($cfg['key'], $courseIds);
        $sourceCount = (clone $source)->count();

        foreach ($source->get() as $row) {
            $data = $this->mapRow($row, $cfg);

            // metakey is a keyword list in Joomla, not a title — drop it.
            $data['meta_title'] = null;

            $data['body_raw'] = $row->introtext.$row->fulltext;
            $data['slug'] = $cfg['slug_overrides'][$row->id]
                ?? Text::slug($data['slug'] ?: $data['title'], 'page-'.$row->id);

            $existingId = Page::query()->fromLegacy($cfg['source'], $row->id)->value('id');
            $data['slug'] = Text::uniqueSlug($data['slug'], 'pages', $existingId);

            $data['excerpt'] = Text::excerpt($data['body'], 200) ?: null;

            $this->upsert(Page::class, $cfg['source'], $row->id, $data);
        }

        $this->reportEmptyBodies();

        return $this->report('Pages', $sourceCount, Page::count());
    }

    /**
     * Five legacy articles were published with a completely empty body, three
     * of them linked from the live menu. They migrate as-is (we do not invent
     * content) but they are surfaced here and in the Phase 8 report so nobody
     * discovers them on launch day.
     */
    private function reportEmptyBodies(): void
    {
        $empty = Page::query()->where('published', true)->get()
            ->filter(fn (Page $p) => $p->isEmpty());

        if ($empty->isEmpty()) {
            return;
        }

        $this->newLine();
        $this->warn("  {$empty->count()} published page(s) have an empty body — needs editor content:");

        foreach ($empty as $page) {
            $this->line("    · {$page->title}  (/{$page->slug})");
        }
    }
}
