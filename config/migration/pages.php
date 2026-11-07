<?php

use App\Models\Page;

return [
    'table' => 'content',
    'source' => 'a0cbr_content',
    'key' => 'id',
    'model' => Page::class,

    /*
     | Everything in the "Main" category that is not a course.
     | Course ids are excluded via config('migration.courses.legacy_ids').
     */
    'where' => [
        ['catid', '=', 2],
    ],

    'map' => [
        'title' => 'title',
        'alias' => 'slug',
        'introtext' => 'body',
        'state' => 'published',
        'publish_up' => 'published_at',
        'hits' => 'hits',
        'metadesc' => 'meta_description',
        'metakey' => 'meta_title',
    ],

    'casts' => [
        'published' => 'state',
        'published_at' => 'datetime',
        'body' => 'html',
    ],

    'defaults' => [],

    /*
     | Legacy aliases we deliberately improve. The old alias is still captured
     | in the redirect map, so no inbound link breaks.
     */
    'slug_overrides' => [
        25 => 'mission-vision',   // legacy alias was "mission-vission" (typo)
    ],
];
