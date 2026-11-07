<?php

namespace App\Models;

use App\Models\Concerns\HasLegacyKey;
use App\Models\Concerns\HasUploadUrls;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class Block extends Model
{
    use HasLegacyKey, HasUploadUrls;

    protected $guarded = [];

    protected $casts = [
        'data' => 'array',
        'published' => 'boolean',
        'full_width' => 'boolean',
    ];

    public const TYPES = [
        'rich_text' => 'Rich text',
        'carousel' => 'Slider',
        'feature_cards' => 'Feature cards',
        'video' => 'Video',
        'link_list' => 'Link list',
        'map' => 'Map embed',
        'cta' => 'Call to action',
        'html' => 'Raw HTML',
        'course_spotlight' => 'Course spotlight',
        'language_grid' => 'Languages we teach',
        'gallery' => 'Photo gallery',
    ];

    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn () => $this->uploadUrl($this->image));
    }

    /**
     * The courses a `course_spotlight` block features, in the order the editor
     * chose them. Unpublished courses drop out rather than rendering dead links.
     */
    public function spotlightCourses(): Collection
    {
        $ids = array_map('intval', $this->data['course_ids'] ?? []);

        return Course::published()->whereIn('id', $ids)->get()
            ->sortBy(fn (Course $course) => array_search($course->id, $ids, true))
            ->values();
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('published', true);
    }

    public function scopeGroup(Builder $q, string $group): Builder
    {
        return $q->where('group', $group);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /** The Blade partial that renders this block. */
    public function view(): string
    {
        return 'blocks.'.$this->type;
    }
}
