<?php

namespace App\Console\Commands\Ai;

use App\Models\Course;
use Illuminate\Support\Str;

/**
 * Promote course free text to typed columns.
 *
 * This is the one place on this project where a mapping genuinely cannot do
 * the job: fees, CEFR levels and durations are stated in prose whose wording,
 * punctuation and number formatting differ in every article —
 *
 *   "Fees: Tk. 12000/- (Regular) Tk. 8,000/- (Only for Daffodil Family
 *    participants) (For Outside Students Level: A1 – 10,000/-)"
 *
 * `body_raw` is never touched, so a wrong extraction is always recoverable and
 * the course page renders correctly with this command never run.
 */
class ExtractCourses extends AiCommand
{
    protected $signature = 'ai:extract-courses
        {--dry-run   : Show what would be sent and changed, write nothing}
        {--force     : Re-ask Gemini even for cached/reviewed values}
        {--only=*    : Limit to these course slugs}';

    protected $description = 'Extract typed course fields (fees, levels, duration) from legacy prose';

    public function handle(): int
    {
        if (! $this->guardConfigured()) {
            return self::SUCCESS;
        }

        $courses = Course::query()
            ->when($this->option('only'), fn ($q, $slugs) => $q->whereIn('slug', $slugs))
            ->orderBy('ordering')
            ->get()
            ->reject(fn (Course $c) => $c->isEmpty());

        if ($courses->isEmpty()) {
            $this->warn('  No courses with a body to extract from.');

            return self::SUCCESS;
        }

        $touched = 0;

        /*
         * Twelve courses, each a long unique document — so this is one call per
         * course, not per row of a 20,000-row table. The distinct-value
         * batching rule applies to repeated short strings; here each input is
         * genuinely unique and too long to batch meaningfully.
         */
        foreach ($courses as $course) {
            $input = $this->plainText($course);

            $result = $this->askGemini(
                'course_extract',
                $course->slug.'|'.md5($input),
                $this->prompt($course, $input),
                $this->schema(),
                $this->system(),
            );

            if ($result === null) {
                continue;
            }

            $before = sprintf(
                'fee=%s levels=%s months=%s',
                $course->fee_regular ?? '—',
                $course->levelsLabel() ?? '—',
                $course->duration_months ?? '—',
            );

            $updates = $this->mapResult($result, $course);

            if ($updates === []) {
                continue;
            }

            if (! $this->option('dry-run')) {
                $course->update($updates + [
                    'ai_normalized' => true,
                    'ai_extracted_at' => now(),
                ]);
            }

            $touched++;

            $this->sample(
                $course->title,
                $before,
                sprintf(
                    'fee=%s levels=%s months=%s',
                    $updates['fee_regular'] ?? $course->fee_regular ?? '—',
                    implode(',', $updates['cefr_levels'] ?? $course->cefr_levels ?? []) ?: '—',
                    $updates['duration_months'] ?? $course->duration_months ?? '—',
                ),
            );
        }

        return $this->reportAi('Course extraction', $touched);
    }

    /**
     * Only fill columns that are still empty, unless --force. A human
     * correction made in Filament therefore survives the next run.
     *
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function mapResult(array $result, Course $course): array
    {
        $candidates = [
            'language' => $this->str($result['language'] ?? null),
            'cefr_levels' => $this->levels($result['cefr_levels'] ?? null),
            'duration_months' => $this->int($result['duration_months'] ?? null),
            'classes_per_week' => $this->int($result['classes_per_week'] ?? null),
            'total_classes' => $this->int($result['total_classes'] ?? null),
            'class_duration_minutes' => $this->int($result['class_duration_minutes'] ?? null),
            'fee_regular' => $this->money($result['fee_regular'] ?? null),
            'fee_daffodil_family' => $this->money($result['fee_daffodil_family'] ?? null),
            'fee_outside' => $this->money($result['fee_outside'] ?? null),
            'fee_note' => $this->str($result['fee_note'] ?? null),
            'summary' => $this->str($result['summary'] ?? null),
        ];

        $updates = [];

        foreach ($candidates as $column => $value) {
            if ($value === null) {
                continue;
            }

            if ($this->option('force') || blank($course->{$column})) {
                $updates[$column] = $value;
            }
        }

        return $updates;
    }

    private function str(mixed $v): ?string
    {
        $v = is_string($v) ? trim($v) : null;

        return ($v === '' || $v === null || strcasecmp($v, 'unknown') === 0) ? null : $v;
    }

    private function int(mixed $v): ?int
    {
        return is_numeric($v) && (int) $v > 0 ? (int) $v : null;
    }

    private function money(mixed $v): ?float
    {
        return is_numeric($v) && (float) $v > 0 ? round((float) $v, 2) : null;
    }

    /** @return array<int, string>|null */
    private function levels(mixed $v): ?array
    {
        if (! is_array($v)) {
            return null;
        }

        $levels = array_values(array_filter(array_map(
            fn ($l) => is_string($l) && preg_match('~^[ABC][12]$~i', trim($l)) ? strtoupper(trim($l)) : null,
            $v,
        )));

        return $levels === [] ? null : $levels;
    }

    private function plainText(Course $course): string
    {
        $text = html_entity_decode(
            strip_tags((string) ($course->body ?: $course->body_raw)),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8',
        );

        return Str::limit(trim(preg_replace('~\s+~u', ' ', $text) ?? ''), 12000, '');
    }

    private function system(): string
    {
        return <<<'TXT'
        You extract structured facts from course descriptions for a language
        institute in Bangladesh (Daffodil Institute of Languages & Culture).

        Rules:
        - Extract ONLY what the text actually states. Never infer, estimate or invent.
        - If a value is not stated, return null for it. A null is always better than a guess.
        - Fees are in Bangladeshi Taka. "Tk. 12,000/-" is 12000. Return a plain number,
          no currency symbol, no thousands separator.
        - There are up to three fee tiers: the regular/general fee, a discounted fee for
          "Daffodil Family" participants, and a fee for "Outside Students".
          Map each to its own field; if only one fee is stated, it is the regular fee.
        - CEFR levels are A1, A2, B1, B2, C1, C2. Watch for OCR-style typos where
          "A1" was written as "Al" (letter L) — treat those as A1.
        - duration_months, classes_per_week, total_classes and class_duration_minutes
          are integers.
        TXT;
    }

    private function prompt(Course $course, string $text): string
    {
        return <<<TXT
        Course title: {$course->title}

        Extract the structured fields from this course description.
        Return null for anything the text does not state.
        Write `summary` as one neutral sentence (max 200 characters) describing who
        the course is for — using only information present in the text.

        --- COURSE DESCRIPTION ---
        {$text}
        TXT;
    }

    /** @return array<string, mixed> */
    private function schema(): array
    {
        $nullableInt = ['type' => 'integer', 'nullable' => true];
        $nullableNum = ['type' => 'number', 'nullable' => true];
        $nullableStr = ['type' => 'string', 'nullable' => true];

        return [
            'type' => 'object',
            'properties' => [
                'language' => $nullableStr,
                'cefr_levels' => ['type' => 'array', 'items' => ['type' => 'string']],
                'duration_months' => $nullableInt,
                'classes_per_week' => $nullableInt,
                'total_classes' => $nullableInt,
                'class_duration_minutes' => $nullableInt,
                'fee_regular' => $nullableNum,
                'fee_daffodil_family' => $nullableNum,
                'fee_outside' => $nullableNum,
                'fee_note' => $nullableStr,
                'summary' => $nullableStr,
            ],
            'required' => ['language', 'cefr_levels'],
        ];
    }
}
