<?php

namespace App\Console\Commands\Ai;

use App\Models\Slide;

/**
 * Generate alt text for slider images that have none.
 *
 * The legacy carousel stored an empty `bg.alt` on both slides, so this is an
 * accessibility fix rather than a migration. Descriptions are derived from the
 * filename and page context only — we never claim to know what is in a photo
 * we have not been shown, so the prompt asks for a functional description and
 * `ai_alt_generated` flags every one of them for human review.
 */
class GenerateAlt extends AiCommand
{
    protected $signature = 'ai:generate-alt
        {--dry-run : Show what would change, write nothing}
        {--force   : Regenerate even where alt text already exists}';

    protected $description = 'Generate missing image alt text for slides';

    public function handle(): int
    {
        if (! $this->guardConfigured()) {
            return self::SUCCESS;
        }

        $slides = Slide::query()
            ->when(! $this->option('force'), fn ($q) => $q->where(function ($q) {
                $q->whereNull('image_alt')->orWhere('image_alt', '');
            }))
            ->get();

        if ($slides->isEmpty()) {
            $this->line('  All slides already have alt text.');

            return self::SUCCESS;
        }

        $inputs = $slides->map(fn (Slide $s) => [
            'id' => (string) $s->id,
            'filename' => basename((string) $s->image),
            'title' => (string) $s->title,
            'caption' => (string) $s->caption,
        ])->values()->all();

        $result = $this->askGemini(
            'image_alt',
            'slides|'.md5(json_encode($inputs)),
            $this->prompt($inputs),
            $this->schema(),
            $this->system(),
        );

        if ($result === null) {
            return $this->reportAi('Image alt text', 0);
        }

        $byId = collect($result['results'] ?? [])->keyBy('id');
        $touched = 0;

        foreach ($slides as $slide) {
            $alt = trim((string) ($byId[(string) $slide->id]['alt'] ?? ''));

            if ($alt === '' || mb_strlen($alt) > 160) {
                continue;
            }

            // Capture BEFORE updating — update() refreshes the original
            // attributes, so reading it afterwards echoes the new value back.
            $before = $slide->image_alt;

            if (! $this->option('dry-run')) {
                $slide->update(['image_alt' => $alt, 'ai_alt_generated' => true]);
            }

            $touched++;
            $this->sample("slide #{$slide->id}", $before, $alt);
        }

        return $this->reportAi('Image alt text', $touched);
    }

    private function system(): string
    {
        return <<<'TXT'
        You write HTML alt text for images on the website of the Daffodil Institute
        of Languages & Culture, a language institute in Dhaka, Bangladesh.

        You have NOT seen the images — you are given only the filename and any
        caption. Therefore:
        - Write a short, functional description based only on that context.
        - Never describe specific people, counts, expressions, clothing or text that
          you cannot verify from the given information.
        - Prefer a general, honest description ("Students in a DILC classroom")
          over a confident but unverifiable one.
        - Maximum 120 characters. Do not begin with "Image of" or "Picture of".
        - Return exactly one object per input, using the same `id`.
        TXT;
    }

    /** @param array<int, array<string, string>> $inputs */
    private function prompt(array $inputs): string
    {
        $json = json_encode($inputs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        return "Write alt text for each of these homepage slider images.\n\nInputs:\n{$json}";
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
                            'id' => ['type' => 'string'],
                            'alt' => ['type' => 'string'],
                        ],
                        'required' => ['id', 'alt'],
                    ],
                ],
            ],
            'required' => ['results'],
        ];
    }
}
