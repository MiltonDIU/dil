@php($images = $block->data['images'] ?? [])

@if ($images)
    <section class="bg-slate-50">
        <div class="section">
            <div class="mx-auto mb-10 max-w-2xl text-center">
                @if ($block->title)
                    <h2 class="text-3xl font-extrabold leading-tight sm:text-4xl">{{ $block->title }}</h2>
                @endif
                @if ($block->subtitle)
                    <p class="mt-4 text-lg text-slate-500">{{ $block->subtitle }}</p>
                @endif
            </div>

            <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($images as $image)
                    <li>
                        <figure class="relative overflow-hidden rounded-2xl bg-sky-950">
                            <img src="{{ $block->uploadUrl($image['image'] ?? null) }}" alt="{{ $image['alt'] ?? '' }}"
                                 loading="lazy" class="aspect-[3/2] w-full object-cover">
                            @if (!empty($image['caption']))
                                <figcaption class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-sky-950/90 to-transparent
                                                   px-4 pb-3 pt-10 text-sm font-semibold text-white">
                                    {{ $image['caption'] }}
                                </figcaption>
                            @endif
                        </figure>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif
