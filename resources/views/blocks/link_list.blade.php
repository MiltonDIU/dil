@php($items = $block->data['items'] ?? [])

<div>
    @if ($block->title)
        <h2 class="mb-5 text-xl font-bold">{{ $block->title }}</h2>
    @endif

    <ul class="divide-y divide-slate-200 border-y border-slate-200">
        @foreach ($items as $item)
            <li>
                {{-- The legacy lists were plain <li> text with no href, so an
                     item without a URL renders as text rather than a dead link. --}}
                @if (!empty($item['url']))
                    <a href="{{ $item['url'] }}"
                       class="group flex items-center justify-between gap-4 py-3 text-sm font-medium text-slate-600
                              transition-colors hover:text-sky-700">
                        {{ $item['label'] }}
                        <span aria-hidden="true"
                              class="text-sky-400 transition-transform duration-200 group-hover:translate-x-1">&rarr;</span>
                    </a>
                @else
                    <span class="block py-3 text-sm text-slate-500">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ul>
</div>
