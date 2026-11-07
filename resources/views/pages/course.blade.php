@extends('layouts.app')

@section('title', $course->metaTitle().' — '.$site->shortName())
@section('meta_description', $course->meta_description ?: $site->get('meta_description'))
@section('og_image', $course->og_image_url ?? '')

@section('content')
    @include('partials.page-header', [
        'title' => $course->title,
        'lead' => $course->summary,
        'crumbs' => ['Courses' => route('course.index')],
    ])

    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 lg:grid-cols-3 lg:gap-14 lg:py-20">
        <article class="lg:col-span-2">
            @if ($course->featured_image_url)
                <img src="{{ $course->featured_image_url }}" alt="{{ $course->featured_image_alt }}"
                     class="mb-10 w-full rounded-2xl object-cover">
            @endif

            @if ($course->isEmpty())
                <p class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
                    Details for this course are being prepared. Please contact us for information.
                </p>
            @else
                <div class="content-html">{!! $course->renderableBody() !!}</div>
            @endif

            @if ($lessons->isNotEmpty())
                <section class="mt-14" aria-labelledby="lesson-breakdown-heading">
                    <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-2">
                        <div>
                            <p class="eyebrow">Curriculum</p>
                            <h2 id="lesson-breakdown-heading" class="mt-2 text-2xl font-extrabold sm:text-3xl">Lesson breakdown</h2>
                        </div>
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">
                            {{ $lessons->count() }} {{ \Illuminate\Support\Str::plural('section', $lessons->count()) }}
                        </p>
                    </div>

                    <div class="mt-7 space-y-3 sm:space-y-4">
                        @foreach ($lessons as $lesson)
                            {{-- The first section starts open so the list reads as
                                 content, not as a wall of closed drawers. The open
                                 item is visually lifted; class strings stay literal
                                 so Tailwind can see them. --}}
                            <div x-data="{ open: {{ $loop->first ? 'true' : 'false' }} }"
                                 class="group overflow-hidden rounded-2xl border bg-white transition-all duration-300"
                                 :class="open
                                     ? 'border-sky-200 shadow-xl shadow-sky-900/5'
                                     : 'border-slate-200 hover:border-sky-300/70 hover:shadow-md hover:shadow-sky-900/5'">
                                <button type="button"
                                        @click="open = !open"
                                        :aria-expanded="open.toString()"
                                        aria-controls="lesson-{{ $lesson->id }}"
                                        class="flex w-full items-center gap-3 px-4 py-4 text-left focus-visible:outline-none
                                               focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-sky-500/40 sm:gap-4 sm:px-6 sm:py-5">
                                    <span aria-hidden="true"
                                          class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-sm font-extrabold
                                                 transition-colors duration-300 sm:h-11 sm:w-11 sm:rounded-2xl sm:text-base"
                                          :class="open
                                              ? 'bg-gradient-to-br from-sky-600 to-sky-900 text-white shadow-md shadow-sky-900/25'
                                              : 'bg-sky-50 text-sky-700 group-hover:bg-sky-100'">
                                        {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}
                                    </span>

                                    <span class="min-w-0 flex-1">
                                        <span class="block text-sm font-bold leading-snug text-sky-900 sm:text-base">
                                            {{ $lesson->title }}
                                        </span>
                                        @if ($lesson->duration_label)
                                            <span class="mt-1.5 inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1
                                                         text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                                <svg class="h-3 w-3 text-emerald-500" fill="none" viewBox="0 0 24 24"
                                                     stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          d="M12 8v4l2.5 2.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                {{ $lesson->duration_label }}
                                            </span>
                                        @endif
                                    </span>

                                    @if (filled($lesson->body))
                                        <span aria-hidden="true"
                                              class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border
                                                     transition-all duration-300 sm:h-9 sm:w-9"
                                              :class="open
                                                  ? 'rotate-180 border-sky-200 bg-sky-50 text-sky-700'
                                                  : 'border-slate-200 text-slate-400 group-hover:border-sky-200 group-hover:text-sky-600'">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                 stroke-width="2" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </span>
                                    @endif
                                </button>

                                @if (filled($lesson->body))
                                    <div id="lesson-{{ $lesson->id }}" x-show="open" x-cloak x-collapse>
                                        <div class="px-4 pb-5 sm:px-6 sm:pb-6">
                                            <div class="content-html border-t border-slate-100 pt-4 text-sm text-slate-600 sm:pl-[3.75rem] sm:text-base">
                                                {!! $lesson->body !!}
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            @if (filled($site->get('payment_info')))
                <section class="mt-14" aria-labelledby="payment-info-heading">
                    <p class="eyebrow">Payments</p>
                    <h2 id="payment-info-heading" class="mt-2 text-2xl font-extrabold sm:text-3xl">How to pay</h2>

                    <div class="mt-6 rounded-2xl border border-emerald-200/70 bg-emerald-50/40 p-5 sm:p-7">
                        <div class="content-html text-sm text-slate-600 sm:text-base">
                            {!! $site->get('payment_info') !!}
                        </div>
                    </div>
                </section>
            @endif
        </article>

        {{-- The panels follow the reader down on a wide screen and simply stack
             above the form on a narrow one. --}}
        <aside class="space-y-6 lg:sticky lg:top-28 lg:self-start">
            {{-- Facts panel. Every row is hidden when the value is unknown,
                 so nothing is ever invented to fill the layout. --}}
            @php($facts = array_filter([
                'Language' => $course->language,
                'Level' => $course->levelsLabel(),
                'Duration' => $course->duration_months ? $course->duration_months.' months' : null,
                'Classes per week' => $course->classes_per_week,
                'Total classes' => $course->total_classes,
                'Class length' => $course->class_duration_minutes ? $course->class_duration_minutes.' minutes' : null,
            ]))

            @if ($facts)
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6">
                    <h2 class="mb-5 text-base font-bold">Course details</h2>
                    <dl class="space-y-3 text-sm">
                        @foreach ($facts as $label => $value)
                            <div class="flex justify-between gap-4 border-b border-slate-200/70 pb-3 last:border-0 last:pb-0">
                                <dt class="text-slate-400">{{ $label }}</dt>
                                <dd class="text-right font-semibold text-sky-900">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endif

            @php($fees = array_filter([
                'Regular' => $course->formatFee('regular'),
                'Daffodil family' => $course->formatFee('daffodil'),
                'Outside students' => $course->formatFee('outside'),
            ]))

            @if ($fees)
                <div class="relative isolate overflow-hidden rounded-2xl bg-sky-900 p-6 text-white">
                    <div aria-hidden="true"
                         class="pointer-events-none absolute -right-16 -top-16 h-48 w-48 rounded-full bg-sky-600/40 blur-2xl"></div>
                    <h2 class="relative mb-5 text-base font-bold text-white">Fees</h2>
                    <dl class="relative space-y-3 text-sm">
                        @foreach ($fees as $label => $value)
                            <div class="flex justify-between gap-4 border-b border-white/10 pb-3 last:border-0 last:pb-0">
                                <dt class="text-sky-200/80">{{ $label }}</dt>
                                <dd class="font-bold text-white">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                    @if ($course->fee_note)
                        <p class="relative mt-4 text-xs text-sky-200/70">{{ $course->fee_note }}</p>
                    @endif
                    <a href="#form-{{ $site->get('enrolment_form_slug', 'enrolment') }}"
                       class="btn-invert relative mt-6 w-full">Enrol now</a>
                </div>
            @endif

            @if ($related->isNotEmpty())
                <div class="rounded-2xl border border-slate-200 p-6">
                    <h2 class="mb-4 text-base font-bold">Other courses</h2>
                    <ul class="space-y-1">
                        @foreach ($related as $other)
                            <li>
                                <a href="{{ route('course.show', $other) }}"
                                   class="group flex items-center justify-between gap-3 rounded-lg py-2 text-sm font-medium
                                          text-slate-600 transition-colors hover:text-sky-700">
                                    {{ $other->title }}
                                    <span aria-hidden="true"
                                          class="text-sky-400 transition-transform duration-200 group-hover:translate-x-1">&rarr;</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </aside>
    </div>

    
@endsection
