@php($slides = ($slides ?? collect())->where('group', $block->data['group'] ?? 'home')->values())

@if ($slides->isNotEmpty())
    @php($count = $slides->count())
    @php($interval = (int) ($block->data['interval'] ?? 6500))

    {{--
        Movement is the browser's own scroll-snap, so swipe, trackpad and
        momentum behave natively; resources/js/carousel.js only adds autoplay,
        the buttons and the announcement. Controls sit in one bar at the foot of
        the hero — position and progress on the left, transport on the right —
        and every one of them is a real <button> at a 44px target. The version
        this replaces offered two 10px dots and a `setInterval` that could not
        be paused, reversed or swiped.
    --}}
    <section x-data="carousel({{ $count }}, {{ $interval }})"
             @mouseenter="hovered = true; sync()"
             @mouseleave="hovered = false; sync()"
             @focusin="focused = true; sync()"
             @focusout="focused = false; sync()"
             @keydown.left.prevent="prev()"
             @keydown.right.prevent="next()"
             @keydown.home.prevent="goto(0)"
             @keydown.end.prevent="goto(n - 1)"
             role="region"
             aria-roledescription="carousel"
             aria-label="{{ $block->title ?: $site->shortName().' highlights' }}"
             class="hero relative isolate bg-sky-950 text-white">

        <div x-ref="rail" @scroll.passive="onScroll()"
             class="hero-rail flex h-[clamp(20rem,52vw,34rem)] snap-x snap-mandatory overflow-x-auto">
            @foreach ($slides as $index => $slide)
                @php($hasCopy = $slide->title || $slide->subtitle || $slide->caption)

                {{-- `is-live` is set only by Alpine, never in the markup: a
                     static copy of it could never be removed again, because
                     x-bind only takes back the classes it added itself. --}}
                <article class="hero-slide relative flex h-full w-full shrink-0 snap-start snap-always items-end overflow-hidden"
                         :class="i === {{ $index }} && 'is-live'"
                         role="group" aria-roledescription="slide"
                         aria-label="Slide {{ $index + 1 }} of {{ $count }}">

                    <img src="{{ $slide->image_url }}" alt="{{ $slide->image_alt }}"
                         @if ($slide->width) width="{{ $slide->width }}" height="{{ $slide->height }}" @endif
                         sizes="100vw" decoding="async"
                         @if ($index === 0) fetchpriority="high" @else loading="lazy" @endif
                         class="absolute inset-0 h-full w-full object-cover">

                    {{-- The foot scrim is always there — it is what the control
                         bar sits on. The side scrim only appears behind copy,
                         so a picture with no words over it is left alone. --}}
                    <div class="absolute inset-x-0 bottom-0 h-2/3 bg-gradient-to-t from-sky-950 via-sky-950/40 to-transparent"></div>
                    @if ($hasCopy)
                        <div class="absolute inset-0 bg-gradient-to-r from-sky-950/75 via-sky-950/25 to-transparent"></div>
                    @endif

                    @if ($hasCopy)
                        <div class="relative mx-auto w-full max-w-7xl px-4 pb-28 sm:px-6 lg:pb-32">
                            <div class="hero-copy max-w-2xl">
                                @if ($slide->subtitle)
                                    <p class="eyebrow text-emerald-300">{{ $slide->subtitle }}</p>
                                @endif

                                @if ($slide->title)
                                    <h2 class="mt-4 text-3xl font-extrabold leading-[1.1] text-white sm:text-5xl lg:text-6xl">
                                        {{ $slide->title }}
                                    </h2>
                                @endif

                                @if ($slide->caption)
                                    <p class="mt-4 max-w-xl text-base leading-relaxed text-sky-100/90 sm:text-lg">
                                        {{ $slide->caption }}
                                    </p>
                                @endif

                                @if ($slide->link_url && $slide->link_text)
                                    <div class="mt-7">
                                        <a href="{{ $slide->link_url }}" target="{{ $slide->target }}"
                                           @if ($slide->target === '_blank') rel="noopener noreferrer" @endif
                                           class="btn-invert">
                                            {{ $slide->link_text }}
                                            <span aria-hidden="true">&rarr;</span>
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </article>
            @endforeach
        </div>

        @if ($count > 1)
            <div class="pointer-events-none absolute inset-x-0 bottom-0">
                <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-4 pb-6 sm:px-6 sm:pb-8">

                    {{-- The live segment fills as the slide's time runs down, so
                         the wait is visible instead of a surprise. --}}
                    <div class="pointer-events-auto flex items-center gap-4">
                        <p class="hidden text-sm font-semibold tabular-nums text-white/70 sm:block">
                            <span class="text-white" x-text="String(i + 1).padStart(2, '0')">01</span>
                            <span class="mx-1 text-white/40">/</span>
                            <span>{{ str_pad($count, 2, '0', STR_PAD_LEFT) }}</span>
                        </p>

                        <div class="flex items-center">
                            @foreach ($slides as $index => $slide)
                                <button type="button" @click="goto({{ $index }})"
                                        class="group/dot px-1 py-4 focus-visible:outline-offset-4"
                                        :aria-current="i === {{ $index }} ? 'true' : 'false'"
                                        aria-label="Go to slide {{ $index + 1 }}">
                                    <span class="block h-1 overflow-hidden rounded-full transition-all duration-300 group-hover/dot:bg-white/60"
                                          :class="i === {{ $index }} ? 'w-10 bg-white/40 sm:w-14' : 'w-5 bg-white/25 sm:w-7'">
                                        <span class="hero-dwell block h-full w-full rounded-full bg-white"
                                              x-show="i === {{ $index }}"></span>
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- No play/pause control: autoplay already stops on hover,
                         on focus, off-screen, in a background tab and under
                         `prefers-reduced-motion`, so the button was offering a
                         choice the carousel makes on its own. `paused` stays on
                         the component for anything that wants to drive it. --}}
                    <div class="pointer-events-auto flex items-center gap-2">
                        <button type="button" @click="prev()"
                                class="flex h-11 w-11 items-center justify-center rounded-full border border-white/25
                                       bg-white/10 text-white backdrop-blur transition hover:border-white/60 hover:bg-white/20"
                                aria-label="Previous slide">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </button>

                        <button type="button" @click="next()"
                                class="flex h-11 w-11 items-center justify-center rounded-full border border-white/25
                                       bg-white/10 text-white backdrop-blur transition hover:border-white/60 hover:bg-white/20"
                                aria-label="Next slide">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Announced only while the carousel is not advancing on its own:
                 a live region that fires every six seconds is unusable. --}}
            <p class="sr-only" :aria-live="running ? 'off' : 'polite'"
               x-text="`Slide ${i + 1} of ${n}`"></p>
        @endif
    </section>
@endif
