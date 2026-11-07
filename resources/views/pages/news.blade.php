@extends('layouts.app')

@section('title', 'News — '.$site->shortName())

@section('content')
    @include('partials.page-header', ['title' => 'News'])

    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:py-20">
        @if ($posts->isEmpty())
            <p class="text-slate-500">There are no news articles yet.</p>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    {{-- `relative` is what the stretched anchor below anchors to. --}}
                    <article class="card group relative flex flex-col overflow-hidden">
                        @if ($post->featured_image_url)
                            <div class="aspect-[16/10] overflow-hidden bg-slate-50">
                                <img src="{{ $post->featured_image_url }}" alt="{{ $post->featured_image_alt }}"
                                     class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                     loading="lazy" decoding="async">
                            </div>
                        @endif

                        <div class="flex flex-1 flex-col p-6">
                            @if ($post->published_at)
                                <time datetime="{{ $post->published_at->toDateString() }}"
                                      class="text-xs font-bold uppercase tracking-wider text-sky-600">
                                    {{ $post->published_at->format('j F Y') }}
                                </time>
                            @endif

                            <h2 class="mt-2 text-lg font-bold">
                                {{-- The whole card is the hit area; the anchor
                                     is stretched rather than duplicated, so
                                     there is still exactly one link per post. --}}
                                <a href="{{ route('post.show', $post) }}"
                                   class="after:absolute after:inset-0 transition-colors group-hover:text-sky-600">{{ $post->title }}</a>
                            </h2>

                            @if ($post->excerpt)
                                <p class="mt-3 text-sm leading-relaxed text-slate-500">{{ Str::limit($post->excerpt, 130) }}</p>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-12">{{ $posts->links() }}</div>
        @endif
    </div>
@endsection
