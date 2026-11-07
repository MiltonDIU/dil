<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serve the Phase 0 URL map as 301s.
 *
 * Two cases, and both matter:
 *
 *  1. `/index.php/...` — Joomla ran with sef_rewrite off, so every indexed URL
 *     carries that prefix. Laravel strips `/index.php` when matching routes, so
 *     these would otherwise resolve to a 200 and the same page would be live at
 *     two addresses. We therefore redirect them BEFORE dispatch, which is what
 *     actually consolidates the ranking rather than splitting it.
 *
 *  2. Everything else is checked only AFTER a 404, so a live route always wins
 *     and the redirect table can never shadow real content.
 */
class HandleLegacyRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET')) {
            return $next($request);
        }

        $uri = $request->getRequestUri();

        // Case 1 — legacy /index.php URLs, resolved before dispatch.
        if (str_starts_with($uri, '/index.php')) {
            if ($response = $this->lookup($request, $uri)) {
                return $response;
            }

            // No mapping, but the prefix is still legacy: send it to the clean
            // path so we never serve the same content at two addresses.
            $clean = substr($uri, strlen('/index.php')) ?: '/';

            if (str_starts_with($clean, '/')) {
                return $this->to($request, $clean, 301);
            }
        }

        $response = $next($request);

        // Case 2 — anything else, only once nothing real matched.
        if ($response->getStatusCode() === 404) {
            return $this->lookup($request, $uri) ?? $response;
        }

        return $response;
    }

    private function lookup(Request $request, string $uri): ?RedirectResponse
    {
        $redirect = Redirect::query()
            ->published()
            ->where('source_hash', hash('sha256', Redirect::normalize($uri)))
            ->first();

        if (! $redirect) {
            return null;
        }

        // Cheap analytics so staff can see which legacy URLs are still in use.
        $redirect->forceFill([
            'hits' => $redirect->hits + 1,
            'last_hit_at' => now(),
        ])->saveQuietly();

        return $this->to($request, $redirect->target, $redirect->status_code);
    }

    /**
     * Build the destination from scheme + host + path explicitly.
     *
     * Using url()/redirect() here would inherit the current request's base
     * path, which for an `/index.php/...` request means the redirect target
     * comes back out as `/index.php/new-path` — sending the visitor straight
     * back into the legacy URL space.
     */
    private function to(Request $request, string $target, int $status): RedirectResponse
    {
        if (! str_starts_with($target, 'http://') && ! str_starts_with($target, 'https://')) {
            $target = rtrim($request->getSchemeAndHttpHost(), '/').'/'.ltrim($target, '/');
        }

        return new RedirectResponse($target, $status);
    }
}
