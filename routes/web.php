<?php

use App\Http\Controllers\FormController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');

Route::get('/courses', [PageController::class, 'courses'])->name('course.index');
Route::get('/courses/{course}', [PageController::class, 'course'])->name('course.show');

Route::get('/news', [PageController::class, 'news'])->name('news.index');
Route::get('/news/{post}', [PageController::class, 'post'])->name('post.show');

Route::post('/forms/{form}', [FormController::class, 'submit'])
    ->middleware('throttle:20,1')
    ->name('form.submit');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

/*
 * Catch-all page route, registered last so it never shadows a real route.
 * `pages` slugs are unique, and the constraint keeps it off /admin, /storage
 * and anything containing a dot (asset requests).
 */
Route::get('/{page}', [PageController::class, 'show'])
    ->where('page', '[a-z0-9][a-z0-9\-]*')
    ->name('page.show');
