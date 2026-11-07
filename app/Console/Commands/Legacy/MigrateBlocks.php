<?php

namespace App\Console\Commands\Legacy;

use App\Models\Block;
use App\Models\Course;
use App\Models\Page;
use App\Support\SppbLayout;
use App\Support\Text;
use Illuminate\Support\Facades\DB;

/**
 * Decompose the SP Page Builder homepage into typed, editable blocks.
 *
 * This is deliberately deterministic — the legacy homepage uses only seven
 * addon types, which a mapping handles perfectly well. AI is not involved.
 * Each block keeps the SPPB JSON it came from in `raw_layout`, forever.
 */
class MigrateBlocks extends LegacyCommand
{
    protected $signature = 'legacy:blocks';

    protected $description = 'Convert the SP Page Builder homepage into editable blocks';

    public function handle(): int
    {
        $cfg = $this->cfg('blocks');

        $row = DB::connection(config('migration.connection'))
            ->table($cfg['table'])->where('id', $cfg['page_id'])->first();

        if (! $row) {
            $this->error("SP Page Builder page {$cfg['page_id']} not found.");

            return self::FAILURE;
        }

        $layout = SppbLayout::fromJson($row->content);
        $rows = $layout->byRow();

        $ordering = 0;
        $legacyIndex = 0;

        foreach ($rows as $rowIndex => $rowData) {
            $columns = $rowData['columns'];
            $blocks = $this->mapRowToBlocks($rowIndex, $rowData['layout'], $columns, $cfg);

            foreach ($blocks as $block) {
                $block['group'] = $cfg['group'];
                $block['ordering'] = $ordering += 10;

                $this->upsert(
                    Block::class,
                    $cfg['source'],
                    // stable synthetic key: page * 1000 + sequence
                    ($cfg['page_id'] * 1000) + $legacyIndex++,
                    $block,
                );
            }
        }

        $this->reportTypes();

        return $this->report('Homepage blocks', count($rows), Block::count());
    }

    /**
     * @param  array<int, array<int, array>>  $columns
     * @return array<int, array<string, mixed>>
     */
    private function mapRowToBlocks(int $rowIndex, string $layout, array $columns, array $cfg): array
    {
        // A multi-column row whose every column looks like icon + text + button
        // is a feature-card grid, not N unrelated blocks.
        if (in_array($layout, $cfg['feature_card_layouts'], true) && $this->looksLikeCards($columns)) {
            return [$this->featureCards($columns, $layout)];
        }

        $blocks = [];

        foreach ($columns as $addons) {
            $pending = null;

            foreach ($addons as $addon) {
                $type = $cfg['addon_map'][$addon['name']] ?? null;

                // A button attaches to the block immediately before it.
                if ($addon['name'] === 'button') {
                    if ($pending !== null) {
                        $pending['link_text'] = SppbLayout::get($addon['settings'], 'text');
                        $pending['link_url'] = $this->resolveUrl(
                            SppbLayout::link($addon['settings'], 'url')
                        );
                    }

                    continue;
                }

                if ($pending !== null) {
                    $blocks[] = $pending;
                    $pending = null;
                }

                if ($type === null) {
                    $this->skip("addon '{$addon['name']}' carries no content");

                    continue;
                }

                $pending = $this->mapAddon($type, $addon, $cfg);

                if ($pending === null) {
                    $this->skip("addon '{$addon['name']}' was empty");
                }
            }

            if ($pending !== null) {
                $blocks[] = $pending;
            }
        }

        return $blocks;
    }

