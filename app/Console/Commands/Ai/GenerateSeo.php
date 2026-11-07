<?php

namespace App\Console\Commands\Ai;

use App\Models\Course;
use App\Models\Page;
use App\Models\Post;
use App\Support\Text;

/**
 * Fill missing meta descriptions.
 *
 * All 29 legacy articles had an empty `metadesc`, so there is nothing to
 * migrate — this generates from content that already exists rather than
 * inventing claims. Anything with a hand-written meta description is left
 * alone, and `ai_meta_generated` marks what a human should still review.
 */
class GenerateSeo extends AiCommand
{
    protected $signature = 'ai:generate-seo
        {--dry-run  : Show what would change, write nothing}
        {--force    : Regenerate even where a meta description already exists}
        {--model=*  : Limit to pages, courses or posts}';

    protected $description = 'Generate missing meta descriptions for pages, courses and posts';

    public function handle(): int
    {
        if (! $this->guardConfigured()) {
            return self::SUCCESS;
        }

        $models = (array) $this->option('model') ?: ['pages', 'courses', 'posts'];
        $touched = 0;

        $sets = array_intersect_key([
            'pages' => Page::class,
            'courses' => Course::class,
            'posts' => Post::class,
        ], array_flip($models));

        foreach ($sets as $label => $model) {
            $records = $model::query()
                ->when(! $this->option('force'), fn ($q) => $q->where(function ($q) {
                    $q->whereNull('meta_description')->orWhere('meta_description', '');
                }))
                ->get()
                ->reject(fn ($r) => blank(trim(strip_tags((string) ($r->body ?: $r->body_raw)))));

            if ($records->isEmpty()) {
                continue;
            }

            $this->line("  <fg=gray>{$label}: {$records->count()} record(s) need a meta description</>");

            /*
             * Batched: the whole set goes in one call, keyed by slug, rather
             * than one call per row.
             */
            foreach ($records->chunk((int) config('gemini.chunk_size')) as $chunk) {
                $inputs = $chunk->map(fn ($r) => [
                    'slug' => $r->slug,
                    'title' => $r->title,
                    'text' => Text::excerpt($r->body ?: $r->body_raw, 900),
                ])->values()->all();

                $result = $this->askGemini(
                    'seo_meta',
                    $label.'|'.md5(json_encode($inputs)),
                    $this->prompt($inputs),
                    $this->schema(),
                    $this->system(),
                );

                if ($result === null) {
                    continue;
                }

                $byslug = collect($result['results'] ?? [])->keyBy('slug');

                foreach ($chunk as $record) {
                    $meta = trim((string) ($byslug[$record->slug]['meta_description'] ?? ''));

                    if ($meta === '' || mb_strlen($meta) > 320) {
                        continue;
                    }

                    // Capture BEFORE updating — update() refreshes the model's
                    // original attributes, so reading it afterwards just
                    // echoes the new value back.
                    $before = $record->meta_description;

                    if (! $this->option('dry-run')) {
                        $record->update([
                            'meta_description' => $meta,
                            'ai_meta_generated' => true,
                        ]);
                    }

                    $touched++;
                    $this->sample("{$label}: {$record->title}", $before, $meta);
                }
            }
        }

        return $this->reportAi('SEO meta descriptions', $touched);
    }

    private function system(): string
    {
        return <<<'TXT'
        You write meta descriptions for the website of the Daffodil Institute of
        Languages & Culture (DILC), a language institute at Daffodil International
        University in Dhaka, Bangladesh.

        Rules:
        - 140-160 characters. Plain sentence case. No quotes, no emoji, no ALL CAPS.
        - Describe ONLY what the supplied text says. Never invent fees, dates,
          accreditations, rankings or outcomes.
        - Write for a prospective student searching Google.
        - Return exactly one object per input, using the same `slug` you were given.
        TXT;
    }

    /** @param array<int, array<string, string>> $inputs */
    private function prompt(array $inputs): string
    {
        $json = json_encode($inputs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        return "Write one meta description for each of these pages.\n\nInputs:\n{$json}";
    }

    /** @return array<string, mixed> */
    private function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'results' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'slug' => ['type' => 'string'],
                            'meta_description' => ['type' => 'string'],
                        ],
                        'required' => ['slug', 'meta_description'],
                    ],
                ],
            ],
            'required' => ['results'],
        ];
    }
}
