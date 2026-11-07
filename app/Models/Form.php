<?php

namespace App\Models;

use App\Models\Concerns\HasLegacyKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Form extends Model
{
    use HasLegacyKey;

    protected $guarded = [];

    protected $casts = [
        'notify_emails' => 'array',
        'store_submissions' => 'boolean',
        'published' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class)->orderBy('ordering');
    }

    public function publishedFields(): HasMany
    {
        return $this->fields()->where('published', true);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('published', true);
    }

    /**
     * Validation rules derived entirely from the field configuration, so an
     * editor adding a field in Filament gets working validation with no deploy.
     *
     * @return array<string, mixed>
     */
    public function validationRules(): array
    {
        $rules = [];

        foreach ($this->publishedFields as $field) {
            if ($field->type === 'heading') {
                continue;
            }

            $rules['data.'.$field->key] = $field->rules();
        }

        return $rules;
    }

    /**
     * The label an editor typed, verbatim.
     *
     * Lower-casing it read badly against Laravel's own sentence — a field
     * labelled "Your name" produced "The your name field is required." The
     * messages below are phrased to take the label as written instead.
     *
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return $this->publishedFields
            ->mapWithKeys(fn (FormField $f) => ['data.'.$f->key => $f->label])
            ->all();
    }

    /**
     * Messages for the rules a visitor actually trips over. Anything not
     * listed keeps Laravel's default wording.
     *
     * @return array<string, string>
     */
    public function validationMessages(): array
    {
        return [
            'data.*.required' => ':attribute is required.',
            'data.*.email' => 'Please enter a valid email address.',
            'data.*.in' => 'Please choose one of the listed options.',
            'data.*.numeric' => ':attribute must be a number.',
            'data.*.date' => ':attribute must be a valid date.',
            'data.*.max' => ':attribute is too long.',
            'data.*.mimes' => ':attribute must be one of the accepted file types.',
        ];
    }
}
