<?php

namespace App\Models;

use App\Models\Concerns\HasLegacyKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One section of a course's lesson breakdown ("Weeks 1–2: Introduction &
 * Basics"). Ordered per course, edited inline on the course admin screen.
 */
class Lesson extends Model
{
    use HasLegacyKey;

    protected $guarded = [];

    protected $casts = [
        'published' => 'boolean',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('published', true);
    }
}
