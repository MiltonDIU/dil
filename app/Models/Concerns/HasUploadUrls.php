<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Storage;

/**
 * Upload path convention (playbook Phase 4).
 *
 * Columns store DISK-RELATIVE paths on the `public` disk ("media/slides/x.jpg").
 * Blade and Filament ImageColumn read the `*_url` accessor, never the raw column.
 * Filament FileUpload->disk('public')->directory(...) round-trips with this —
 * do NOT add formatStateUsing/dehydrateStateUsing prefix hacks, they break
 * upload previews.
 *
 * The only exception is inline <img> inside stored HTML, which is rewritten to
 * an absolute /storage/... path at import time and rendered as-is.
 */
trait HasUploadUrls
{
    public function uploadUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://')
            || str_starts_with($path, 'https://')
            || str_starts_with($path, '/storage/')
        ) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }

    /**
     * True when the column points at a file that no longer exists.
     * Used by the Phase 8 dead-media report.
     */
    public function uploadMissing(?string $path): bool
    {
        return filled($path) && ! Storage::disk('public')->exists($path);
    }
}
