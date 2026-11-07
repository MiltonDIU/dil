<?php

namespace App\Console\Commands\Legacy;

use App\Models\Category;
use App\Models\Post;
use App\Support\Text;

class MigratePosts extends LegacyCommand
{
    protected $signature = 'legacy:posts';

    protected $description = 'Import news posts from com_content';

    public function handle(): int
    {
        $cfg = $this->cfg('posts');
        $categoriesSource = $this->cfg('categories')['source'];

        $categoryByLegacy = Category::query()
            ->where('legacy_source', $categoriesSource)
            ->pluck('id', 'legacy_id');

        $source = $this->legacy($cfg);
        $sourceCount = (clone $source)->count();

        foreach ($source->get() as $row) {
            $data = $this->mapRow($row, $cfg);

            $data['body_raw'] = $row->introtext.$row->fulltext;
            $data['category_id'] = $categoryByLegacy[(int) $row->catid] ?? null;

            /*
             * Joomla's unicodeslugs was off, so Bengali titles produced
             * timestamp aliases like "2026-07-14-06-06-14". Text::slug()
             * transliterates instead of carrying that forward.
             */
            $slug = $data['slug'];

            if ($slug === '' || preg_match('~^\d{4}(-\d{2}){5}$~', $slug)) {
                $slug = Text::slug($data['title'], 'news-'.$row->id);
            }

            $existingId = Post::query()->fromLegacy($cfg['source'], $row->id)->value('id');
            $data['slug'] = Text::uniqueSlug($slug, 'posts', $existingId);

            $data['excerpt'] = Text::excerpt($data['body'], 200) ?: null;

            $this->upsert(Post::class, $cfg['source'], $row->id, $data);
        }

        return $this->report('Posts (news)', $sourceCount, Post::count());
    }
}
