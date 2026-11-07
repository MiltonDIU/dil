@php($items = $block->menuItems())

@if ($items->isNotEmpty())
    <section class="bg-white">
        <div class="section">
            <div class="mx-auto mb-10 max-w-2xl text-center">
                @if ($block->title)
                    <h2 class="text-3xl font-extrabold leading-tight sm:text-4xl">{{ $block->title }}</h2>
                @endif
                @if ($block->subtitle)
                    <p class="mt-4 text-lg text-slate-500">{{ $block->subtitle }}</p>
                @endif
            </div>

            <ul class="grid grid-cols-2 gap-4 md:grid-cols-4">
                @foreach ($items as $item)
                    @php($url = $item->resolvedUrl())

                    {{-- Chinese, Korean, French and Italian are dropdown parents
                         stored with url "#", so they go to that language's
                         section on /courses rather than a dead link. --}}
                    @if (blank($url) || $url === '#')
                        @php($language = $item->children->first()?->linkable?->language)
                        @php($url = route('course.index').($language ? '#lang-'.Str::slug($language) : ''))
                    @endif

                    <li>
                        <a href="{{ $url }}"
                           class="card flex h-full items-center justify-between gap-3 px-5 py-5 font-bold text-sky-900">
                            {{ trim($item->title) }}
                            <span aria-hidden="true" class="text-sky-600">&rarr;</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif
