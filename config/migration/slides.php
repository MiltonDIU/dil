<?php

use App\Models\Slide;

return [
    'source' => 'a0cbr_sppagebuilder.carousel',
    'model' => Slide::class,
    'group' => 'home',

    /*
     | Slides come from the SP Page Builder `carousel` addon on page 1
     | (settings.sp_carousel_item[]), not from a table of their own.
     |
     | The DJ-ImageSlider extension is installed and enabled but has ZERO rows,
     | its module has no template position, and its configured image folder
     | (images/home/publication) does not exist. It is dead and is not migrated.
     */
    'page_id' => 1,
    'addon' => 'carousel',
    'items_key' => 'sp_carousel_item',

    'map' => [
        'bg.src' => 'image',
        'bg.alt' => 'image_alt',
        'bg.width' => 'width',
        'bg.height' => 'height',
        'title' => 'title',
        'content' => 'caption',
        'button_text' => 'link_text',
        'button_url.url' => 'link_url',
    ],
];
