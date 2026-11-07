<?php

namespace App\Models;

use App\Models\Concerns\HasLegacyKey;
use App\Models\Concerns\HasUploadUrls;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection as SupportCollection;

class Course extends Model
{
    use HasLegacyKey, HasUploadUrls;

    protected $guarded = [];

    protected $casts = [
        'cefr_levels' => 'array',
        'published' => 'boolean',
        'featured' => 'boolean',
        'published_at' => 'datetime',
        'ai_normalized' => 'boolean',
        'ai_meta_generated' => 'boolean',
        'ai_extracted_at' => 'datetime',
        'ai_reviewed_at' => 'datetime',
        'fee_regular' => 'decimal:2',
        'fee_daffodil_family' => 'decimal:2',
        'fee_outside' => 'decimal:2',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected function featuredImageUrl(): Attribute
    {
        return Attribute::get(fn () => $this->uploadUrl($this->featured_image));
    }

    protected function ogImageUrl(): Attribute
    {
        return Attribute::get(fn () => $this->uploadUrl($this->og_image ?: $this->featured_image));
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('ordering');
    }

    public function publishedLessons(): HasMany
    {
        return $this->lessons()->where('published', true);
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('published', true);
    }

    /**
     * Published courses keyed by language, for /courses and the homepage grid.
     *
     * A language with a course marked `featured` comes first, so promoting a
     * language is an admin decision rather than a code change. Titles are
     * trimmed for sorting because several imported ones start with a space.
     *
     * @return SupportCollection<string, EloquentCollection<int, self>>
     */
    public static function byLanguage(): SupportCollection
    {
        return static::published()->get()
            ->sortBy([
                ['ordering', 'asc'],
                fn (self $a, self $b) => strnatcasecmp(trim($a->title), trim($b->title)),
            ])
            ->groupBy(fn (self $course) => $course->language ?: 'Other')
            ->sortBy(fn ($courses, string $language) => sprintf(
                '%d%d%s',
                $courses->contains('featured', true) ? 0 : 1,
                $language === 'Other' ? 1 : 0,
                $language,
            ))
            ->toBase();
    }

    /**
     * "German Language" for a heading or tile. `language` itself stays the bare
     * name, because the course page already labels it "Language: German".
     */
    public static function languageLabel(string $language): string
    {
        return $language === 'Other' ? 'Other courses' : $language.' Language';
    }

    /**
     * The body we actually render.
     *
     * AI-extracted `curriculum_html` is preferred, but `body` (deterministically
     * cleaned) and then `body_raw` (untouched original) always stand behind it.
     * The course page therefore renders correctly with AI disabled or failed.
     */
    public function renderableBody(): ?string
    {
        return $this->curriculum_html ?: ($this->body ?: $this->body_raw);
    }

    public function isEmpty(): bool
    {
        return blank(trim(strip_tags((string) $this->renderableBody())));
    }

    /**
     * "Tk. 12,000" — null when the fee was never stated in the legacy copy.
     */
    public function formatFee(?string $tier = 'regular'): ?string
    {
        $value = match ($tier) {
            'daffodil' => $this->fee_daffodil_family,
            'outside' => $this->fee_outside,
            default => $this->fee_regular,
        };

        if ($value === null) {
            return null;
        }

        $symbol = $this->currency === 'BDT' ? 'Tk.' : $this->currency;

        return $symbol.' '.number_format((float) $value, 0);
    }

    public function levelsLabel(): ?string
    {
        $levels = $this->cefr_levels;

        return filled($levels) ? implode(', ', $levels) : $this->cefr_levels_raw;
    }

    public function metaTitle(): string
    {
        return $this->meta_title ?: $this->title;
    }
}
