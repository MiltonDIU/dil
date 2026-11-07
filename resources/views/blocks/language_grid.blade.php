@php($languages = \App\Models\Course::byLanguage()->except('Other'))

@if ($languages->isNotEmpty())
    <section class="bg-white">
        <div class="section">
            <div class="mx-auto mb-10 max-w-2xl text-center">
                @if ($block->title)
                    <h2 class="text-3xl font-extrabold leading-tight sm:text-4xl">{{ $block->title }}</h2>
                @endif
                @if ($block->subtitle)
                    <p class="mt-4 text-lg text-slate-500">{{ $block->subtitle }}</p>
                @endif
            </div>

            <ul class="grid grid-cols-2 gap-4 md:grid-cols-4">
                @foreach ($languages as $language => $courses)
                    <li>
                        <a href="{{ route('course.index') }}#lang-{{ Str::slug($language) }}"
                           class="card flex h-full items-center justify-between gap-3 px-5 py-5 font-bold text-sky-900">
                            {{ $language }}
                            <span aria-hidden="true" class="text-sky-600">&rarr;</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif
