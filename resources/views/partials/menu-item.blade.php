@php
    $url = $item->resolvedUrl();
    $children = $item->children->filter(fn ($c) => $c->published);

    /*
     * Exact match, or a real path segment below it. The previous test was
     * `fullUrlIs(url($url).'*')`, and for the Home item that pattern is the
     * bare origin followed by a wildcard — so Home was marked current on every
     * page of the site.
     */
    $here = rtrim(url()->current(), '/');
    $target = $url ? rtrim(url($url), '/') : null;
    $root = rtrim(url('/'), '/');

    $current = $target !== null && $here === $target;
    $active = $current || ($target !== null && $target !== $root && str_starts_with($here, $target.'/'));
@endphp

{{--
    The dropdown opens on hover for a mouse and on focus for a keyboard, and
    Escape closes it. Hover alone — which is all this had — leaves the submenu
    unreachable without a pointing device.
--}}
<li class="relative"
    @if ($children->isNotEmpty())
        x-data="{ open: false }"
        @mouseenter="open = true" @mouseleave="open = false"
        @focusin="open = true" @focusout="open = false"
        @keydown.escape.stop="open = false; $refs.trigger.focus()"
    @endif>

    @if ($url)
        <a href="{{ $url }}" target="{{ $item->target }}"
           @if ($children->isNotEmpty()) x-ref="trigger" :aria-expanded="open.toString()" @endif
           @if ($item->isExternal()) rel="noopener noreferrer" @endif
           @if ($current) aria-current="page" @endif
           class="relative flex items-center gap-1.5 rounded-full px-3.5 py-2 text-sm font-semibold transition-colors
                  {{ $active ? 'text-sky-700' : 'text-slate-700 hover:text-sky-700' }}">
            {{ $item->title }}
            @if ($children->isNotEmpty())
                <svg class="h-3 w-3 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2.5"
                     viewBox="0 0 24 24" aria-hidden="true" :class="open && 'rotate-180'">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
            @endif

            {{-- The active marker is DIU's: a short rule under the label, not a fill. --}}
            <span aria-hidden="true"
                  class="absolute inset-x-3.5 -bottom-0.5 h-0.5 origin-left rounded-full bg-emerald-500 transition-transform duration-300
                         {{ $active ? 'scale-x-100' : 'scale-x-0' }}"></span>
        </a>
    @else
        {{--
            A dropdown heading with no destination. The legacy site rendered
            six of these as href="#", which look clickable and go nowhere;
            here they are a button when they have children, and inert text
            otherwise.
        --}}
        <button type="button"
                @if ($children->isNotEmpty())
                    x-ref="trigger" @click="open = !open" :aria-expanded="open.toString()"
                @endif
                class="flex items-center gap-1.5 rounded-full px-3.5 py-2 text-sm font-semibold text-slate-700 transition-colors hover:text-sky-700">
            {{ $item->title }}
            @if ($children->isNotEmpty())
                <svg class="h-3 w-3 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2.5"
                     viewBox="0 0 24 24" aria-hidden="true" :class="open && 'rotate-180'">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
            @endif
        </button>
    @endif

    @if ($children->isNotEmpty())
        <ul x-show="open" x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-end="opacity-0"
            class="absolute left-0 top-full z-50 mt-2 min-w-64 rounded-2xl border border-slate-200 bg-white/95 p-2
                   shadow-2xl shadow-sky-900/10 backdrop-blur">
            @foreach ($children as $child)
                @include('partials.menu-dropdown-item', ['item' => $child])
            @endforeach
        </ul>
    @endif
</li>
