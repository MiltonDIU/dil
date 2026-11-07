<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = [['loc' => route('home'), 'lastmod' => null, 'priority' => '1.0']];

        $urls[] = ['loc' => route('course.index'), 'lastmod' => null, 'priority' => '0.9'];

        foreach (Course::published()->get() as $course) {
            $urls[] = [
                'loc' => route('course.show', $course),
                'lastmod' => $course->updated_at?->toAtomString(),
                'priority' => '0.8',
            ];
        }

        foreach (Page::published()->get() as $page) {
            $urls[] = [
                'loc' => route('page.show', $page),
                'lastmod' => $page->updated_at?->toAtomString(),
                'priority' => '0.7',
            ];
        }

        if (Post::published()->exists()) {
            $urls[] = ['loc' => route('news.index'), 'lastmod' => null, 'priority' => '0.6'];

            foreach (Post::published()->get() as $post) {
                $urls[] = [
                    'loc' => route('post.show', $post),
                    'lastmod' => $post->updated_at?->toAtomString(),
                    'priority' => '0.6',
                ];
            }
        }

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }
}
