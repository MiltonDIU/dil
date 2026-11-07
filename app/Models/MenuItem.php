<?php

namespace App\Models;

use App\Models\Concerns\HasLegacyKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Route;

class MenuItem extends Model
{
    use HasLegacyKey;

    protected $guarded = [];

    protected $casts = [
        'published' => 'boolean',
        'is_home' => 'boolean',
    ];

    /**
     * Keep the polymorphic target consistent with `type`.
     *
     * The admin form only asks which record to link to; the class it belongs
     * to is implied by the type, so deriving it here means neither Filament
     * nor the importer can leave the pair half-set.
     */
    protected static function booted(): void
    {
        static::saving(function (MenuItem $item) {
            $item->linkable_type = match ($item->type) {
                'page' => Page::class,
                'course' => Course::class,
                'post' => Post::class,
                default => null,
            };

            if ($item->linkable_type === null) {
                $item->linkable_id = null;
            }

            // Only one of these three can be meaningful at a time.
            if ($item->type !== 'route') {
                $item->route_name = null;
            }

            if ($item->type !== 'url') {
                $item->url = null;
            }
        });
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('ordering');
    }

    /**
     * How many levels below the top this item sits (0 = top-level).
     *
     * Walks the `parent` relation rather than trusting a stored column, so it
     * stays correct even if a row is re-parented directly in the database.
     * Capped so a corrupted/cyclical chain can never loop forever.
     */
    public function depth(): int
    {
        $depth = 0;
        $item = $this;

        while ($item->parent_id !== null && $depth < 10) {
            $item = $item->parent;
            $depth++;
        }

        return $depth;
    }

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('published', true);
    }

    /**
     * Resolve to a real front-end URL.
     *
     * Returns null for `none` items — Joomla stored six of these as link='#'
     * (Courses, Team, Training, Tests, Events, Upcoming). They are dropdown
     * parents, and rendering them as dead href="#" links is exactly the
     * behaviour we do not want to carry over.
     */
    public function resolvedUrl(): ?string
    {
        if ($this->is_home) {
            return route('home');
        }

        return match ($this->type) {
            'page' => $this->linkable ? route('page.show', $this->linkable) : null,
            'post' => $this->linkable ? route('post.show', $this->linkable) : null,
            'course' => $this->linkable ? route('course.show', $this->linkable) : null,
            'route' => $this->route_name && Route::has($this->route_name)
                ? route($this->route_name)
                : null,
            'url' => $this->url ?: null,
            default => null,
        };
    }

    public function isExternal(): bool
    {
        $url = $this->resolvedUrl();

        return $url !== null
            && str_starts_with($url, 'http')
            && ! str_starts_with($url, rtrim(config('app.url'), '/'));
    }

    /** True when this item points at a page an editor has not written yet. */
    public function pointsAtEmptyContent(): bool
    {
        $target = $this->linkable;

        return $target instanceof Page || $target instanceof Course
            ? $target->isEmpty()
            : false;
    }
}
