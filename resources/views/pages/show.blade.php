@extends('layouts.app')

@section('title', $page->metaTitle().' — '.$site->shortName())
@section('meta_description', $page->meta_description ?: $site->get('meta_description'))
@section('og_image', $page->og_image_url ?? '')

@section('content')
    @include('partials.page-header', ['title' => $page->title, 'lead' => $page->excerpt])

    <article class="mx-auto max-w-4xl px-4 py-14 sm:px-6 lg:py-20">
        @if ($page->featured_image_url)
            <img src="{{ $page->featured_image_url }}" alt="{{ $page->featured_image_alt }}"
                 class="mb-10 w-full rounded-2xl object-cover">
        @endif

        @if ($page->isEmpty())
            {{--
                Five legacy articles were published with an empty body. Rather
                than showing a blank page, we say so plainly — and the admin
                flags them under Pages -> "Needs content".
            --}}
            <p class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
                This page has no content yet. Please check back soon.
            </p>
        @else
            <div class="content-html">{!! $page->body !!}</div>
        @endif

        @if ($page->slug === $site->get('contact_form_slug', 'contact'))
            @include('partials.contact-details')
            @include('partials.form', ['slug' => $site->get('contact_form_slug', 'contact')])
        @endif
    </article>
@endsection
