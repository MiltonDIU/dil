<?php

namespace App\Models;

use App\Models\Concerns\HasLegacyKey;
use App\Models\Concerns\HasUploadUrls;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasLegacyKey, HasUploadUrls;

    protected $guarded = [];

    protected $casts = [
        'published' => 'boolean',
        'published_at' => 'datetime',
        'ai_meta_generated' => 'boolean',
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

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('published', true);
    }

    /** True when the page has a body an editor still needs to write. */
    public function isEmpty(): bool
    {
        return blank(trim(strip_tags((string) $this->body)));
    }

    public function metaTitle(): string
    {
        return $this->meta_title ?: $this->title;
    }
}
