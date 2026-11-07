# CLAUDE.md — DILC (Laravel + Filament)

Daffodil Institute of Languages & Culture, migrated from **Joomla 5.4.7**
(table prefix `a0cbr_`, Helix Ultimate template, SP Page Builder).

> ## ⚠️ The migration has already been applied
>
> `php artisan legacy:migrate` is a **development-only** tool. It has been run,
> its output verified, and the finished database shipped.
>
> **Never run it — or any `legacy:*` command — against production.**
> Doing so overwrites whatever editors have changed since launch. The
> orchestrator refuses when `APP_ENV=production`, but that is a backstop, not
> permission.
>
> The legacy Joomla database is also **read-only** and enforced in two layers
> (see *Legacy connection* below). Do not weaken either one.

---

## Stack

| | |
|---|---|
| Laravel | 13 |
| Filament | 5 (panel at `/admin`) |
| Tailwind | 4 |
| PHP | **8.3** — pinned in `composer.json`. The dev machine runs 8.5; do **not** use 8.4+ syntax |
| Alpine.js | nav dropdowns, mobile menu, slider |

## Databases

| Connection | Purpose |
|---|---|
| `mysql` | the application (`dilnew` locally) |
| `legacy` | the Joomla source. **READ ONLY.** Used only by `legacy:*` commands |

Both live in the Docker MySQL on `127.0.0.1:3307`. Credentials are in `.env`.

### Legacy connection is read-only, twice over

1. **Database grant** — the `dil_legacy_ro` user has `SELECT` only. This is the
   real enforcement.
2. **`LegacyGuardServiceProvider`** — a `beforeExecuting` hook rejects any
   non-SELECT statement on the `legacy` connection before it is sent.

The guard hooks `beforeExecuting`, **not** the `StatementPrepared` event.
`Connection::affectingStatement()` (UPDATE/DELETE) and `statement()`
(INSERT/DDL) never call `prepared()`, so `StatementPrepared` only ever sees
SELECTs and cannot guard writes. This was found the hard way.

---

## Path conventions

**Uploads are stored disk-relative on the `public` disk** (`media/slides/x.jpg`),
and read through a `*_url` accessor:

```php
$slide->image        // media/slides/x.jpg   — what is in the column
$slide->image_url    // /storage/media/...   — what Blade and Filament use
```

- Blade and Filament `ImageColumn` use the accessor **only**, never the raw column.
- `FileUpload->disk('public')->directory('media/...')` round-trips with this.
- **Do not** add `formatStateUsing` / `dehydrateStateUsing` prefix hacks to
  `FileUpload` — they break upload previews.
- **Exception:** inline `<img>` inside stored HTML is rewritten to an absolute
  `/storage/media/...` path at import time and rendered as-is.

`App\Models\Concerns\HasUploadUrls` provides `uploadUrl()` and `uploadMissing()`.

---

## Everything is dynamic

Nothing an editor might change lives in Blade:

| Thing | Table | Managed at |
|---|---|---|
| Site name, logo, contacts, socials, colours, analytics | `settings` | Appearance → Site settings |
| Navigation | `menus` + `menu_items` | Appearance → Menus (relation manager) |
| Homepage sections | `blocks` | Appearance → Homepage blocks |
| Slider | `slides` | Appearance → Slider |
| Courses, pages, news | `courses`, `pages`, `posts` | Content |
| Forms and their fields | `forms` + `form_fields` | Forms (Repeater builder) |
| 301 redirects | `redirects` | System → Redirects |

Blade reaches all of it through the shared `$site` accessor
(`App\Support\Site`), never through a hardcoded string.

### Theming

Brand colours are applied by **remapping Tailwind scales** in
`resources/css/app.css` `@theme` — every `sky-*` utility is DILC blue, every
`emerald-*` is the accent green. A rebrand is that one block. The layout then
emits `--brand-*` custom properties from `settings`, so an editor can override
colours with no rebuild.

The ramp is sampled from **daffodilvarsity.edu.bd**, so DILC reads as part of
DIU: `#002652` navy (`sky-900`), `#034ea2` primary (`sky-600`), `#22a139` green
(`emerald-500`), `#f3f4f9` section background (`slate-50`). The typeface is
**Manrope**, DIU's, self-hosted through the Vite fonts manifest.

> `{{ Vite::fonts() }}` in the layout head is what emits the `@font-face` block.
> Without it the `@theme --font-sans` value never resolves and the whole site
> silently falls back to the system UI font — which is how it shipped.

Shared vocabulary lives in `@layer components`: `.btn-primary` / `.btn-invert` /
`.btn-outline` / `.btn-quiet`, `.card`, `.section`, `.eyebrow`, `.link-arrow`.
`.btn` itself is an `@utility`, because in Tailwind 4 only utilities can be
`@apply`-ed by another rule.

**Never interpolate a class name** — `lg:grid-cols-{{ $n }}` compiles to a PHP
echo Tailwind can never match, so the class is not in the bundle. Look the
literal up from a map instead (`blocks/feature_cards`, `pages/home` both do).

Stored HTML renders inside `.content-html`, **not** Tailwind `prose` —
typography utilities fight the inline styles the legacy TinyMCE editor emitted.

### The hero carousel

`blocks/carousel.blade.php` + `resources/js/carousel.js` (`Alpine.data('carousel')`).

