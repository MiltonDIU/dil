<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

/**
 * Hard Rule 1: the legacy database is a READ-ONLY source.
 *
 * Enforcement is layered, because app-level checks alone are not trustworthy:
 *
 *  1. The `dil_legacy_ro` MySQL user is granted SELECT only (see PRODUCTION.md
 *     and `php artisan legacy:harden`). That is the real enforcement.
 *  2. This guard is defence in depth: it rejects the statement *before* it is
 *     sent, so a stray write fails loudly with a useful message instead of a
 *     raw SQL permission error.
 *
 * Note: we hook `beforeExecuting` rather than the `StatementPrepared` event.
 * `Connection::affectingStatement()` (UPDATE/DELETE) and `statement()`
 * (INSERT/DDL) never call `prepared()`, so `StatementPrepared` only ever
 * observes SELECTs — it cannot guard writes.
 */
class LegacyGuardServiceProvider extends ServiceProvider
{
    /**
     * Leading keywords that are safe against the legacy connection.
     */
    private const ALLOWED = [
        'select', 'show', 'describe', 'desc', 'explain', 'set', 'use', 'with',
    ];

    public function boot(): void
    {
        if (! array_key_exists('legacy', config('database.connections', []))) {
            return;
        }

        DB::connection('legacy')->beforeExecuting(
            function (string $query, array $bindings, $connection): void {
                $verb = strtolower(strtok(ltrim($query), " \t\n\r(") ?: '');

                if (in_array($verb, self::ALLOWED, true)) {
                    return;
                }

                throw new \RuntimeException(
                    "Refused a '{$verb}' statement on the read-only `legacy` connection. ".
                    'The legacy Joomla database is a source, never a target. '.
                    'Statement: '.Str::limit($query, 160)
                );
            }
        );
    }
}
