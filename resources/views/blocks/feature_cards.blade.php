@php($cards = $block->data['cards'] ?? [])

@if ($cards)
    {{--
        The column count is picked from a literal map, not interpolated into
        the class name. Tailwind only ships classes it can see as text, and
        `lg:grid-cols-{{ $n }}` compiles to a PHP echo it can never match — so
        the four-card row here had silently been collapsing to two.
    --}}
    @php($columns = [
        1 => 'lg:grid-cols-1',
        2 => 'lg:grid-cols-2',
        3 => 'lg:grid-cols-3',
        4 => 'lg:grid-cols-4',
    ][min(count($cards), 4)])

    <section class="bg-slate-50">
        <div class="section">
            @if ($block->title)
                <div class="mx-auto mb-12 max-w-2xl text-center">
                    <h2 class="text-3xl font-extrabold leading-tight sm:text-4xl">{{ $block->title }}</h2>
                    @if ($block->subtitle)
                        <p class="mt-4 text-lg text-slate-500">{{ $block->subtitle }}</p>
                    @endif
                </div>
            @endif

            <div class="grid gap-6 sm:grid-cols-2 {{ $columns }}">
                @foreach ($cards as $card)
                    <div class="card group flex flex-col p-7">
                        @if (!empty($card['icon']))
                            <span class="mb-5 inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-50
                                         text-sky-600 transition-colors duration-300 group-hover:bg-sky-600 group-hover:text-white">
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                    <path d="M10 1l2.4 5.3L18 7l-4 3.9.9 5.6L10 14l-4.9 2.5.9-5.6L2 7l5.6-.7z"/>
                                </svg>
                            </span>
                        @endif

                        @if (!empty($card['title']))
                            <h3 class="mb-3 text-lg font-bold">{{ $card['title'] }}</h3>
                        @endif

                        @if (!empty($card['body']))
                            <div class="content-html flex-1 text-sm leading-relaxed text-slate-500">{!! $card['body'] !!}</div>
                        @endif

                        {{-- Three of the four legacy buttons had an empty URL,
                             so the button only renders when it goes somewhere. --}}
                        @if (!empty($card['link_url']) && !empty($card['link_text']))
                            <a href="{{ $card['link_url'] }}" class="link-arrow mt-6">{{ $card['link_text'] }}</a>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
