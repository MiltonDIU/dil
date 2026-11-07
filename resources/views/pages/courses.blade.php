@extends('layouts.app')

@section('title', 'Courses — '.$site->shortName())
@section('meta_description', 'Language courses, IELTS preparation and short English courses at '.$site->name().'.')

@section('content')
    @include('partials.page-header', [
        'title' => 'Courses',
        'lead' => 'Foreign languages, IELTS preparation and short English courses.',
    ])

    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:py-20">
        @if ($languages->isEmpty())
            <p class="text-slate-500">No courses are published yet.</p>
        @else
            <nav aria-label="Jump to a language" class="mb-12 flex flex-wrap gap-2">
                @foreach ($languages as $language => $courses)
                    <a href="#lang-{{ Str::slug($language) }}"
                       class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-sky-900
                              transition-colors hover:border-sky-300 hover:bg-sky-50">
                        {{ $language }}
                    </a>
                @endforeach
            </nav>

            <div class="space-y-16">
                @foreach ($languages as $language => $courses)
                    <section id="lang-{{ Str::slug($language) }}" class="scroll-mt-28">
                        <div class="mb-6 flex flex-wrap items-center gap-3">
                            <h2 class="text-2xl font-extrabold sm:text-3xl">{{ \App\Models\Course::languageLabel($language) }}</h2>
                            @if ($courses->contains('featured', true))
                                <span class="rounded-full bg-emerald-500 px-3 py-1 text-xs font-bold uppercase tracking-wider text-white">Featured</span>
                            @endif
                        </div>

                        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($courses as $course)
                                @php($facts = array_filter([
                                    'Level' => $course->levelsLabel(),
                                    'Duration' => $course->duration_months ? $course->duration_months.' months' : null,
                                    'Fee' => $course->formatFee('regular'),
                                ]))

                                <a href="{{ route('course.show', $course) }}" class="card group flex flex-col overflow-hidden">
                                    @if ($course->featured_image_url)
                                        <img src="{{ $course->featured_image_url }}" alt="{{ $course->featured_image_alt }}"
                                             loading="lazy" class="aspect-video w-full object-cover">
                                    @endif

                                    <div class="flex flex-1 flex-col p-7">
                                        <h3 class="text-lg font-bold transition-colors group-hover:text-sky-600">{{ $course->title }}</h3>

                                        @if ($course->summary)
                                            <p class="mt-3 text-sm leading-relaxed text-slate-500">{{ Str::limit($course->summary, 130) }}</p>
                                        @endif

                                        {{-- `flex-1` sits on the spacer, not the summary: four
                                             courses carry no fee and two carry no copy at all,
                                             and hanging the growth off the text left those
                                             cards with a rule across an empty box. --}}
                                        <div class="flex-1"></div>

                                        @if ($facts)
                                            <dl class="mt-6 space-y-1.5 border-t border-slate-100 pt-4 text-sm">
                                                @foreach ($facts as $label => $value)
                                                    <div class="flex justify-between gap-3">
                                                        <dt class="text-slate-400">{{ $label }}</dt>
                                                        <dd class="text-right font-semibold {{ $label === 'Fee' ? 'text-sky-600' : 'text-sky-900' }}">{{ $value }}</dd>
                                                    </div>
                                                @endforeach
                                            </dl>
                                        @endif

                                        <span class="link-arrow mt-6" aria-hidden="true">View course</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        @endif
    </div>
@endsection
