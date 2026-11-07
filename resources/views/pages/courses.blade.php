@extends('layouts.app')

@section('title', 'Courses — '.$site->shortName())
@section('meta_description', 'Language courses, IELTS preparation and short English courses at '.$site->name().'.')

@section('content')
    @include('partials.page-header', [
        'title' => 'Courses',
        'lead' => 'Foreign languages, IELTS preparation and short English courses.',
    ])

    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:py-20">
        @if ($courses->isEmpty())
            <p class="text-slate-500">No courses are published yet.</p>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($courses as $course)
                    @php($facts = array_filter([
                        'Level' => $course->levelsLabel(),
                        'Duration' => $course->duration_months ? $course->duration_months.' months' : null,
                        'Fee' => $course->formatFee('regular'),
                    ]))

                    <a href="{{ route('course.show', $course) }}" class="card group flex flex-col p-7">
                        @if ($course->language)
                            <span class="mb-3 inline-flex w-fit rounded-full bg-sky-50 px-3 py-1 text-xs font-bold uppercase tracking-wider text-sky-700">
                                {{ $course->language }}
                            </span>
                        @endif

                        <h2 class="text-lg font-bold transition-colors group-hover:text-sky-600">{{ $course->title }}</h2>

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
                    </a>
                @endforeach
            </div>
        @endif
    </div>
@endsection
