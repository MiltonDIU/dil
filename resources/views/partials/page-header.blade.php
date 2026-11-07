{{--
    Inner pages open the way DIU's do: a navy band lit from one corner, the
    breadcrumb small above the title. The glow is a blurred layer, not an
    image, so it costs nothing and adapts to any width.
--}}
<div class="relative isolate overflow-hidden bg-sky-900 text-white">
    <div aria-hidden="true"
         class="pointer-events-none absolute -right-32 -top-40 h-[28rem] w-[28rem] rounded-full bg-sky-600/30 blur-3xl"></div>
    <div aria-hidden="true"
         class="pointer-events-none absolute -bottom-48 left-1/4 h-80 w-80 rounded-full bg-emerald-600/15 blur-3xl"></div>

    <div class="relative mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:py-20">
        {{-- The crumb trail wraps on a phone instead of forcing the band wide. --}}
        <nav aria-label="Breadcrumb" class="mb-5 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-sky-200/80">
            <a href="{{ route('home') }}" class="transition-colors hover:text-white">Home</a>
            @foreach ($crumbs ?? [] as $label => $url)
                <span aria-hidden="true" class="text-sky-200/40">/</span>
                @if ($url)
                    <a href="{{ $url }}" class="transition-colors hover:text-white">{{ $label }}</a>
                @else
                    <span>{{ $label }}</span>
                @endif
            @endforeach
            <span aria-hidden="true" class="text-sky-200/40">/</span>
            <span class="font-medium text-white" aria-current="page">{{ $title }}</span>
        </nav>

        <h1 class="max-w-4xl text-3xl font-extrabold leading-[1.15] text-white sm:text-4xl lg:text-5xl">{{ $title }}</h1>

        @if (!empty($lead))
            <p class="mt-5 max-w-2xl text-base leading-relaxed text-sky-100/85 sm:text-lg">{{ $lead }}</p>
        @endif
    </div>
</div>
