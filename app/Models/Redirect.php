<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Redirect extends Model
{
    protected $guarded = [];

    protected $casts = [
        'published' => 'boolean',
        'last_hit_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Redirect $r) {
            $r->source = static::normalize($r->source);
            $r->source_hash = hash('sha256', $r->source);
        });
    }

    /**
     * Normalize a request path so that /a, /a/, //a and /A?x=1&y=2 all agree.
     *
     * Query strings are preserved but their parameters are sorted, because
     * Joomla emitted the same page as both ?option=…&view=…&id=17 and
     * ?view=…&option=…&id=17 depending on which template built the link.
     */
    public static function normalize(string $url): string
    {
        $url = trim($url);
        $parts = parse_url($url);

        $path = $parts['path'] ?? '/';
        $path = '/'.trim($path, '/');
        $path = preg_replace('#/+#', '/', $path) ?? $path;

        if (! empty($parts['query'])) {
            parse_str($parts['query'], $params);
            unset($params['Itemid'], $params['lang'], $params['tmpl']);
            ksort($params);

            if ($params !== []) {
                return $path.'?'.http_build_query($params);
            }
        }

        return $path;
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('published', true);
    }
}
