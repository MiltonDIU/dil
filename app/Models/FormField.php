<?php

namespace App\Models;

use App\Models\Concerns\HasLegacyKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\Rule;

class FormField extends Model
{
    use HasLegacyKey;

    protected $guarded = [];

    protected $casts = [
        'options' => 'array',
        'validation' => 'array',
        'required' => 'boolean',
        'published' => 'boolean',
    ];

    public const TYPES = [
        'text' => 'Single line text',
        'email' => 'Email',
        'tel' => 'Phone',
        'number' => 'Number',
        'textarea' => 'Paragraph',
        'select' => 'Dropdown',
        'radio' => 'Radio buttons',
        'checkbox' => 'Checkboxes',
        'date' => 'Date',
        'file' => 'File upload',
        'hidden' => 'Hidden',
        'heading' => 'Section heading',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    /**
     * Choice options, either literal or queried live from the database.
     *
     * `options_source` keeps the Course Enquiry dropdown in sync with the
     * courses table automatically — an editor publishing a new course does not
     * also have to remember to edit the form.
     *
     * @return array<string, string>
     */
    public function resolvedOptions(): array
    {
        if ($this->options_source === 'courses') {
            return Course::query()->where('published', true)
                ->orderBy('ordering')->orderBy('title')
                ->pluck('title', 'title')->all();
        }

        $out = [];

        foreach ($this->options ?? [] as $option) {
            if (is_array($option)) {
                $value = $option['value'] ?? $option['label'] ?? null;
                if ($value !== null) {
                    $out[$value] = $option['label'] ?? $value;
                }
            } else {
                $out[$option] = $option;
            }
        }

        return $out;
    }

    /**
     * Laravel validation rules for this field, built from its own config.
     *
     * @return array<int, mixed>
     */
    public function rules(): array
    {
        $v = $this->validation ?? [];
        $rules = [$this->required ? 'required' : 'nullable'];

        $rules[] = match ($this->type) {
            'email' => 'email:rfc',
            'number' => 'numeric',
            'date' => 'date',
            'file' => 'file',
            'checkbox' => 'array',
            default => 'string',
        };

        if ($this->type === 'file') {
            $rules[] = 'max:'.(int) ($v['max_kb'] ?? 5120);
            if (! empty($v['mimes'])) {
                $rules[] = 'mimes:'.(is_array($v['mimes']) ? implode(',', $v['mimes']) : $v['mimes']);
            }
        } elseif (! in_array($this->type, ['checkbox', 'number', 'date'], true)) {
            $rules[] = 'max:'.(int) ($v['max'] ?? 5000);
            if (! empty($v['min'])) {
                $rules[] = 'min:'.(int) $v['min'];
            }
        }

        if ($this->type === 'number') {
            if (isset($v['min'])) {
                $rules[] = 'min:'.(int) $v['min'];
            }
            if (isset($v['max'])) {
                $rules[] = 'max:'.(int) $v['max'];
            }
        }

        if (! empty($v['regex'])) {
            $rules[] = 'regex:'.$v['regex'];
        }

        if (in_array($this->type, ['select', 'radio'], true)) {
            $options = array_keys($this->resolvedOptions());
            if ($options !== []) {
                $rules[] = Rule::in($options);
            }
        }

        return $rules;
    }
}
