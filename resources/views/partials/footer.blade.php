{{-- The background stays settings-driven; only the layout inside it changed. --}}
<footer class="relative isolate mt-auto overflow-hidden text-sky-100" style="background-color: var(--brand-footer-bg)">
    <div aria-hidden="true"
         class="pointer-events-none absolute -left-40 -top-40 h-[30rem] w-[30rem] rounded-full bg-sky-600/20 blur-3xl"></div>

    <div class="relative mx-auto grid max-w-7xl gap-12 px-4 py-16 sm:px-6 md:grid-cols-2 lg:grid-cols-12 lg:gap-10">

        <div class="lg:col-span-5">
            @if ($logo = $site->url('logo'))
                <img src="{{ $logo }}" alt="{{ $site->name() }}" class="mb-5 h-14 w-auto rounded-xl bg-white p-2">
            @endif
            <p class="text-xl font-bold text-white">{{ $site->name() }}</p>
            @if ($parent = $site->get('parent_org'))
                <p class="mt-1.5 text-sm font-medium text-emerald-300">{{ $parent }}</p>
            @endif
            @if ($tagline = $site->get('tagline'))
                <p class="mt-4 max-w-sm text-sm leading-relaxed text-sky-200/80">{{ $tagline }}</p>
            @endif
        </div>

        <div class="lg:col-span-4">
            <h2 class="mb-5 text-xs font-bold uppercase tracking-[0.16em] text-white">Contact</h2>
            <address class="space-y-3 text-sm not-italic text-sky-200/80">
                @if ($address = $site->get('contact_address'))
                    <p class="whitespace-pre-line leading-relaxed">{{ $address }}</p>
                @endif
                @if ($phone = $site->get('contact_phone'))
                    <p><a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="transition-colors hover:text-white">{{ $phone }}</a></p>
                @endif
                @if ($email = $site->get('contact_email'))
                    <p><a href="mailto:{{ $email }}" class="break-words transition-colors hover:text-white">{{ $email }}</a></p>
                @endif
            </address>
        </div>

        <div class="lg:col-span-3">
            <h2 class="mb-5 text-xs font-bold uppercase tracking-[0.16em] text-white">Explore</h2>
            <ul class="space-y-3 text-sm text-sky-200/80">
                @foreach ($site->menu()->take(6) as $item)
                    @if ($url = $item->resolvedUrl())
                        <li>
                            <a href="{{ $url }}" class="group inline-flex items-center gap-2 transition-colors hover:text-white">
                                <span aria-hidden="true"
                                      class="h-px w-0 bg-emerald-400 transition-all duration-200 group-hover:w-3"></span>
                                {{ $item->title }}
                            </a>
                        </li>
                    @endif
                @endforeach
            </ul>

            @if ($socials = $site->socials())
                <div class="mt-7 flex flex-wrap gap-2">
                    @foreach ($socials as $network => $url)
                        <a href="{{ $url }}" target="_blank" rel="noopener noreferrer"
                           class="rounded-full border border-white/20 px-3.5 py-1.5 text-xs font-semibold capitalize
                                  text-sky-100 transition hover:border-white/60 hover:bg-white/10">{{ $network }}</a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="relative border-t border-white/10">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 px-4 py-6 text-sm text-sky-200/70 sm:flex-row sm:px-6">
            <p>{{ $site->copyright() }}</p>
            <a href="{{ route('sitemap') }}" class="transition-colors hover:text-white">Sitemap</a>
        </div>
    </div>
</footer>
