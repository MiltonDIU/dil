<?php

/*
 * One-time seed for the German-programme homepage release (2026-11-07).
 * Upload the new files under storage/app/public/media first, then from the app root:
 *
 *     php artisan tinker --execute="require 'deploy/seed-german-spotlight.php';"
 *
 * Idempotent: blocks and the slide that already exist are left untouched, and a
 * course image is only replaced when it is empty or one of the Russian-course
 * photos being withdrawn. Deliberately NOT a legacy:* command.
 */

use App\Models\Block;
use App\Models\Course;
use App\Models\Slide;

$withdrawn = [
    'media/courses/01M6V54RCWA9GKV1HS3JBW0E61.png',
    'media/courses/01M6V5HC8C6BDSE6ET55HGJYT5.jpg',
    'media/courses/01M6V68G81WX4T0JQG12B2BH2Z.jpeg',
    'media/courses/01M6V6P7T1B6K9E6NC3HCZB8KP.jpeg',
    // General campus photos, used only until the seminar photos arrived.
    'media/courses/diu-language-lab-booths.webp',
    'media/courses/diu-language-lab.webp',
    'media/courses/diu-seminar.jpg',
    'media/courses/diu-classroom.webp',
    'media/courses/diu-computer-lab.jpg',
];

$courseImages = [
    'german-language-course' => ['media/courses/german-seminar-presentation.jpg', 'A speaker presenting CEFR language levels at a German language seminar at Daffodil International University'],
    'gl-course-a1' => ['media/courses/german-seminar-audience-1.jpg', 'The audience at a German language seminar in Kobi Nazrul Eduplex'],
    'gl-course-a2' => ['media/courses/german-seminar-audience-2.jpg', 'Students listening at a German language seminar'],
    'gl-course-b1' => ['media/courses/german-seminar-audience-3.jpg', 'Students seated at a German language seminar in Kobi Nazrul Eduplex'],
    'gl-course-b2' => ['media/courses/german-seminar-welcome.jpg', 'Speakers beside the seminar poster at a German language seminar'],
];

$ids = [];

foreach ($courseImages as $slug => [$path, $alt]) {
    $course = Course::where('slug', $slug)->first();
    if (! $course) {
        continue;
    }

    $ids[$slug] = (string) $course->id;
    $course->featured = true;

    if (blank($course->featured_image) || in_array($course->featured_image, $withdrawn, true)) {
        $course->featured_image = $path;
        $course->featured_image_alt = $alt;
    }

    $course->save();
}

// The overview sorts ahead of its four levels on /courses.
Course::where('slug', 'german-language-course')->update(['ordering' => 0]);

// Imported under "English"; the course teaches Bangla to foreign students.
Course::where('slug', 'bangla-language-course')->where('language', 'English')->update(['language' => 'Bangla']);

Block::firstOrCreate(['group' => 'home', 'type' => 'course_spotlight'], [
    'title' => 'Learn the German Language at DILC',
    'subtitle' => 'German Language Programme',
    'content' => '<p>Germany is home to world-class universities and leading industries. Build your German language skills step by step through four levels following the Common European Framework of Reference for Languages (CEFR).</p>',
    'image' => 'media/courses/german-seminar-presentation.jpg',
    'image_alt' => 'A speaker presenting at a German language seminar at Daffodil International University',
    'link_text' => 'Explore the German Language Programme',
    'link_url' => '/courses/german-language-course',
    'data' => ['course_ids' => array_values(array_filter([
        $ids['gl-course-a1'] ?? null, $ids['gl-course-a2'] ?? null, $ids['gl-course-b1'] ?? null, $ids['gl-course-b2'] ?? null,
    ]))],
    'ordering' => 13,
    'published' => true,
]);

Block::firstOrCreate(['group' => 'home', 'type' => 'language_grid'], [
    'title' => 'Our Courses',
    'ordering' => 16,
    'published' => true,
]);

Block::firstOrCreate(['group' => 'home', 'type' => 'gallery'], [
    'title' => 'Our campus and facilities',
    'subtitle' => 'DILC is part of Daffodil International University.',
    'data' => ['images' => [
        ['image' => 'media/courses/diu-language-lab-booths.webp', 'caption' => 'Language lab', 'alt' => 'Headphone booths in a language lab'],
        ['image' => 'media/courses/diu-computer-lab.jpg', 'caption' => 'Computer lab', 'alt' => 'Students working in a computer lab'],
        ['image' => 'media/courses/diu-classroom.webp', 'caption' => 'Classroom', 'alt' => 'Students seated in a classroom'],
        ['image' => 'media/gallery/diu-conference-hall.jpg', 'caption' => 'Conference hall', 'alt' => 'Rows of green seats in a conference hall'],
        ['image' => 'media/gallery/diu-innovation-lab.jpg', 'caption' => 'Innovation Lab', 'alt' => 'The Innovation Lab building on campus'],
        ['image' => 'media/gallery/diu-campus-aerial.jpg', 'caption' => 'Daffodil Smart City', 'alt' => 'Aerial view of the Daffodil Smart City campus'],
    ]],
    'ordering' => 25,
    'published' => true,
]);

$slide = Slide::firstOrCreate(['group' => 'home', 'image' => 'media/slides/diu-campus-german.jpg'], [
    'title' => 'Learn the German Language at Daffodil International University',
    'subtitle' => 'German Language Programme',
    'caption' => 'Four levels from A1 to B2, following the CEFR.',
    'image_alt' => 'The Daffodil International University campus',
    'width' => 1920,
    'height' => 800,
    'link_text' => 'Explore German Language Courses',
    'link_url' => '/courses/german-language-course',
    'target' => '_self',
    'ordering' => 0,
    'published' => true,
]);

if ($slide->wasRecentlyCreated) {
    // Ordering is unsigned, so the new slide takes 0 and any other at 0 moves back one step.
    Slide::where('group', 'home')->where('id', '!=', $slide->id)->where('ordering', 0)->update(['ordering' => 5]);
}

// Databases seeded before the seminar photos arrived: swap only the values this
// script itself wrote, so anything an editor has since changed is left alone.
Block::where('group', 'home')->where('type', 'course_spotlight')
    ->where('image', 'media/gallery/diu-students-studying.webp')
    ->update([
        'image' => 'media/courses/german-seminar-presentation.jpg',
        'image_alt' => 'A speaker presenting at a German language seminar at Daffodil International University',
    ]);

// Same rule for the first wording: a value is only replaced if it is still exactly
// what this script wrote, so an editor's own wording survives a re-run.
$rewordings = [
    [Block::where('group', 'home')->where('type', 'course_spotlight'), 'title', 'Learn German at DILC', 'Learn the German Language at DILC'],
    [Block::where('group', 'home')->where('type', 'course_spotlight'), 'link_text', 'Explore the German programme', 'Explore the German Language Programme'],
    [Block::where('group', 'home')->where('type', 'course_spotlight'), 'content',
        '<p>Germany is home to world-class universities and leading industries. Build your German step by step through four levels following the Common European Framework of Reference for Languages (CEFR).</p>',
        '<p>Germany is home to world-class universities and leading industries. Build your German language skills step by step through four levels following the Common European Framework of Reference for Languages (CEFR).</p>'],
    [Block::where('group', 'home')->where('type', 'language_grid'), 'title', 'Languages we teach', 'Our Courses'],
    [Block::where('group', 'home')->where('type', 'language_grid'), 'subtitle',
        'Alongside German, DILC offers courses in a wide range of languages, including IELTS preparation.', null],
    [Block::where('group', 'home')->where('type', 'language_grid'), 'subtitle',
        'Along with the German language, DILC teaches many other languages and offers IELTS preparation.', null],
    [Slide::where('image', 'media/slides/diu-campus-german.jpg'), 'title', 'Learn German at Daffodil International University', 'Learn the German Language at Daffodil International University'],
    [Slide::where('image', 'media/slides/diu-campus-german.jpg'), 'link_text', 'Explore German courses', 'Explore German Language Courses'],
];

foreach ($rewordings as [$query, $column, $old, $new]) {
    $query->where($column, $old)->update([$column => $new]);
}

$gallery = Block::where('group', 'home')->where('type', 'gallery')->first();
if ($gallery) {
    $data = $gallery->data;
    foreach ($data['images'] ?? [] as $i => $image) {
        if (($image['image'] ?? null) === 'media/gallery/diu-meeting-room.webp') {
            $data['images'][$i] = ['image' => 'media/gallery/diu-conference-hall.jpg', 'caption' => 'Conference hall', 'alt' => 'Rows of green seats in a conference hall'];
        }
    }
    $gallery->update(['data' => $data]);
}

$overview = Course::where('slug', 'german-language-course')->first();
if ($overview && ! str_contains((string) $overview->curriculum_html, 'German Language and Study Abroad Seminar')) {
    $overview->update(['curriculum_html' => $overview->curriculum_html
        .'<h2><strong>German Language and Study Abroad Seminar</strong></h2>'
        .'<p>DILC held an exclusive seminar on German language and study abroad on 20 May 2026 at Kobi Nazrul Eduplex, DIU Library, with German Language Instructor Abdul Ahad.</p>'
        .'<p><img src="/storage/media/courses/german-seminar-welcome.jpg" alt="Speakers beside the seminar poster at the German language seminar"></p>'
        .'<p><img src="/storage/media/courses/german-seminar-audience-1.jpg" alt="The audience at the German language seminar in Kobi Nazrul Eduplex"></p>',
    ]);
}

echo "German spotlight seeded.\n";
