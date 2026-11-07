<?php

/*
|--------------------------------------------------------------------------
| Legacy migration — shared configuration
|--------------------------------------------------------------------------
|
| Per-entity mappings live in config/migration/*.php. This file holds what
| every one of them needs. A new project should be able to change config
| rather than classes.
|
*/

return [

    'connection' => 'legacy',

    /*
    | The legacy connection already applies the a0cbr_ prefix, so mappings
    | reference bare table names ("content"). `source` is the fully-qualified
    | name recorded in each row's `legacy_source` column.
    */
    'prefix' => env('LEGACY_DB_PREFIX', 'a0cbr_'),

    'site_path' => env('LEGACY_SITE_PATH'),

    /*
    | Where migrated media lands on the `public` disk, and how legacy relative
    | paths inside stored HTML are rewritten to absolute /storage/... URLs.
    */
    'media' => [
        'root' => 'media',
        'copy_dirs' => ['images'],   // NOT `media/` — that is 37 MB of Joomla core JS/CSS
        'skip_dirs' => [
            'images/home/download',  // 18 Department of Student Affairs leftovers
            'images/home/_spmedia_thumbs',
            'images/home/slider/_spmedia_thumbs',
        ],
        'skip_files' => [
            'images/logo-dsa.png',
            'images/joomla_black.png',
            'images/powered_by.png',
            'index.html',
        ],
        'html_path_map' => [
            'images' => '/storage/media/images',
        ],
    ],

    /*
    | Joomla `state` column → our boolean `published`.
    | 1 = published, 0 = unpublished, -2 = trashed, 2 = archived.
    | Per the approved plan, trashed content is imported as unpublished so it
    | stays recoverable in Filament rather than being silently dropped.
    */
    'state_map' => [
        1 => true,
        0 => false,
        2 => false,
        -2 => false,
    ],

];
