<?php

namespace App\Models;

use App\Models\Concerns\HasLegacyKey;
use App\Models\Concerns\HasUploadUrls;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Slide extends Model
{
    use HasLegacyKey, HasUploadUrls;

    protected $guarded = [];

    protected $casts = [
        'published' => 'boolean',
        'ai_alt_generated' => 'boolean',
    ];

    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn () => $this->uploadUrl($this->image));
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('published', true);
    }

    public function scopeGroup(Builder $q, string $group): Builder
    {
        return $q->where('group', $group);
    }
}
