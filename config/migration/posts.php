<?php

use App\Models\Post;

return [
    'table' => 'content',
    'source' => 'a0cbr_content',
    'key' => 'id',
    'model' => Post::class,

    // The "News" category. Both legacy rows are trashed, so this imports two
    // unpublished posts and the news section ships empty but functional.
    'where' => [
        ['catid', '=', 8],
    ],

    'map' => [
        'title' => 'title',
        'alias' => 'slug',
        'introtext' => 'body',
        'state' => 'published',
        'featured' => 'featured',
        'publish_up' => 'published_at',
        'hits' => 'hits',
        'catid' => 'legacy_category_id',
        'metadesc' => 'meta_description',
    ],

    'casts' => [
        'published' => 'state',
        'featured' => 'bool',
        'published_at' => 'datetime',
        'body' => 'html',
    ],

    'defaults' => [],
];
