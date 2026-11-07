@php
    $children = $item->children->filter(fn ($c) => $c->published);
    $url = $item->resolvedUrl();
@endphp

{{--
    One row of the mobile nav, recursive so it renders however many levels a
    menu actually has (the admin UI caps items at 3 levels deep). Every
    submenu starts closed — expanding them all at once turns the menu into a
    long scroll, and buries the items a visitor is looking for.
--}}
<li @if ($children->isNotEmpty()) x-data="{ open: false }" @endif>
    @if ($children->isNotEmpty())
        <div class="flex items-center gap-1">
            @if ($url)
                {{-- The parent is still reachable: tapping the label follows
                     it, tapping the chevron opens the submenu. --}}
                <a href="{{ $url }}" target="{{ $item->target }}"
                   class="flex-1 rounded-xl px-3 py-2.5 font-semibold text-sky-900 transition hover:bg-sky-50">{{ $item->title }}</a>
            @else
                <button type="button" @click="open = !open"
                        class="flex-1 rounded-xl px-3 py-2.5 text-left font-semibold text-sky-900 transition hover:bg-sky-50"
                        :aria-expanded="open.toString()" aria-controls="submenu-{{ $item->id }}">
                    {{ $item->title }}
                </button>
            @endif

            <button type="button" @click="open = !open"
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-sky-50 hover:text-sky-700"
                    :aria-expanded="open.toString()" aria-controls="submenu-{{ $item->id }}"
                    :aria-label="(open ? 'Hide' : 'Show') + ' {{ $item->title }} submenu'">
                <svg class="h-4 w-4 transition-transform duration-200" fill="none" stroke="currentColor"
                     stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true" :class="open && 'rotate-180'">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
        </div>

        <ul id="submenu-{{ $item->id }}" x-show="open" x-cloak x-collapse
            class="ml-3 space-y-1 border-l border-slate-200 pl-3">
            @foreach ($children as $child)
                @include('partials.mobile-menu-item', ['item' => $child, 'depth' => ($depth ?? 0) + 1])
            @endforeach
        </ul>
    @elseif ($url)
        <a href="{{ $url }}" target="{{ $item->target }}"
           class="block rounded-xl px-3 py-2.5 {{ ($depth ?? 0) > 0 ? 'text-sm text-slate-600' : 'font-semibold text-sky-900' }} transition hover:bg-sky-50 hover:text-sky-700">{{ $item->title }}</a>
    @else
        {{-- A legacy `type = 'none'` item with nowhere to go and nothing
             under it. Kept at the same weight as its siblings and merely
             muted: styled as a small caps heading it read as a label for the
             item below it. --}}
        <span class="block px-3 py-2.5 font-semibold text-slate-400">{{ $item->title }}</span>
    @endif
</li>
