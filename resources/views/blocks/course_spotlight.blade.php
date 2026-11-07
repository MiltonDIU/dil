@php($courses = $block->spotlightCourses())
@php($levelNames = ['A1' => 'Beginner', 'A2' => 'Elementary', 'B1' => 'Intermediate', 'B2' => 'Upper intermediate'])
{{-- Literal classes from a map: Tailwind cannot see an interpolated class name. --}}
@php($columns = [1 => 'lg:grid-cols-1', 2 => 'lg:grid-cols-2', 3 => 'lg:grid-cols-3', 4 => 'lg:grid-cols-4'][max(1, min($courses->count(), 4))])

<section class="bg-sky-900 text-white">
    <div class="section">
        <div class="grid items-center gap-10 lg:grid-cols-2 lg:gap-16">
            <div>
                @if ($block->subtitle)
                    <p class="eyebrow text-emerald-300">{{ $block->subtitle }}</p>
                @endif

                @if ($block->title)
                    <h2 class="mt-4 text-3xl font-extrabold leading-tight text-white sm:text-5xl">{{ $block->title }}</h2>
                @endif

                @if ($block->content)
                    <div class="content-html mt-5 text-base leading-relaxed text-sky-100 sm:text-lg">{!! $block->content !!}</div>
                @endif

                @if ($block->link_url && $block->link_text)
                    <a href="{{ $block->link_url }}" class="btn-invert mt-7">
                        {{ $block->link_text }}
                        <span aria-hidden="true">&rarr;</span>
                    </a>
                @endif
            </div>

            @if ($block->image_url)
                <img src="{{ $block->image_url }}" alt="{{ $block->image_alt }}" loading="lazy"
                     class="aspect-[3/2] w-full rounded-2xl object-cover shadow-2xl shadow-sky-950/40">
            @endif
        </div>

        @if ($courses->isNotEmpty())
            <ol class="mt-12 grid gap-5 sm:grid-cols-2 lg:mt-16 {{ $columns }}">
                @foreach ($courses as $course)
                    @php($level = $course->cefr_levels[0] ?? null)

                    <li>
                        <a href="{{ route('course.show', $course) }}"
                           class="group flex h-full flex-col rounded-2xl bg-white p-6 text-sky-900 transition duration-300
                                  hover:-translate-y-1 hover:shadow-2xl hover:shadow-sky-950/40">
                            @if ($level)
                                <span class="text-4xl font-extrabold text-sky-600">{{ $level }}</span>
                                <span class="mt-1 text-sm font-semibold text-slate-500">{{ $levelNames[$level] ?? '' }}</span>
                            @else
                                <span class="text-lg font-extrabold">{{ $course->title }}</span>
                            @endif

                            <dl class="mt-5 flex-1 space-y-1.5 border-t border-slate-100 pt-4 text-sm">
                                @if ($course->duration_months)
                                    <div class="flex justify-between gap-3">
                                        <dt class="text-slate-400">Duration</dt>
                                        <dd class="font-semibold">{{ $course->duration_months }} months</dd>
                                    </div>
                                @endif
                                @if ($course->formatFee('regular'))
                                    <div class="flex justify-between gap-3">
                                        <dt class="text-slate-400">Fee</dt>
                                        <dd class="font-semibold text-sky-600">{{ $course->formatFee('regular') }}</dd>
                                    </div>
                                @endif
                            </dl>

                            <span class="link-arrow mt-5" aria-hidden="true">View course</span>
                        </a>
                    </li>
                @endforeach
            </ol>
        @endif
    </div>
</section>
