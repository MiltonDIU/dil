@php
    // Every attribute here comes from the field's own row, so an editor adding
    // a field in Filament gets a working, validated control with no deploy.
    $name = 'data['.$field->key.']';
    $id = 'field-'.$field->form_id.'-'.$field->key;
    // Page context (e.g. the course being viewed) may pre-fill a field; an
    // editor's default_value still applies everywhere else.
    $old = old('data.'.$field->key, ($prefill ?? [])[$field->key] ?? $field->default_value);
    $span = $field->width === 'half' ? 'sm:col-span-1' : 'sm:col-span-2';
    $invalid = $errors->has('data.'.$field->key);

    /*
     * The help text and the error message are announced with the control
     * rather than left as loose paragraphs beside it: without this, a screen
     * reader user hears "invalid" and is never told why.
     */
    $describedBy = trim(
        ($field->help ? $id.'-help ' : '').($invalid ? $id.'-error' : '')
    );

    $base = 'w-full rounded-xl border bg-white px-4 py-2.5 text-slate-800 transition placeholder:text-slate-400 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500/25 '
        .($invalid ? 'border-red-400' : 'border-slate-300');
@endphp

@if ($field->type === 'heading')
    <h3 class="sm:col-span-2 mt-4 text-lg font-bold">{{ $field->label }}</h3>
@elseif ($field->type === 'hidden')
    <input type="hidden" name="{{ $name }}" value="{{ $old }}">
@else
    <div class="{{ $span }}">
        {{-- Required is carried by the `required` attribute, which assistive
             tech announces on its own; the asterisk is the sighted cue for it
             and the legend under the form explains it. Optional fields say so
             in words, because "no asterisk" is not a thing you can notice. --}}
        <label id="{{ $id }}-label" for="{{ $id }}" class="mb-1.5 flex items-baseline gap-1.5 text-sm font-semibold text-sky-900">
            <span>
                {{ $field->label }}
                @if ($field->required)
                    <span class="text-red-600" aria-hidden="true">*</span>
                @endif
            </span>
            @unless ($field->required)
                <span class="text-xs font-normal text-slate-400">(optional)</span>
            @endunless
        </label>

        @switch($field->type)
            @case('textarea')
                <textarea id="{{ $id }}" name="{{ $name }}" rows="5"
                          placeholder="{{ $field->placeholder }}"
                          @if ($field->required) required @endif
                          @if ($invalid) aria-invalid="true" @endif
                          @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
                          class="{{ $base }}">{{ $old }}</textarea>
                @break

            @case('select')
                <select id="{{ $id }}" name="{{ $name }}"
                        @if ($field->required) required @endif
                        @if ($invalid) aria-invalid="true" @endif
                        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
                        class="{{ $base }}">
                    <option value="">{{ $field->placeholder ?: 'Please choose…' }}</option>
                    @foreach ($field->resolvedOptions() as $value => $label)
                        <option value="{{ $value }}" @selected($old == $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @break

            {{-- A group of choices is one control, so the group carries the
                 label and the invalid state, not each individual input. --}}
            @case('radio')
                <div class="space-y-2" role="radiogroup" aria-labelledby="{{ $id }}-label"
                     @if ($invalid) aria-invalid="true" @endif
                     @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>
                    @foreach ($field->resolvedOptions() as $value => $label)
                        <label class="flex items-center gap-2.5 text-sm text-slate-600">
                            <input type="radio" name="{{ $name }}" value="{{ $value }}"
                                   @checked($old == $value)
                                   @if ($field->required) required @endif
                                   class="text-sky-600 focus:ring-sky-500">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                @break

            @case('checkbox')
                <div class="space-y-2" role="group" aria-labelledby="{{ $id }}-label"
                     @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>
                    @foreach ($field->resolvedOptions() as $value => $label)
                        <label class="flex items-center gap-2.5 text-sm text-slate-600">
                            <input type="checkbox" name="{{ $name }}[]" value="{{ $value }}"
                                   @checked(is_array($old) && in_array($value, $old))
                                   class="rounded text-sky-600 focus:ring-sky-500">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                @break

            @case('file')
                <input type="file" id="{{ $id }}" name="{{ $name }}"
                       @if ($field->required) required @endif
                       @if ($invalid) aria-invalid="true" @endif
                       @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
                       class="{{ $base }} file:mr-3 file:rounded-lg file:border-0 file:bg-sky-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-sky-700">
                @break

            @default
                <input type="{{ $field->type }}" id="{{ $id }}" name="{{ $name }}"
                       value="{{ $old }}" placeholder="{{ $field->placeholder }}"
                       @if ($field->required) required @endif
                       @if ($invalid) aria-invalid="true" @endif
                       @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
                       class="{{ $base }}">
        @endswitch

        @if ($field->help)
            <p id="{{ $id }}-help" class="mt-1.5 text-xs text-slate-400">{{ $field->help }}</p>
        @endif

        @error('data.'.$field->key)
            <p id="{{ $id }}-error" class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
        @enderror
    </div>
@endif
