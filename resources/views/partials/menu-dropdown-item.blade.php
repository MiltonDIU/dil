@php
    $url = $item->resolvedUrl();
    $children = $item->children->filter(fn ($c) => $c->published);
@endphp

{{--
    One row inside a dropdown. When it has its own children it opens a flyout
    to the side on hover/focus, the same interaction pattern as the top-level
    dropdown itself — so this partial can recurse for as many levels as a menu
    is ever nested, though the admin UI caps items at 3 levels deep.
--}}
<li class="relative"
    @if ($children->isNotEmpty())
        {{--
            `openLeft` is decided the moment the flyout opens, by checking
            whether it would run past the right edge of the viewport — rows
            near the end of the nav flip to open leftward instead of
            overflowing off-screen.
        --}}
        x-data="{ open: false, openLeft: false }"
        @mouseenter="open = true; $nextTick(() => openLeft = $refs.flyout?.getBoundingClientRect().right > window.innerWidth)"
        @mouseleave="open = false"
        @focusin="open = true; $nextTick(() => openLeft = $refs.flyout?.getBoundingClientRect().right > window.innerWidth)"
        @focusout="open = false"
        @keydown.escape.stop="open = false"
    @endif>
    <div class="group/sub flex items-center gap-1">
        @if ($url)
            <a href="{{ $url }}" target="{{ $item->target }}"
               @if ($children->isNotEmpty()) x-ref="trigger" :aria-expanded="open.toString()" @endif
               @if ($item->isExternal()) rel="noopener noreferrer" @endif
               class="flex flex-1 items-center justify-between gap-3 rounded-xl px-3 py-2.5 text-sm font-medium
                      text-slate-600 transition hover:bg-sky-50 hover:text-sky-700">
                {{ $item->title }}
                @if ($children->isEmpty())
                    <span aria-hidden="true"
                          class="text-sky-400 opacity-0 transition-all duration-200 group-hover/sub:translate-x-0.5 group-hover/sub:opacity-100">&rarr;</span>
                @endif
            </a>
        @else
            {{-- A dropdown heading with no destination of its own. --}}
            <span class="flex-1 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-400">{{ $item->title }}</span>
        @endif

        @if ($children->isNotEmpty())
            <svg class="mr-2 h-3 w-3 shrink-0 -rotate-90 text-slate-400" fill="none" stroke="currentColor"
                 stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        @endif
    </div>

    @if ($children->isNotEmpty())
        <ul x-ref="flyout" x-show="open" x-cloak
            :class="openLeft ? 'right-full mr-1' : 'left-full ml-1'"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-end="opacity-0"
            class="absolute top-0 z-50 min-w-56 max-w-[min(20rem,90vw)] rounded-2xl border border-slate-200 bg-white/95 p-2
                   shadow-2xl shadow-sky-900/10 backdrop-blur">
            @foreach ($children as $child)
                @include('partials.menu-dropdown-item', ['item' => $child])
            @endforeach
        </ul>
    @endif
</li>
