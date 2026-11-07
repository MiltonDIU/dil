<?php

use App\Models\Menu;
use App\Models\MenuItem;

return [
    'table' => 'menu',
    'source' => 'a0cbr_menu',
    'key' => 'id',
    'model' => MenuItem::class,

    'menu_table' => 'menu_types',
    'menu_source' => 'a0cbr_menu_types',
    'menu_model' => Menu::class,

    /*
     | client_id 0 = front-end. id 1 is the nested-set root sentinel.
     */
    'where' => [
        ['client_id', '=', 0],
        ['id', '>', 1],
    ],

    'map' => [
        'title' => 'title',
        'published' => 'published',
        'parent_id' => 'legacy_parent_id',
        'link' => 'legacy_link',
        'path' => 'legacy_path',
        'browserNav' => 'target',
    ],

    'casts' => [
        'published' => 'state',
        'target' => 'browser_nav',
    ],

    'defaults' => [],

    'root_legacy_id' => 1,

    /*
     | Joomla component links → our routes.
     |
     | `article` resolves against pages, courses and posts in that order, since
     | one legacy table became three native ones.
     */
    'link_patterns' => [
        '~option=com_content&view=article&id=(\d+)~' => 'article',
        '~option=com_content&view=category&id=(\d+)~' => 'category',
        '~option=com_sppagebuilder.*?id=(\d+)~' => 'sppagebuilder',
        '~option=com_users&view=login~' => 'login',
    ],

    /*
     | The homepage is SP Page Builder page id 1.
     */
    'home_sppagebuilder_id' => 1,

    /*
     | Legacy items whose link is '#': dropdown parents with no destination.
     | They import as type 'none' and render as non-clickable parents rather
     | than as dead href="#" links.
     */
    'placeholder_link' => '#',

    /*
     | Category views map to a named route rather than a record.
     */
    'category_routes' => [
        8 => 'news.index',
    ],
];
