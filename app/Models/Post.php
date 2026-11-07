<?php

namespace App\Models;

use App\Models\Concerns\HasLegacyKey;
use App\Models\Concerns\HasUploadUrls;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    use HasLegacyKey, HasUploadUrls;

    protected $guarded = [];

    protected $casts = [
        'published' => 'boolean',
        'featured' => 'boolean',
        'published_at' => 'datetime',
        'ai_meta_generated' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    protected function featuredImageUrl(): Attribute
    {
        return Attribute::get(fn () => $this->uploadUrl($this->featured_image));
    }

    protected function ogImageUrl(): Attribute
    {
        return Attribute::get(fn () => $this->uploadUrl($this->og_image ?: $this->featured_image));
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('published', true)
            ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function metaTitle(): string
    {
        return $this->meta_title ?: $this->title;
    }
}
