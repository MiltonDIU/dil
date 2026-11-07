<?php

namespace App\Support;

use App\Models\Menu;
use App\Models\Setting;
use Illuminate\Support\Collection;

/**
 * The single accessor Blade uses for site identity and navigation.
 *
 * Everything here reads from the database, so nothing an editor might change
 * is ever hardcoded in a template.
 */
class Site
{
    private ?Collection $menuCache = null;

    public function get(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }

    /** Absolute URL for an image-typed setting (logo, favicon, share image). */
    public function url(string $key): ?string
    {
        return Setting::url($key);
    }

    public function name(): string
    {
        return (string) ($this->get('site_name') ?: config('app.name'));
    }

    public function shortName(): string
    {
        return (string) ($this->get('site_short_name') ?: $this->name());
    }

    /** Copyright line with {year} expanded. */
    public function copyright(): string
    {
        $line = (string) ($this->get('copyright')
            ?: '© {year} '.$this->name().'. All Rights Reserved.');

        return str_replace('{year}', (string) now()->year, $line);
    }

    /**
     * Social links that actually have a URL.
     *
     * Helix stored all five as "#" placeholders; those imported as NULL, so
     * the footer renders no dead icons until the client fills them in.
     *
     * @return array<string, string>
     */
    public function socials(): array
    {
        $out = [];

        foreach (['facebook', 'linkedin', 'instagram', 'youtube', 'pinterest'] as $network) {
            $url = $this->get("social_{$network}");

            if (filled($url) && $url !== '#') {
                $out[$network] = $url;
            }
        }

        return $out;
    }

    /** Published top-level items of the main menu, children eager-loaded. */
    public function menu(string $slug = 'mainmenu'): Collection
    {
        if ($this->menuCache !== null) {
            return $this->menuCache;
        }

        try {
            return $this->menuCache = Menu::where('slug', $slug)->first()?->tree() ?? collect();
        } catch (\Throwable) {
            // An unmigrated database must not break the whole page.
            return $this->menuCache = collect();
        }
    }
}