The horizontal movement is the **browser's own scrolling**, snapped with
`scroll-snap-type: x mandatory`. Swipe, trackpad, momentum, per-pixel dragging
and rubber-banding are therefore the platform's, on every device, and cannot be
broken by a pointer event we forgot to handle. JavaScript only adds what
scroll-snap cannot express: autoplay, the buttons, and the slide announcement.
`onScroll()` derives the live index from `scrollLeft`, so the dots can never
disagree with the picture on screen.

Autoplay is suspended while **any** of `paused`, `hovered`, `focused`,
`tabHidden`, `offscreen`, `settling` is true, and never starts under
`prefers-reduced-motion`. The progress bar is driven by a `--dwell` custom
property written straight to the DOM once per frame — deliberately not an
Alpine binding.

`is-live` is applied **only by Alpine**. A copy of it in the markup could never
be removed again, because `x-bind:class` only takes back the classes it added.

---

## Migration architecture

Mappings are config, not code: `config/migration/*.php`, one file per entity.

```
config/migration.php          shared: connection, media roots, state map
config/migration/users.php    legacy column => target attribute + casts
config/migration/pages.php    …courses, posts, menus, blocks, slides, settings
```

One idempotent command per entity, all keyed on `(legacy_source, legacy_id)`
via `updateOrCreate`, all re-runnable at any time:

```bash
php artisan legacy:migrate                      # everything, in dependency order
php artisan legacy:migrate --only=courses       # one step
php artisan legacy:migrate --with-ai            # plus the Gemini commands
php artisan legacy:migrate --skip-media
php artisan legacy:verify --url=http://127.0.0.1:8000
```

Shared helpers live in `App\Support\Text` (`fixMojibake`, `slug`, `uniqueSlug`,
`cleanHtml`, `rewriteAssetPaths`, `unserializePhp`, `excerpt`).

### Things that will bite you

- **Hierarchies** (`categories`, `menu_items`) import `legacy_parent_id` first,
  then a **second pass** remaps to local ids. Joomla's nested-set root (id 1)
  is a sentinel, not a real record — its children become top level.
- **One legacy table became three.** `a0cbr_content` splits into `pages`,
  `courses` and `posts`. `MigrateMenus` resolves a legacy article id against
  all three.
- **`MenuItem` has a `saving` hook** that derives `linkable_type` from `type`,
  so the polymorphic pair can never be left half-set.
- **`type = 'none'`** menu items are dropdown parents (Joomla stored them as
  `link = '#'`). They render as non-clickable headings, not dead links.

---

## AI

Gemini is a finishing tool, never the engine. `App\Services\GeminiService`:
structured output via `responseSchema`, exponential backoff, and **returns
`null` on failure so the caller falls back to the raw value**. A failed call
never destroys data or aborts a run.

```bash
php artisan ai:extract-courses --dry-run
php artisan ai:generate-seo
php artisan ai:generate-alt --force
```

- Every command supports `--dry-run` and `--force` and logs a before/after sample.
- Decisions are cached in `ai_mappings`. **Marking a row Reviewed in the admin
  pins it** — re-runs read the cache first and never overwrite a reviewed row,
  so human corrections survive.
- Raw values are always kept beside normalized ones (`body_raw`,
  `language_raw`, `cefr_levels_raw`), and `ai_normalized` / `ai_meta_generated`
  flag what a human still needs to check.
- **The site renders correctly with `GEMINI_API_KEY` unset.**

The homepage was decomposed **deterministically** (7 SP Page Builder addon
types), not by AI. Do not reach for AI where a mapping works.

---

## Testing

```bash
php artisan test
```

32 tests: `AdminPanelTest` renders every admin screen and checks panel gating;
`FrontEndTest` covers routes, redirect behaviour, form round-trip, honeypot,
settings-driven identity and the sitemap.

---

## Deploying

```bash
php artisan deploy:bundle
```

Produces `deploy/` — `release.zip`, `db.sql.gz`, `media.tar.gz`,
`.env.production.example`. Follow **PRODUCTION.md**.

Redeploy rule: **never overwrite `storage/app/public/media`** — that is where
new uploads live.

---

## Known content issues (deliberate, not bugs)

These were carried over unchanged rather than invented. They need the client,
not code:

1. **The About page describes the wrong organisation** — it is text about the
   DIU Office of Students' Affairs, inherited from the site this Joomla install
   was cloned from.
2. **5 published records are empty** and were empty in Joomla too: Team,
   Japanese Language, Bangla Language, Remedial English Course, Various Short
   English Courses. *Bangla Language is linked from the live menu.*
   See the **Needs content** tab on Pages and Courses.
3. **4 courses have no fee** because the legacy copy never stated one.
4. **7 settings are blank** — 5 social links (all `#` placeholders in Helix),
   the default share image, and the analytics ID.
5. **90 broken inline images** exist inside unpublished, legacy-trashed
   articles. Harmless while unpublished; `legacy:verify` fails the run if one
   ever appears in published content.

---

## Deliberately dropped

DJ-ImageSlider (installed but zero rows, unpositioned module, missing image
folder) · the homepage testimonial (JoomShaper demo data, "John Doe") ·
Joomla's `finder_*` search index (6,556 rows) · ACL/workflow/history/log tables
· `dilold/media/` (37 MB of Joomla core JS/CSS) · `images/home/download/`
(18 Department of Student Affairs PDFs) and `logo-dsa.png`.
