@php($form = \App\Models\Form::published()->with('publishedFields')->where('slug', $slug ?? '')->first())

@if ($form)
    @php($hasRequired = $form->publishedFields->contains(fn ($f) => $f->required && ! in_array($f->type, ['heading', 'hidden'], true)))

    <section id="form-{{ $form->slug }}" class="mt-14 rounded-3xl border border-slate-200 bg-slate-50 p-6 sm:p-10">
        <h2 class="text-2xl font-extrabold sm:text-3xl">{{ $form->name }}</h2>

        @if ($form->description)
            <p class="mt-3 text-slate-500">{{ $form->description }}</p>
        @endif

        @if ($message = session('form_success.'.$form->slug))
            <div role="status" class="mt-6 flex items-start gap-3 rounded-xl border border-emerald-300 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-900">
                <svg class="mt-0.5 h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0z" clip-rule="evenodd"/>
                </svg>
                {{ $message }}
            </div>
        @endif

        @if ($errors->any())
            {{-- Focusable and focused on arrival, so a keyboard or screen reader
                 user lands on the reason the submission bounced rather than
                 back at the top of the page with no explanation. --}}
            <div role="alert" tabindex="-1" x-data x-init="$el.focus()"
                 class="mt-6 rounded-xl border border-red-300 bg-red-50 px-5 py-4 text-sm text-red-900">
                <p class="font-semibold">Please check the form:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('form.submit', $form) }}" class="mt-8 grid gap-5 sm:grid-cols-2"
              @if ($form->publishedFields->contains('type', 'file')) enctype="multipart/form-data" @endif>
            @csrf

            {{-- Honeypot: real browsers leave this empty. --}}
            <div class="hidden" aria-hidden="true">
                <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
            </div>

            @if ($hasRequired)
                {{-- An asterisk means nothing on its own; this is what it means. --}}
                <p class="text-xs text-slate-400 sm:col-span-2">
                    Fields marked <span class="font-semibold text-red-600">*</span> are required.
                </p>
            @endif

            @foreach ($form->publishedFields as $field)
                @include('partials.form-field', ['field' => $field, 'prefill' => $prefill ?? []])
            @endforeach

            <div class="pt-1 sm:col-span-2">
                <button type="submit" class="btn-primary">{{ $form->submit_label }}</button>
            </div>
        </form>
    </section>
@endif
