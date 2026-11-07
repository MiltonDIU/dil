<section class="section">
    <div class="grid gap-8 lg:grid-cols-12 lg:gap-16">
        {{-- DIU sets the heading beside the body copy rather than above it, so
             an article never opens with a full-width wall of text. --}}
        <div class="lg:col-span-5">
            @if ($block->title)
                <h2 class="text-3xl font-extrabold leading-tight sm:text-4xl">{{ $block->title }}</h2>
            @endif
            @if ($block->subtitle)
                <p class="mt-4 text-lg leading-relaxed text-slate-500">{{ $block->subtitle }}</p>
            @endif

            @if ($block->link_url && $block->link_text)
                <a href="{{ $block->link_url }}" class="btn-primary mt-8 hidden lg:inline-flex">
                    {{ $block->link_text }}
                    <span aria-hidden="true">&rarr;</span>
                </a>
            @endif
        </div>

        <div class="lg:col-span-7">
            <div class="content-html text-base leading-relaxed">{!! $block->content !!}</div>

            @if ($block->link_url && $block->link_text)
                <a href="{{ $block->link_url }}" class="btn-primary mt-8 lg:hidden">
                    {{ $block->link_text }}
                    <span aria-hidden="true">&rarr;</span>
                </a>
            @endif
        </div>
    </div>
</section>
