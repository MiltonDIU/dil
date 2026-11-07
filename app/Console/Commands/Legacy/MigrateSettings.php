<?php

namespace App\Console\Commands\Legacy;

use App\Models\Setting;
use App\Support\SppbLayout;
use App\Support\Text;
use Illuminate\Support\Facades\DB;

/**
 * Everything that used to be locked in Joomla's configuration.php or the Helix
 * Ultimate template params becomes an editable `settings` row.
 */
class MigrateSettings extends LegacyCommand
{
    protected $signature = 'legacy:settings {--force : Overwrite values an editor has already changed}';

    protected $description = 'Import site identity, contacts, socials and theme values as settings';

    public function handle(): int
    {
        $cfg = $this->cfg('settings');

        $helix = $this->helixParams($cfg);
        $sppb = $this->sppbValues();

        foreach ($cfg['settings'] as $definition) {
            $key = $definition['key'];
            $existing = Setting::where('key', $key)->first();

            $value = $this->resolveValue($definition['from'], $helix, $sppb);

            /*
             * Re-runs must never clobber an editor's work. We only write the
             * value when the row is new, when it is still exactly what we
             * imported last time, or when --force is given.
             */
            $attributes = [
                'group' => $definition['group'],
                'label' => $definition['label'],
                'type' => $definition['type'],
                'ordering' => $definition['ordering'],
                'description' => $definition['description'] ?? null,
            ];

            if ($existing === null || $this->option('force')) {
                $attributes['value'] = $value;
            } else {
                $this->skip('kept existing value for '.$key);
            }

            Setting::updateOrCreate(['key' => $key], $attributes);

            $existing === null ? $this->created++ : $this->updated++;
        }

        Setting::flushCache();

        $this->reportBlanks();

        return $this->report('Settings', count($cfg['settings']), Setting::count());
    }

    /** @return array<string, mixed> */
    private function helixParams(array $cfg): array
    {
        $params = DB::connection(config('migration.connection'))
            ->table('template_styles')->where('id', $cfg['template_style_id'])->value('params');

        return json_decode((string) $params, true) ?: [];
    }

    /**
     * The Google Maps embed lives in a homepage text_block, not in any config
     * table, so it is lifted out of the page builder blob.
     *
     * @return array<string, string>
     */
    private function sppbValues(): array
    {
        $row = DB::connection(config('migration.connection'))
            ->table('sppagebuilder')->where('id', 1)->first();

        if (! $row) {
            return [];
        }

        foreach (SppbLayout::fromJson($row->content)->addons() as $addon) {
            $html = (string) SppbLayout::get($addon['settings'], 'text', '');

            if (preg_match('~<iframe[^>]+google\.com/maps~i', $html)) {
                return ['map' => trim($html)];
            }
        }

        return [];
    }

    private function resolveValue(string $from, array $helix, array $sppb): ?string
    {
        [$kind, $ref] = array_pad(explode(':', $from, 2), 2, '');

        $value = match ($kind) {
            'helix' => $helix[$ref] ?? null,
            'sppb' => $sppb[$ref] ?? null,
            'const' => $ref,
            default => null,
        };

        if (! is_string($value)) {
            $value = $value === null ? null : (string) $value;
        }

        $value = $value === null ? null : Text::fixMojibake(trim($value));

        /*
         * Helix stored all five social URLs as "#" placeholders. Importing
         * those verbatim would render five dead icons in the footer, so they
         * become NULL and the footer simply omits them.
         */
        if ($value === '#' || $value === '') {
            return null;
        }

        // Helix stores image paths JSON-escaped ("images\/dil-logo.png") and
        // site-relative; settings hold disk-relative paths on the public disk.
        if (str_contains($value, 'images/') || str_contains($value, 'images\\/')) {
            $path = str_replace('\\/', '/', $value);
            $value = trim(config('migration.media.root'), '/').'/'.ltrim($path, '/');
        }

        return $value;
    }

    private function reportBlanks(): void
    {
        $blank = Setting::whereNull('value')->orWhere('value', '')->pluck('key');

        if ($blank->isEmpty()) {
            return;
        }

        $this->newLine();
        $this->warn("  {$blank->count()} setting(s) have no value and need the client to supply one:");
        $this->line('    '.$blank->implode(', '));
    }
}
