<?php

namespace App\Models;

use App\Models\Concerns\HasLegacyKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Menu extends Model
{
    use HasLegacyKey;

    protected $guarded = [];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('ordering');
    }

    /**
     * Top-level items with two levels of children eager-loaded (published
     * only), so templates can render up to 3 levels deep without N+1s.
     */
    public function tree(): Collection
    {
        return $this->items()
            ->whereNull('parent_id')
            ->where('published', true)
            ->with([
                'linkable',
                'children' => fn ($q) => $q->where('published', true)->orderBy('ordering'),
                'children.linkable',
                'children.children' => fn ($q) => $q->where('published', true)->orderBy('ordering'),
                'children.children.linkable',
            ])
            ->orderBy('ordering')
            ->get();
    }
}
