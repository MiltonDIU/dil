<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Every AI decision is cached here, so that re-runs are deterministic and free,
 * a human can correct a decision in Filament, and that correction survives the
 * next re-run — importers look here before calling the API and never overwrite
 * a row a human has reviewed.
 */
class AiMapping extends Model
{
    protected $guarded = [];

    protected $casts = [
        'output_json' => 'array',
        'reviewed' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public static function hashFor(string $input): string
    {
        return hash('sha256', trim($input));
    }

    /**
     * Cached output for an input, or null if we have never asked.
     */
    public static function lookup(string $type, string $input): ?array
    {
        return static::query()
            ->where('type', $type)
            ->where('input_hash', static::hashFor($input))
            ->value('output_json');
    }

    /**
     * Store a result. A human-reviewed row is never overwritten by a re-run —
     * that is what makes corrections stick.
     */
    public static function remember(
        string $type,
        string $input,
        ?array $output,
        ?string $model = null,
        bool $force = false,
    ): void {
        $existing = static::query()
            ->where('type', $type)
            ->where('input_hash', static::hashFor($input))
            ->first();

        if ($existing?->reviewed && ! $force) {
            return;
        }

        static::updateOrCreate(
            ['type' => $type, 'input_hash' => static::hashFor($input)],
            ['input' => $input, 'output_json' => $output, 'model' => $model],
        );
    }
}
