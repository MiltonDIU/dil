{{-- Top bar: contact details, all from `settings`. --}}
@php($phone = $site->get('contact_phone'))
@php($email = $site->get('contact_email'))

@if ($phone || $email || $site->socials())
    <div class="hidden bg-sky-900 text-sky-100 md:block">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-2.5 text-xs sm:px-6">
            <div class="flex items-center gap-6">
                @if ($phone)
                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}"
                       class="inline-flex items-center gap-2 transition-colors hover:text-white">
                        <svg class="h-3.5 w-3.5 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293a1.125 1.125 0 01-1.21.38 12.035 12.035 0 01-7.143-7.143 1.125 1.125 0 01.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                        </svg>
                        {{ $phone }}
                    </a>
                @endif
                @if ($email)
                    <a href="mailto:{{ $email }}"
                       class="inline-flex items-center gap-2 transition-colors hover:text-white">
                        <svg class="h-3.5 w-3.5 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                        </svg>
                        {{ $email }}
                    </a>
                @endif
            </div>

            {{-- Only links that actually exist are rendered: the legacy site
                 stored all five socials as "#" placeholders. --}}
            @if ($socials = $site->socials())
                <div class="flex items-center gap-5">
                    @foreach ($socials as $network => $url)
                        <a href="{{ $url }}" target="_blank" rel="noopener noreferrer"
                           class="capitalize transition-colors hover:text-white">{{ $network }}</a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endif

{{--
    `scrolled` only ever adds shadow and tightens the bar — the header is fully
    usable before Alpine boots and if JavaScript never arrives.
--}}
<header x-data="{ open: false, scrolled: false }"
        @scroll.window="scrolled = window.scrollY > 8"
        :class="scrolled ? 'shadow-lg shadow-sky-900/5 backdrop-blur-xl' : ''"
        class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/90 backdrop-blur transition-shadow duration-300">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 transition-all duration-300 sm:px-6"
         :class="scrolled ? 'py-2' : 'py-3.5'">
        <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-3">
            @if ($logo = $site->url('logo'))
                <img src="{{ $logo }}" alt="{{ $site->name() }}" class="h-10 w-auto transition-all duration-300"
                     :class="scrolled ? 'h-8' : 'h-10'">
            @endif
            <span class="flex flex-col leading-none">
                <span class="text-lg font-extrabold tracking-tight text-sky-900">{{ $site->shortName() }}</span>
                @if ($parent = $site->get('parent_org'))
                    <span class="mt-1 hidden text-[0.625rem] font-semibold uppercase tracking-[0.14em] text-slate-400 sm:block">
                        {{ $parent }}
                    </span>
                @endif
            </span>
        </a>

        {{-- Navigation is built from `menu_items`, never hardcoded. --}}
        <nav class="hidden lg:block" aria-label="Main">
            <ul class="flex items-center gap-0.5">
                @foreach ($site->menu() as $item)
                    @include('partials.menu-item', ['item' => $item])
                @endforeach
            </ul>
        </nav>

        <div class="flex items-center gap-2">
            {{-- The destination is the settings-driven contact page, so this
                 follows an editor renaming it rather than pointing at a slug
                 baked into the template. --}}
            <a href="{{ route('page.show', $site->get('contact_form_slug', 'contact')) }}"
               class="btn-primary hidden px-5 py-2.5 lg:inline-flex">
                Enquire now
            </a>

            <button type="button" @click="open = !open"
                    class="flex h-11 w-11 items-center justify-center rounded-full text-sky-900 transition hover:bg-sky-50 lg:hidden"
                    :aria-expanded="open.toString()" aria-controls="mobile-nav" aria-label="Menu">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                    <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16"/>
                    <path x-show="open" x-cloak stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Mobile navigation, same data source. --}}
    <nav id="mobile-nav" x-show="open" x-cloak x-collapse
         class="max-h-[75vh] overflow-y-auto border-t border-slate-200 bg-white lg:hidden" aria-label="Main">
        {{--
            Every submenu starts closed. Expanding all of them at once turned
            the menu into a 30-item scroll — Courses alone carries ten children
            — and buried the top-level items the visitor was looking for.
            A parent with children is a disclosure button; a parent without
            them stays a plain link.
        --}}
        <ul class="mx-auto max-w-7xl space-y-1 px-4 py-4 sm:px-6">
            @foreach ($site->menu() as $item)
                @include('partials.mobile-menu-item', ['item' => $item, 'depth' => 0])
            @endforeach

            <li class="pt-3">
                <a href="{{ route('page.show', $site->get('contact_form_slug', 'contact')) }}" class="btn-primary w-full">Enquire now</a>
            </li>
        </ul>
    </nav>
</header>
