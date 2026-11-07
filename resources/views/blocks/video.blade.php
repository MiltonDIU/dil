@php($embed = $block->data['embed'] ?? null)

<section class="section">
    @if ($block->title || $block->subtitle)
        <div class="mb-8 max-w-2xl">
            @if ($block->subtitle)
                <p class="eyebrow mb-4">{{ $block->subtitle }}</p>
            @endif
            @if ($block->title)
                <h2 class="text-3xl font-extrabold leading-tight sm:text-4xl">{{ $block->title }}</h2>
            @endif
        </div>
    @endif

    @if ($embed)
        {{-- The frame is inset by a hairline of navy so the video reads as a
             deliberate object rather than a hole punched in the page. --}}
        <div class="rounded-3xl bg-sky-900/5 p-2 ring-1 ring-sky-900/10">
            <div class="aspect-video w-full overflow-hidden rounded-2xl bg-sky-950">
                <iframe src="{{ $embed }}" title="{{ $block->title ?? 'Video' }}"
                        class="h-full w-full" loading="lazy" allowfullscreen
                        allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
            </div>
        </div>
    @elseif ($url = $block->data['url'] ?? null)
        <iframe width="100%" height="450" src="https://www.youtube.com/embed/V2WpRf2Qbuc?si=F-XZDwmO3aqEAyq5" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
    @endif
</section>
