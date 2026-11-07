<div>
    @if ($block->title)
        <h2 class="mb-5 text-xl font-bold">{{ $block->title }}</h2>
    @endif

    {{-- The stored embed is a raw Google Maps <iframe> with its own fixed
         width and height attributes, so the wrapper forces it to fill a
         responsive box instead of overflowing the column on a phone. --}}
    <div class="content-html overflow-hidden rounded-2xl border border-slate-200 [&_iframe]:aspect-[4/3] [&_iframe]:h-auto [&_iframe]:w-full">
        {!! $block->data['embed'] ?? '' !!}
    </div>
</div>
