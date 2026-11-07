<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Every migrated table carries (legacy_source, legacy_id) as a unique pair.
 * That pair is what makes every import step idempotent and re-runnable.
 */
trait HasLegacyKey
{
    public function scopeFromLegacy(Builder $query, string $source, int|string $id): Builder
    {
        return $query->where('legacy_source', $source)->where('legacy_id', $id);
    }

    /**
     * The identity used by every `updateOrCreate` in the importers.
     */
    public static function legacyKey(string $source, int|string $id): array
    {
        return ['legacy_source' => $source, 'legacy_id' => $id];
    }

    public function scopeMigrated(Builder $query): Builder
    {
        return $query->whereNotNull('legacy_id');
    }
}
