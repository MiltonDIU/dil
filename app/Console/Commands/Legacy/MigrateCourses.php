<?php

namespace App\Console\Commands\Legacy;

use App\Models\Course;
use App\Support\Text;

class MigrateCourses extends LegacyCommand
{
    protected $signature = 'legacy:courses';

    protected $description = 'Import language courses from com_content articles';

    public function handle(): int
    {
        $cfg = $this->cfg('courses');

        $source = $this->legacy($cfg);
        $sourceCount = (clone $source)->count();

        $ordering = 0;

        foreach ($source->get() as $row) {
            $data = $this->mapRow($row, $cfg);

            $data['body_raw'] = $row->introtext.$row->fulltext;   // never overwritten
            $data['slug'] = Text::slug($data['slug'] ?: $data['title'], 'course-'.$row->id);

            $existingId = Course::query()->fromLegacy($cfg['source'], $row->id)->value('id');
            $data['slug'] = Text::uniqueSlug($data['slug'], 'courses', $existingId);

            $data['language_raw'] = $data['title'];
            $data['language'] = $this->guessLanguage($data['title'], $cfg['language_hints']);
            $data['ordering'] = $ordering += 10;
            $data['summary'] = Text::excerpt($data['body'], 220) ?: null;

            // Conservative regex pass: only patterns that appear verbatim in
            // every course article. Everything ambiguous (fee tiers, CEFR
            // levels) is left null for `ai:extract-courses` to fill in.
            $data += $this->extractStructured($data['body']);

            $this->upsert(Course::class, $cfg['source'], $row->id, $data);
        }

        $this->reportCoverage();

        return $this->report('Courses', $sourceCount, Course::count());
    }

    private function guessLanguage(string $title, array $hints): ?string
    {
        $haystack = mb_strtolower($title);

        foreach ($hints as $needle => $language) {
            if (str_contains($haystack, $needle)) {
                return $language;
            }
        }

        return null;
    }

    /**
     * Deterministic extraction of the fields whose wording is stable across
     * every course article. Anything not matched stays null.
     *
     * @return array<string, int|null>
     */
    private function extractStructured(string $html): array
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('~\s+~u', ' ', $text) ?? $text;

        return array_filter([
            'duration_months' => $this->matchInt($text, '~Course\s+Duration\s*:\s*(\d+)\s*Month~i'),
            'classes_per_week' => $this->matchInt($text, '~Per\s+Week\s*:\s*(\d+)\s*Class~i'),
            'total_classes' => $this->matchInt($text, '~Total\s*[-–:]?\s*(\d+)\s*Class~i'),
            'class_duration_minutes' => $this->matchInt($text, '~Class\s+Duration\s*:\s*(\d+)\s*Minute~i'),
        ], fn ($v) => $v !== null);
    }

    private function matchInt(string $text, string $pattern): ?int
    {
        return preg_match($pattern, $text, $m) ? (int) $m[1] : null;
    }

    /**
     * Show what the deterministic pass could and could not fill, so the value
     * of the AI pass is measurable rather than assumed.
     */
    private function reportCoverage(): void
    {
        $total = Course::count();

        if ($total === 0) {
            return;
        }

        $this->newLine();
        $this->line('  <fg=cyan>Deterministic field coverage</> (rest is left for ai:extract-courses)');

        foreach ([
            'duration_months', 'classes_per_week', 'total_classes',
            'class_duration_minutes', 'fee_regular', 'cefr_levels',
        ] as $column) {
            $filled = Course::whereNotNull($column)->count();
            $bar = str_repeat('█', (int) round($filled / $total * 20));
            $this->line(sprintf('    %-24s %2d/%-2d %s', $column, $filled, $total, $bar));
        }

        $empty = Course::get()->filter(fn (Course $c) => $c->isEmpty());

        if ($empty->isNotEmpty()) {
            $this->newLine();
            $this->warn("  {$empty->count()} course(s) have an empty body in the legacy data:");
            foreach ($empty as $course) {
                $this->line("    · {$course->title}  (/{$course->slug})");
            }
        }
    }
}
