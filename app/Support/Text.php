<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Shared text helpers used by every importer.
 *
 * Recon found no mojibake in this particular dump (the Bengali content is clean
 * utf8mb4), but `fixMojibake()` still runs on every imported string — it is a
 * no-op on healthy text and cheap insurance against the one column nobody
 * checked.
 */
class Text
{
    /**
     * Repair UTF-8 that was decoded as cp1252 and re-encoded ("Ã©", "â€™").
     * Idempotent and safe on already-correct text.
     */
    public static function fixMojibake(?string $s): string
    {
        $s = (string) $s;

        if ($s === '' || ! preg_match('/[ÃÂâ€]/u', $s)) {
            return $s;
        }

        $candidate = $s;

        // Repeat: text can be double- or triple-encoded.
        for ($i = 0; $i < 3; $i++) {
            $decoded = @mb_convert_encoding($candidate, 'ISO-8859-1', 'UTF-8');

            if ($decoded === false || $decoded === '' || ! mb_check_encoding($decoded, 'UTF-8')) {
                break;
            }

            // Only accept the repair if it reduced the tell-tale sequences.
            if (self::mojibakeScore($decoded) >= self::mojibakeScore($candidate)) {
                break;
            }

            $candidate = $decoded;
        }

        return $candidate;
    }

    private static function mojibakeScore(string $s): int
    {
        preg_match_all('/(Ã[\x80-\xBF]|â€|Â[\xA0-\xBF])/u', $s, $m);

        return count($m[0]);
    }

    /**
     * A URL slug that is never empty and never collides.
     *
     * Joomla's `unicodeslugs` was off, so Bengali titles produced date-stamped
     * aliases like "2026-07-14-06-06-14". Where that happened we fall back to a
     * transliterated slug rather than carrying the timestamp forward.
     */
    public static function slug(?string $value, string $fallback = 'item'): string
    {
        $value = trim((string) $value);

        $slug = Str::slug($value);

        // Bengali (and other non-Latin) titles slug to an empty string.
        if ($slug === '') {
            $slug = Str::slug(Str::ascii($value));
        }

        return $slug !== '' ? $slug : Str::slug($fallback);
    }

    /**
     * Make a slug unique within a table, appending -2, -3, … as needed.
     */
    public static function uniqueSlug(
        string $slug,
        string $table,
        ?int $ignoreId = null,
        string $column = 'slug',
    ): string {
        $base = $slug;
        $i = 1;

        while (
            DB::table($table)
                ->where($column, $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }

    /**
     * Strip editor cruft from TinyMCE/Word HTML without touching real content.
     */
    public static function cleanHtml(?string $html): string
    {
        $html = (string) $html;

        if (trim($html) === '') {
            return '';
        }

        // Word/Office leftovers
        $html = preg_replace('~<!--\[if [^\]]*\]>.*?<!\[endif\]-->~is', '', $html) ?? $html;
        $html = preg_replace('~</?o:p[^>]*>~i', '', $html) ?? $html;
        $html = preg_replace('~\s(class|lang)="?Mso[^"\s>]*"?~i', '', $html) ?? $html;

        // Legacy presentational tags
        $html = preg_replace('~</?font[^>]*>~i', '', $html) ?? $html;

        // Empty paragraphs / spans left behind by the editor
        $html = preg_replace('~<p[^>]*>(\s|&nbsp;|<br\s*/?>)*</p>~i', '', $html) ?? $html;
        $html = preg_replace('~<span>\s*</span>~i', '', $html) ?? $html;

        // Collapse runs of <br>
        $html = preg_replace('~(<br\s*/?>\s*){3,}~i', '<br><br>', $html) ?? $html;

        /*
         * Double-encoded entities. Joomla stored "Languages &amp;amp; Culture",
         * which renders literally as "Languages &amp; Culture" on the page —
         * the ampersand was escaped once by the editor and again on save.
         * Only one layer is peeled, and only for the entities that actually
         * occur, so a genuine "&amp;lt;" in a code sample survives.
         */
        $html = preg_replace(
            '~&amp;(amp|lt|gt|quot|apos|nbsp|#0*39|#0*34);~i',
            '&$1;',
            $html,
        ) ?? $html;

        return trim($html);
    }

    /**
     * Rewrite legacy relative asset paths inside stored HTML to absolute
     * /storage/... URLs.
     *
     * This is the documented exception to the disk-relative rule: inline images
     * inside HTML bodies are rewritten once at import time and rendered as-is.
     *
     * @param  array<string, string>  $map  legacy prefix => new prefix
     */
    public static function rewriteAssetPaths(?string $html, array $map): string
    {
        $html = (string) $html;

        if ($html === '') {
            return '';
        }

        foreach ($map as $from => $to) {
            $from = trim($from, '/');

            // src="images/x.jpg", src="/images/x.jpg", src='images/x.jpg'
            $html = preg_replace(
                '~(\b(?:src|href)\s*=\s*["\'])/?'.preg_quote($from, '~').'/~i',
                '$1'.$to.'/',
                $html
            ) ?? $html;
        }

        return $html;
    }

    /**
     * Unserialize a PHP serialized string, returning null instead of throwing.
     * Guarded against object injection.
     */
    public static function unserializePhp(?string $s): mixed
    {
        $s = (string) $s;

        if ($s === '' || ! preg_match('/^[aOsbdi]:/', $s)) {
            return null;
        }

        $value = @unserialize($s, ['allowed_classes' => false]);

        return $value === false && $s !== 'b:0;' ? null : $value;
    }

    /**
     * First N characters of plain text, for excerpts and meta descriptions.
     */
    public static function excerpt(?string $html, int $length = 160): string
    {
        $text = html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim(preg_replace('~\s+~u', ' ', $text) ?? '');

        return Str::limit($text, $length, '…');
    }

    /** Normalize a Joomla datetime, treating its null-date as null. */
    public static function date(?string $value): ?string
    {
        if (blank($value) || str_starts_with($value, '0000-00-00')) {
            return null;
        }

        return $value;
    }
}
