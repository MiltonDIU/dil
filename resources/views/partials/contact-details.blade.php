<div class="mt-12 grid gap-8 rounded-3xl border border-slate-200 bg-white p-6 sm:p-8 md:grid-cols-2">
    <div>
        <h2 class="mb-4 text-xl font-bold">Visit us</h2>
        <address class="space-y-2 text-sm not-italic leading-relaxed text-slate-500">
            @if ($address = $site->get('contact_address'))
                <p class="whitespace-pre-line">{{ $address }}</p>
            @endif
            @if ($phone = $site->get('contact_phone'))
                <p><a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="font-semibold text-sky-700 hover:underline">{{ $phone }}</a></p>
            @endif
            @if ($email = $site->get('contact_email'))
                <p><a href="mailto:{{ $email }}" class="font-semibold text-sky-700 hover:underline">{{ $email }}</a></p>
            @endif
        </address>
    </div>

    @if ($map = $site->get('map_embed'))
        {{-- The stored embed carries its own fixed width and height, so the wrapper
             forces it into a responsive box instead of overflowing the column. --}}
        <div class="content-html overflow-hidden rounded-2xl border border-slate-200 [&_iframe]:aspect-[4/3] [&_iframe]:h-auto [&_iframe]:w-full">{!! $map !!}</div>
    @endif
</div>
