<?php

use App\Models\Block;

return [
    'table' => 'sppagebuilder',
    'source' => 'a0cbr_sppagebuilder',
    'key' => 'id',
    'model' => Block::class,

    // Page id 1 is the homepage. Page id 2 is an unpublished, empty module.
    'page_id' => 1,
    'group' => 'home',

    /*
     | SP Page Builder 5 stores its layout in `content` (not `text`, which is
     | the pre-5 column and is empty here) as:
     |     [ row → columns[] → addons[] { name, settings } ]
     |
     | The homepage uses only seven addon types, which is regular enough for a
     | deterministic mapper — no AI is involved in building the homepage.
     | Each block keeps its originating SPPB JSON in `raw_layout` forever.
     */
    'addon_map' => [
        'carousel' => 'carousel',
        'text_block' => 'rich_text',
        'button' => null,        // folded into the preceding rich_text block
        'icon' => null,          // folded into the feature card it belongs to
        'empty_space' => null,   // pure spacing, carries no content
        'video' => 'video',
        'testimonial' => null,   // JoomShaper demo data ("John Doe") — dropped
    ],

    /*
     | Rows whose columns each contain icon + text_block + text_block + button
     | collapse into a single `feature_cards` block instead of 20 loose blocks.
     */
    'feature_card_layouts' => ['3+3+3+3', '4+4+4', '6+6'],

    /*
     | A text_block whose body is a bare <ul> of links becomes a link_list, and
     | one whose body is a single <iframe> becomes a map embed.
     */
    'detect' => [
        'link_list' => '~^\s*<ul>.*</ul>\s*$~is',
        'map' => '~^\s*<iframe[^>]+(google\.com/maps|openstreetmap)~i',
    ],
];
