<?php

namespace App\Models;

use App\Models\Concerns\HasUploadUrls;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Key/value site configuration. Replaces Joomla's configuration.php and the
 * Helix Ultimate template params — site name, logo, contacts, socials,
 * theme colours, analytics. Nothing an editor might change lives in Blade.
 */
class Setting extends Model
{
    use HasUploadUrls;

    protected $guarded = [];

    protected $casts = [
        'options' => 'array',
    ];

    public const CACHE_KEY = 'settings.all';

    protected static function booted(): void
    {
        static::saved(fn () => static::flushCache());
        static::deleted(fn () => static::flushCache());
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * All settings as a flat key => typed value map (cached).
     *
     * @return array<string, mixed>
     */
    public static function all_values(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return static::query()->get()
                ->mapWithKeys(fn (Setting $s) => [$s->key => $s->typedValue()])
                ->all();
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::all_values()[$key] ?? $default;
    }

    /**
     * Absolute URL for an image-typed setting (logo, favicon, og image).
     */
    public static function url(string $key): ?string
    {
        $value = static::get($key);

        return $value ? (new static)->uploadUrl($value) : null;
    }

    public function typedValue(): mixed
    {
        $v = $this->value;

        return match ($this->type) {
            'boolean' => filter_var($v, FILTER_VALIDATE_BOOLEAN),
            'number' => is_numeric($v) ? $v + 0 : null,
            'json' => json_decode((string) $v, true),
            default => $v,
        };
    }
}
