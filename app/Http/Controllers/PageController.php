<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\Course;
use App\Models\Page;
use App\Models\Post;
use App\Models\Slide;

class PageController extends Controller
{
    public function home()
    {
        return view('pages.home', [
            'blocks' => Block::published()->group('home')->orderBy('ordering')->get(),
            'slides' => Slide::published()->group('home')->orderBy('ordering')->get(),
        ]);
    }

    public function show(Page $page)
    {
        abort_unless($page->published, 404);

        return view('pages.show', ['page' => $page]);
    }

    public function courses()
    {
        return view('pages.courses', [
            'languages' => Course::byLanguage(),
        ]);
    }

    public function course(Course $course)
    {
        abort_unless($course->published, 404);

        return view('pages.course', [
            'course' => $course,
            'lessons' => $course->publishedLessons()->get(),
            'related' => Course::published()
                ->where('id', '!=', $course->id)
                ->orderBy('ordering')->limit(3)->get(),
        ]);
    }

    public function news()
    {
        return view('pages.news', [
            'posts' => Post::published()->latest('published_at')->paginate(12),
        ]);
    }

    public function post(Post $post)
    {
        abort_unless($post->published, 404);

        return view('pages.post', ['post' => $post]);
    }
}
