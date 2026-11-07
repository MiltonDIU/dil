{{--
    DIU's call-to-action band: full-bleed navy, lit from one corner by a soft
    blue glow. The glow is a blurred radial layer rather than an image, so it
    costs nothing and scales to any viewport.
--}}
<section class="relative isolate overflow-hidden bg-sky-900 text-white">
    <div aria-hidden="true"
         class="pointer-events-none absolute -right-24 -top-32 h-96 w-96 rounded-full bg-sky-600/35 blur-3xl"></div>
    <div aria-hidden="true"
         class="pointer-events-none absolute -bottom-40 -left-24 h-96 w-96 rounded-full bg-emerald-600/20 blur-3xl"></div>

    <div class="section relative">
        <div class="mx-auto max-w-3xl text-center">
            @if ($block->subtitle)
                <p class="eyebrow justify-center text-emerald-300">{{ $block->subtitle }}</p>
            @endif

            @if ($block->title)
                <h2 class="mt-5 text-3xl font-extrabold leading-tight text-white sm:text-4xl lg:text-5xl">
                    {{ $block->title }}
                </h2>
            @endif

            @if ($block->content)
                <div class="content-html mx-auto mt-5 max-w-2xl text-lg leading-relaxed text-sky-100/85">
                    {!! $block->content !!}
                </div>
            @endif

            @if ($block->link_url && $block->link_text)
                <div class="mt-9">
                    <a href="{{ $block->link_url }}" class="btn-invert">
                        {{ $block->link_text }}
                        <span aria-hidden="true">&rarr;</span>
                    </a>
                </div>
            @endif
        </div>
    </div>
</section>
