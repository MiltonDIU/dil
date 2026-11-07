<?php

namespace App\Support;

/**
 * Reader for an SP Page Builder 5 layout blob.
 *
 * SPPB 5 keeps the layout in `sppagebuilder.content` (the pre-5 `text` column
 * is still present but empty here) shaped as:
 *
 *     [ { id, settings, layout, columns: [ { addons: [ { name, settings } ] } ] } ]
 *
 * Nested rows can appear under an addon's own `settings.rows`.
 */
class SppbLayout
{
    /** @param array<int, array<string, mixed>> $rows */
    public function __construct(private array $rows) {}

    public static function fromJson(?string $json): self
    {
        $decoded = json_decode((string) $json, true);

        return new self(is_array($decoded) ? $decoded : []);
    }

    /** @return array<int, array<string, mixed>> */
    public function rows(): array
    {
        return $this->rows;
    }

    /**
     * Every addon in document order, each tagged with the row and column it
     * came from so a mapper can reason about layout.
     *
     * @return array<int, array{row:int, column:int, layout:string, name:string, settings:array}>
     */
    public function addons(): array
    {
        $out = [];

        $walk = function (array $rows, int $depth) use (&$walk, &$out): void {
            foreach ($rows as $rowIndex => $row) {
                foreach ($row['columns'] ?? [] as $colIndex => $column) {
                    foreach ($column['addons'] ?? [] as $addon) {
                        $out[] = [
                            'row' => $rowIndex,
                            'column' => $colIndex,
                            'layout' => (string) ($row['layout'] ?? ''),
                            'name' => (string) ($addon['name'] ?? ''),
                            'settings' => $addon['settings'] ?? [],
                            'raw' => $addon,
                        ];

                        if (! empty($addon['settings']['rows']) && $depth < 4) {
                            $walk($addon['settings']['rows'], $depth + 1);
                        }
                    }
                }
            }
        };

        $walk($this->rows, 0);

        return $out;
    }

    /**
     * Addons grouped by row index, preserving order.
     *
     * @return array<int, array{layout:string, columns:array<int, array<int, array>>}>
     */
    public function byRow(): array
    {
        $rows = [];

        foreach ($this->addons() as $addon) {
            $rows[$addon['row']]['layout'] = $addon['layout'];
            $rows[$addon['row']]['columns'][$addon['column']][] = $addon;
        }

        return $rows;
    }

    /**
     * Read a dotted path out of an addon's settings array.
     * SPPB nests almost everything ("bg.src", "button_url.url").
     */
    public static function get(array $settings, string $path, mixed $default = null): mixed
    {
        $value = $settings;

        foreach (explode('.', $path) as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value === '' ? $default : $value;
    }

    /**
     * SPPB link fields are objects: {"type":"url","url":"…","new_tab":false}.
     * Returns null for the very common empty case.
     */
    public static function link(array $settings, string $key): ?string
    {
        $url = self::get($settings, $key.'.url');

        if (! is_string($url) || trim($url) === '' || trim($url) === '#') {
            return null;
        }

        return trim($url);
    }
}
