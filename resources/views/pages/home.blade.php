@extends('layouts.app')

@section('title', $site->name())
@section('meta_description', $site->get('meta_description'))

@section('content')
    @php
        /*
         * The legacy homepage's last row put Downloads / Our Partners /
         * Where we are side by side. Those became three separate blocks so an
         * editor can reorder them individually, so consecutive "column" blocks
         * are regrouped into one responsive row here.
         */
        $columnTypes = ['link_list', 'map'];
        $groups = [];
        foreach ($blocks as $block) {
            $isColumn = in_array($block->type, $columnTypes, true);
            $last = array_key_last($groups);

            if ($isColumn && $last !== null && $groups[$last]['columns']) {
                $groups[$last]['blocks'][] = $block;
            } else {
                $groups[] = ['columns' => $isColumn, 'blocks' => [$block]];
            }
        }

        /*
         * Literal class names, looked up rather than interpolated: Tailwind
         * only ships classes it can find as text, and `md:grid-cols-{{ $n }}`
         * compiles to a PHP echo it can never match, so the row had been
         * stacking to one column at every width.
         */
        $columnClasses = [1 => 'md:grid-cols-1', 2 => 'md:grid-cols-2', 3 => 'md:grid-cols-3'];
    @endphp

    @forelse ($groups as $group)
        @if ($group['columns'])
            <section class="bg-slate-50">
                <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:py-20">
                    <div class="grid gap-10 lg:gap-14 {{ $columnClasses[min(count($group['blocks']), 3)] }}">
                        @foreach ($group['blocks'] as $block)
                            @include($block->view(), ['block' => $block])
                        @endforeach
                    </div>
                </div>
            </section>
        @else
            @foreach ($group['blocks'] as $block)
                @includeIf($block->view(), ['block' => $block, 'slides' => $slides])
            @endforeach
        @endif
    @empty
        <div class="mx-auto max-w-3xl px-4 py-24 text-center">
            <h1 class="text-3xl font-extrabold">{{ $site->name() }}</h1>
            <p class="mt-3 text-slate-500">
                No homepage blocks are published yet. Add them under
                <strong class="font-semibold text-sky-900">Appearance &rarr; Homepage blocks</strong> in the admin.
            </p>
        </div>
    @endforelse
@endsection
