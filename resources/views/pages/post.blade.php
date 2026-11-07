@extends('layouts.app')

@section('title', $post->metaTitle().' — '.$site->shortName())
@section('meta_description', $post->meta_description ?: $site->get('meta_description'))
@section('og_image', $post->og_image_url ?? '')

@section('content')
    @include('partials.page-header', [
        'title' => $post->title,
        'crumbs' => ['News' => route('news.index')],
    ])

    <article class="mx-auto max-w-3xl px-4 py-14 sm:px-6 lg:py-20">
        @if ($post->published_at)
            <time datetime="{{ $post->published_at->toDateString() }}" class="text-xs font-bold uppercase tracking-wider text-sky-600">
                {{ $post->published_at->format('j F Y') }}
            </time>
        @endif

        @if ($post->featured_image_url)
            <img src="{{ $post->featured_image_url }}" alt="{{ $post->featured_image_alt }}"
                 class="my-8 w-full rounded-2xl object-cover">
        @endif

        <div class="content-html mt-6">{!! $post->body !!}</div>
    </article>
@endsection
