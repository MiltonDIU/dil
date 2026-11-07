<?php

namespace App\Console\Commands\Legacy;

use App\Models\Slide;
use App\Support\SppbLayout;
use App\Support\Text;
use Illuminate\Support\Facades\DB;

class MigrateSlides extends LegacyCommand
{
    protected $signature = 'legacy:slides';

    protected $description = 'Import carousel slides from the SP Page Builder homepage';

    public function handle(): int
    {
        $cfg = $this->cfg('slides');

        $row = DB::connection(config('migration.connection'))
            ->table('sppagebuilder')->where('id', $cfg['page_id'])->first();

        if (! $row) {
            $this->error('SP Page Builder page not found.');

            return self::FAILURE;
        }

        $carousel = collect(SppbLayout::fromJson($row->content)->addons())
            ->firstWhere('name', $cfg['addon']);

        if (! $carousel) {
            $this->warn('  No carousel addon on the homepage — nothing to import.');

            return self::SUCCESS;
        }

        $items = SppbLayout::get($carousel['settings'], $cfg['items_key'], []) ?: [];
        $ordering = 0;

        foreach ($items as $index => $item) {
            $image = SppbLayout::get($item, 'bg.src');

            if (blank($image)) {
                $this->skip('slide has no background image');

                continue;
            }

            $this->upsert(Slide::class, $cfg['source'], $index, [
                'group' => $cfg['group'],
                'image' => $this->mediaPath((string) $image),
                'image_alt' => SppbLayout::get($item, 'bg.alt'),
                'width' => (int) SppbLayout::get($item, 'bg.width') ?: null,
                'height' => (int) SppbLayout::get($item, 'bg.height') ?: null,
                'title' => Text::fixMojibake((string) SppbLayout::get($item, 'title', '')) ?: null,
                'caption' => Text::excerpt(SppbLayout::get($item, 'content', ''), 300) ?: null,
                'link_text' => SppbLayout::get($item, 'button_text'),
                'link_url' => SppbLayout::link($item, 'button_url'),
                'ordering' => $ordering += 10,
                'published' => true,
            ]);
        }

        return $this->report('Slides', count($items), Slide::count());
    }

    /**
     * Legacy paths are site-relative ("images/home/dil.png"). Store them
     * DISK-RELATIVE under the media root; `legacy:media` copies the files and
     * NULLs anything that turns out to be missing.
     */
    private function mediaPath(string $legacyPath): string
    {
        return trim(config('migration.media.root'), '/').'/'.ltrim($legacyPath, '/');
    }
}