    /**
     * A feature-card grid, as opposed to an ordinary multi-column row.
     *
     * The distinguishing signal is an `icon` addon in EVERY column. The
     * homepage has two 3+ column rows and only one is cards: the other holds
     * Downloads / Our Partners / Where we are, which must stay as a link_list,
     * link_list and map. Matching on text_block alone collapses both.
     *
     * @param  array<int, array<int, array>>  $columns
     */
    private function looksLikeCards(array $columns): bool
    {
        if (count($columns) < 3) {
            return false;
        }

        foreach ($columns as $addons) {
            if (! in_array('icon', array_column($addons, 'name'), true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Collapse a 3- or 4-column row of icon/heading/body/button into one
     * `feature_cards` block, so an editor manages four cards in one place
     * instead of twenty loose blocks.
     *
     * @param  array<int, array<int, array>>  $columns
     * @return array<string, mixed>
     */
    private function featureCards(array $columns, string $layout): array
    {
        $cards = [];

        foreach ($columns as $addons) {
            $card = ['icon' => null, 'title' => null, 'body' => null,
                'link_text' => null, 'link_url' => null];

            foreach ($addons as $addon) {
                $settings = $addon['settings'];

                match ($addon['name']) {
                    'icon' => $card['icon'] = SppbLayout::get($settings, 'name'),
                    'button' => [
                        $card['link_text'] = SppbLayout::get($settings, 'text'),
                        $card['link_url'] = $this->resolveUrl(SppbLayout::link($settings, 'url')),
                    ],
                    'text_block' => $this->fillCardText($card, $settings),
                    default => null,
                };
            }

            if (filled($card['title']) || filled($card['body'])) {
                $cards[] = $card;
            }
        }

        return [
            'type' => 'feature_cards',
            'title' => null,
            'data' => ['cards' => $cards, 'columns' => count($cards)],
            'raw_layout' => json_encode($columns, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
    }

    /**
     * SPPB emitted each card as two text_blocks: one carrying only a `title`
     * (with an empty <p> body) and one carrying only the paragraph.
     *
     * @param  array<string, mixed>  $card
     */
    private function fillCardText(array &$card, array $settings): void
    {
        $title = SppbLayout::get($settings, 'title');
        $body = $this->cleanBody(SppbLayout::get($settings, 'text', ''));

        if (filled($title) && $card['title'] === null) {
            $card['title'] = Text::fixMojibake((string) $title);
        }

        if (filled($body)) {
            $card['body'] = $card['body'] ? $card['body'].$body : $body;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function mapAddon(string $type, array $addon, array $cfg): ?array
    {
        $settings = $addon['settings'];
        $raw = json_encode($addon['raw'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($type === 'carousel') {
            // Slides live in their own table; this block just marks the slot.
            return [
                'type' => 'carousel',
                'data' => ['group' => $cfg['group']],
                'full_width' => true,
                'raw_layout' => $raw,
            ];
        }

        if ($type === 'video') {
            $url = SppbLayout::get($settings, 'url');

            return blank($url) ? null : [
                'type' => 'video',
                'title' => SppbLayout::get($settings, 'title'),
                'data' => ['url' => $url, 'embed' => $this->youtubeEmbed((string) $url)],
                'raw_layout' => $raw,
            ];
        }

        // text_block — which may actually be a link list or a map embed.
        $body = $this->cleanBody(SppbLayout::get($settings, 'text', ''));
        $title = SppbLayout::get($settings, 'title');

        if (blank($body) && blank($title)) {
            return null;
        }

        foreach ($cfg['detect'] as $detected => $pattern) {
            if (preg_match($pattern, trim((string) SppbLayout::get($settings, 'text', '')))) {
                return $detected === 'map'
                    ? ['type' => 'map', 'title' => $title,
                        'data' => ['embed' => (string) SppbLayout::get($settings, 'text')],
                        'raw_layout' => $raw]
                    : ['type' => 'link_list', 'title' => $title,
                        'data' => ['items' => $this->parseList((string) SppbLayout::get($settings, 'text'))],
                        'raw_layout' => $raw];
            }
        }

        return [
            'type' => 'rich_text',
            'title' => $title ? Text::fixMojibake((string) $title) : null,
            'content' => $body,
            'raw_layout' => $raw,
        ];
    }

    /**
     * The homepage "Downloads" and "Our Partners" lists were plain <li> text
     * with no hrefs. We keep the labels and leave the URL null so an editor
     * can wire them up — rather than inventing links.
     *
     * @return array<int, array{label:string, url:string|null}>
     */
    private function parseList(string $html): array
    {
        preg_match_all('~<li[^>]*>(.*?)</li>~is', $html, $matches);

        $items = [];

        foreach ($matches[1] ?? [] as $item) {
            $url = preg_match('~href=["\']([^"\']+)["\']~i', $item, $m) ? $m[1] : null;
            $label = trim(html_entity_decode(strip_tags($item), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            if ($label !== '') {
                $items[] = ['label' => Text::fixMojibake($label), 'url' => $this->resolveUrl($url)];
            }
        }

        return $items;
    }

    private function cleanBody(mixed $html): string
    {
        return trim($this->prepareHtml(Text::fixMojibake((string) $html)));
    }

    /**
     * Rewrite a legacy component URL to the real route of the record it points
     * at, so homepage buttons keep working after the cutover.
     */
    private function resolveUrl(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        if (! preg_match('~option=com_content&view=article&id=(\d+)~', $url, $m)) {
            return $url;
        }

        $legacyId = (int) $m[1];
        $source = $this->cfg('pages')['source'];

        if ($page = Page::query()->fromLegacy($source, $legacyId)->first()) {
            return '/'.$page->slug;
        }

        if ($course = Course::query()->fromLegacy($source, $legacyId)->first()) {
            return '/courses/'.$course->slug;
        }

        $this->skip("button link to article {$legacyId} could not be resolved");

        return null;
    }

    private function youtubeEmbed(string $url): ?string
    {
        if (preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/|youtube\.com/embed/)([A-Za-z0-9_-]{11})~', $url, $m)) {
            return 'https://www.youtube-nocookie.com/embed/'.$m[1];
        }

        return null;
    }

    private function reportTypes(): void
    {
        $this->newLine();
        $this->line('  <fg=cyan>Blocks by type</>');

        foreach (Block::query()->selectRaw('type, count(*) n')->groupBy('type')->pluck('n', 'type') as $type => $count) {
            $this->line(sprintf('    %-16s %d', $type, $count));
        }
    }
}
