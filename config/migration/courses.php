<?php

use App\Models\Course;

return [
    'table' => 'content',
    'source' => 'a0cbr_content',
    'key' => 'id',
    'model' => Course::class,

    /*
     | The twelve articles that are language / skill courses.
     |
     | Joomla stored these as ordinary com_content articles, so there is no
     | column that identifies them — the distinction lives in the menu
     | structure ("Courses" dropdown) and in the body copy, which every one of
     | these follows: "Course Structure & Fees: Course Duration … Fees: Tk. …".
     |
     | Listing them explicitly keeps the import deterministic and reviewable,
     | and an editor can create further courses in Filament afterwards.
     */
    'legacy_ids' => [
        13, // IELTS Courses
        14, // Japanese Language          (empty body in legacy)
        15, // Chinese Language
        16, // Korean Language
        17, // French Language
        18, // Italian Language
        19, // Bangla Language            (empty body in legacy)
        20, // Remedial English Course    (empty body in legacy)
        21, // Arabic Language Course
        22, // Various Short English Courses (empty body in legacy)
        26, // German Language Course
        27, // Empowering Writing & Speaking
    ],

    'map' => [
        'title' => 'title',
        'alias' => 'slug',
        'introtext' => 'body',
        'state' => 'published',
        'publish_up' => 'published_at',
        'hits' => 'hits',
        'metadesc' => 'meta_description',
    ],

    'casts' => [
        'published' => 'state',
        'published_at' => 'datetime',
        'body' => 'html',
    ],

    'defaults' => [
        'currency' => 'BDT',
    ],

    /*
     | Deterministic language guesses from the title. The AI pass refines these
     | and fills the numeric fields, but the site is fully usable without it.
     */
    'language_hints' => [
        'german' => 'German',
        'japanese' => 'Japanese',
        'chinese' => 'Chinese',
        'korean' => 'Korean',
        'french' => 'French',
        'italian' => 'Italian',
        'bangla' => 'Bangla',
        'arabic' => 'Arabic',
        'ielts' => 'English',
        'english' => 'English',
        'writing' => 'English',
    ],
];
