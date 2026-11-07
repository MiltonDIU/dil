<?php

namespace App\Console\Commands\Legacy;

use App\Support\Text;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Shared behaviour for every `legacy:*` importer.
 *
 * Every importer is idempotent — it keys on (legacy_source, legacy_id) and uses
 * updateOrCreate, so any step is safe to re-run at any time. That is the
 * feature, not an optimisation: these commands get run dozens of times while
 * the mapping is being tuned.
 */
abstract class LegacyCommand extends Command
{
    /** Rows created during this run. */
    protected int $created = 0;

    /** Rows updated during this run. */
    protected int $updated = 0;

    /** Rows skipped, keyed by reason. @var array<string,int> */
    protected array $skipped = [];

    protected function cfg(string $entity): array
    {
        $config = config("migration.{$entity}");

        if (! is_array($config)) {
            throw new \RuntimeException("Missing config/migration/{$entity}.php");
        }

        return $config;
    }

    /**
     * A query against the read-only legacy connection, with any `where`
     * clauses from the mapping already applied.
     */
    protected function legacy(array $cfg): Builder
    {
        $query = DB::connection(config('migration.connection'))->table($cfg['table']);

        foreach ($cfg['where'] ?? [] as [$column, $operator, $value]) {
            $query->where($column, $operator, $value);
        }

        if (! empty($cfg['legacy_ids'])) {
            $query->whereIn($cfg['key'], $cfg['legacy_ids']);
        }

        return $query->orderBy($cfg['key']);
    }

    /**
     * Apply the mapping's `map`, `casts` and `defaults` to one legacy row.
     *
     * fixMojibake() runs on every string before anything else — the playbook's
     * hardest-won rule.
     *
     * @return array<string, mixed>
     */
    protected function mapRow(object $row, array $cfg): array
    {
        $data = $cfg['defaults'] ?? [];

        foreach ($cfg['map'] as $column => $attribute) {
            $raw = $row->{$column} ?? null;

            $value = is_string($raw) ? Text::fixMojibake(trim($raw)) : $raw;

            $data[$attribute] = $this->cast($cfg['casts'][$attribute] ?? null, $value);
        }

        return $data;
    }

    /**
     * Named transforms referenced from a mapping's `casts` array.
     */
    protected function cast(?string $cast, mixed $value): mixed
    {
        return match ($cast) {
            null => $value,

            'bool' => (bool) $value,

            // Joomla state: 1 published, 0 unpublished, 2 archived, -2 trashed
            'state' => config('migration.state_map')[(int) $value] ?? false,

            'datetime' => Text::date(is_string($value) ? $value : null),

            'int' => $value === null || $value === '' ? null : (int) $value,

            'html' => $this->prepareHtml($value),

            // Joomla stored target as 0 = same tab, 1/2 = new window
            'browser_nav' => ((int) $value) > 0 ? '_blank' : '_self',

            'json' => is_string($value) ? json_decode($value, true) : $value,

            default => $value,
        };
    }

    /**
     * Clean editor cruft and rewrite inline asset paths to /storage/... URLs.
     */
    protected function prepareHtml(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        return Text::rewriteAssetPaths(
            Text::cleanHtml($value),
            config('migration.media.html_path_map', []),
        );
    }

    /**
     * updateOrCreate on the legacy key, tracking created/updated counts.
     */
    protected function upsert(string $model, string $source, int|string $legacyId, array $data): object
    {
        $record = $model::updateOrCreate(
            ['legacy_source' => $source, 'legacy_id' => $legacyId],
            $data,
        );

        $record->wasRecentlyCreated ? $this->created++ : $this->updated++;

        return $record;
    }

    protected function skip(string $reason): void
    {
        $this->skipped[$reason] = ($this->skipped[$reason] ?? 0) + 1;
    }

    /**
     * Uniform end-of-run report. Never claim success without numbers.
     */
    protected function report(string $label, int $sourceCount, int $targetCount): int
    {
        $this->newLine();
        $this->line("  <fg=cyan>{$label}</>");
        $this->line(sprintf(
            '    source %d  ->  target %d   (created %d, updated %d)',
            $sourceCount, $targetCount, $this->created, $this->updated,
        ));

        foreach ($this->skipped as $reason => $count) {
            $this->line("    <fg=yellow>skipped {$count}</> — {$reason}");
        }

        return self::SUCCESS;
    }
}
