<?php

use App\Models\Category;

return [
    'table' => 'categories',
    'source' => 'a0cbr_categories',
    'key' => 'id',
    'model' => Category::class,

    /*
     | Only com_content categories carry content. The other five rows are empty
     | per-extension "Uncategorised" stubs (com_banners, com_contact,
     | com_newsfeeds, com_users) plus the nested-set ROOT.
     */
    'where' => [
        ['extension', '=', 'com_content'],
    ],

    'map' => [
        'title' => 'name',
        'alias' => 'slug',
        'description' => 'description',
        'published' => 'published',
        'parent_id' => 'legacy_parent_id',
        'metadesc' => 'meta_description',
    ],

    'casts' => [
        'published' => 'bool',
        'description' => 'html',
    ],

    'defaults' => [],

    /*
     | Joomla's ROOT category (id 1) is a nested-set sentinel, not a real
     | category — anything parented to it becomes a top-level category here.
     */
    'root_legacy_id' => 1,
];
