<?php

namespace App\Models;

use App\Models\Concerns\HasLegacyKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasLegacyKey;

    protected $guarded = [];

    protected $casts = [
        'published' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('ordering');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('published', true);
    }
}
