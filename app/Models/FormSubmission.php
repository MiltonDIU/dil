<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormSubmission extends Model
{
    protected $guarded = [];

    protected $casts = [
        'data' => 'array',
    ];

    public const STATUSES = [
        'new' => 'New',
        'read' => 'Read',
        'replied' => 'Replied',
        'spam' => 'Spam',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    /**
     * Submitted values keyed by their human label, for the admin detail view
     * and the CSV export. Falls back to the raw key if a field was deleted.
     *
     * @return array<string, string>
     */
    public function labelled(): array
    {
        $labels = $this->form?->fields->pluck('label', 'key')->all() ?? [];
        $out = [];

        foreach ($this->data ?? [] as $key => $value) {
            $out[$labels[$key] ?? $key] = is_array($value)
                ? implode(', ', $value)
                : (string) $value;
        }

        return $out;
    }

    /** Best-effort display name for the submissions table. */
    public function summary(): string
    {
        foreach (['name', 'full_name', 'email'] as $key) {
            if (! empty($this->data[$key])) {
                return (string) $this->data[$key];
            }
        }

        return '#'.$this->id;
    }
}
