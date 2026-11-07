# SKILLS.md — the DILC front end

How the public site is built, why it is built that way, and what will bite you
if you change it. `CLAUDE.md` covers the migration and the data model; this file
covers everything a visitor can see.

Companion docs: **EDITING-GUIDE.md** (for staff), **PRODUCTION.md** (deploying).

---

## 1. Brand

DILC is part of Daffodil International University, so the design language is
sampled from **daffodilvarsity.edu.bd** rather than invented. If you are ever
unsure what something should look like, open the parent site and copy it.

| Token | Value | Where DIU uses it |
|---|---|---|
| `sky-900` | `#002652` | every full-bleed navy section, and all headings |
| `sky-700` | `#09509e` | the blue their gradients and glows are built from |
| `sky-600` | `#034ea2` | primary buttons, links, active states |
| `emerald-500` | `#22a139` | the DIU green — accents only, never a surface |
| `emerald-600` | `#189030` | the DILC logo green, kept as the deeper step |
| `slate-50` | `#f3f4f9` | the light section background between navy bands |

Typeface: **Manrope**, DIU's, self-hosted.

### The colours are a scale remap, not hex in templates

`resources/css/app.css` `@theme` overwrites Tailwind's own `sky-*` and
`emerald-*` ramps. Every `text-sky-900` in the markup is therefore DIU navy, and
a rebrand is a change to that one block — nothing else in the project holds a
colour. `slate-50` is remapped too, so `bg-slate-50` is DIU's section grey.

On top of that, the layout emits `--brand-primary` / `--brand-secondary` /
`--brand-accent` / `--brand-footer-bg` from the `settings` table, so an editor
can override colours from the admin with no rebuild. The `@theme` values are the
fallback.

### The font is wired through the Vite fonts manifest

`vite.config.js` declares `bunny('Manrope', { weights: [400, 500, 600, 700, 800] })`,
and **`{{ Vite::fonts() }}` in the layout `<head>` is what emits the
`@font-face` block and the preloads**.

> This call is load-bearing and easy to lose. Without it the `@theme
> --font-sans` value never resolves and the entire site silently falls back to
> the system UI font — which is exactly how it shipped before this pass. Nothing
> errors; it just quietly looks wrong.

### The design vocabulary

In `@layer components`, so a button is the same button everywhere:

| Class | Use |
|---|---|
| `.btn-primary` | the main action, on a light background |
| `.btn-invert` | the same action on navy — white pill, navy label |
| `.btn-outline` | secondary action on navy or over a photo |
| `.btn-quiet` | tertiary, bordered, on light |
| `.card` | bordered white panel that lifts on hover |
| `.section` | the standard `max-w-7xl` + responsive vertical rhythm |
| `.eyebrow` | small uppercase kicker with the green rule before it |
| `.link-arrow` | DIU's label-plus-arrow link; the arrow steps on hover |

`.btn` itself is declared with **`@utility`, not as a plain class**. In
Tailwind 4 only utilities are visible to `@apply`, so a `.btn` defined inside
`@layer components` makes `@apply btn` fail the build with *"Cannot apply
unknown utility class"*. If you add another shared base that variants build on,
declare it the same way.

> **A literal `&amp;` on the page is data, not a template.** Joomla stored some
> bodies double-encoded (`&amp;amp;`), which renders as `&amp;` through
> `{!! !!}`. `Text::cleanHtml()` now peels one layer on import, and the four
> affected rows have been repaired in place. If it reappears, look in the
> database, not in Blade.

---

## 2. The hero carousel

`resources/views/blocks/carousel.blade.php` + `resources/js/carousel.js`,
registered as `Alpine.data('carousel', …)`.

### The horizontal movement is the browser's, not ours

The track is a `scroll-snap-type: x mandatory` container and each slide is a
`w-full shrink-0 snap-start` child. That is the central decision.

Touch swipe, trackpad gestures, momentum, per-pixel dragging, rubber-banding at
the ends and mid-gesture direction changes are then **the platform's**, on every
device, and behave the way every other scrollable surface on the phone behaves.
None of it can be broken by a pointer event we forgot to handle. A hand-rolled
`pointerdown`/`pointermove` drag can match this on a good day and cannot match
it on a bad one.

JavaScript is left with only what scroll-snap cannot express: autoplay, the
buttons, and telling assistive tech which slide is showing.

**`onScroll()` derives the live index from `scrollLeft`**, so a swipe, an arrow
press, a dot click and an autoplay tick all converge on one source of truth. The
dots can never disagree with the picture on screen.

### Autoplay stops when nobody is looking

`get running()` is the whole policy. Autoplay runs only when **every** one of
these is false:

| Flag | Set by |
|---|---|
| `paused` | reserved — no UI drives it, see below |
| `hovered` | `@mouseenter` / `@mouseleave` on the section |
| `focused` | `@focusin` / `@focusout` — keyboard focus inside it |
| `tabHidden` | `visibilitychange` — the tab went to the background |
| `offscreen` | an `IntersectionObserver` at `threshold: 0.4` |
| `settling` | any scroll in flight, cleared 240 ms after it stops |

…and `reduced` (from `prefers-reduced-motion`) is false, and there is more than
one slide. Under reduced motion the carousel becomes a manual one: it never
advances by itself and `scrollTo` uses `behavior: 'auto'`.

There is deliberately **no play/pause button**. Five of the six flags above stop
autoplay without anyone asking, so the control was offering a choice the
carousel already makes on its own. `paused` stays on the component for anything
that wants to drive it programmatically.

Every listener is registered through `listen()`, which pushes its own removal
onto `teardown`, and `destroy()` runs them all. The observer disconnects, the
rAF is cancelled, the timeout is cleared.

### The progress bar

The filling segment is driven by a `--dwell` custom property (0 → 1) written
**straight to the DOM** once per frame:

```js
this.$el.style.setProperty('--dwell', Math.min(this.elapsed / this.interval, 1))
```

Deliberately not an Alpine binding — a reactive property mutating 60 times a
second is the one thing in this component that would actually cost something.
CSS reads it in `.hero-dwell { transform: scaleX(var(--dwell)) }`.

### `is-live` is applied only by Alpine

The class that triggers the slow image push and the copy entrance is set by
`:class="i === N && 'is-live'"` and **must never also appear in the markup**.
`x-bind:class` only takes back the classes it added itself, so a server-rendered
copy can never be removed again — the first slide stays `is-live` forever and
two slides animate at once. This was a real bug here; it is not theoretical.

### Editor-facing knobs

Slides come from the `slides` table (Appearance → Slider). Per slide:

- `subtitle` → the green eyebrow
- `title` → the headline
- `caption` → the standfirst
- `link_url` + `link_text` → the CTA pill

A slide with none of these renders as a clean full-bleed photo — the side scrim
is suppressed so nothing darkens a picture with no words on it. The foot scrim
always stays, because the control bar sits on it.

The block's `data.interval` overrides the 6500 ms dwell; `data.group` picks
which slide group to show.

---

## 3. Layout and responsive rules

- Page shell: `body` is `flex min-h-screen flex-col`, `main` is `flex-1`, the
  footer is `mt-auto`. Short pages still push the footer to the bottom.
- Content width is `max-w-7xl` with `px-4 sm:px-6`. Use `.section` unless you
  need something else.
- Breakpoints in use: `sm` 640 (two-column cards, the hero counter appears),
  `lg` 1024 (desktop nav replaces the hamburger, three-column grids).
- Hero height is `h-[clamp(20rem,52vw,34rem)]` — one fluid rule instead of three
  fixed step-downs.
- **Wide things scroll inside themselves, never the page.** `.content-html` is
  `overflow-x-auto`; stored map/video `<iframe>`s are forced into a responsive
  box with `[&_iframe]:aspect-[4/3] [&_iframe]:h-auto [&_iframe]:w-full`,
  because the embeds carry their own fixed pixel width.

### Never interpolate a class name

```blade
{{-- broken: compiles to a PHP echo Tailwind can never see --}}
<div class="lg:grid-cols-{{ min(count($cards), 4) }}">
```

Tailwind ships only classes it can find as literal text. The class above is
simply absent from the bundle, and the layout silently falls back to whatever
the previous breakpoint said — a four-card row that had been rendering as two
for as long as it existed. Look the literal up from a map:

```blade
@php($columns = [1 => 'lg:grid-cols-1', 2 => 'lg:grid-cols-2',
                 3 => 'lg:grid-cols-3', 4 => 'lg:grid-cols-4'][min(count($cards), 4)])
```

`blocks/feature_cards.blade.php` and `pages/home.blade.php` both do this.

---

## 4. Forms

One renderer (`partials/form.blade.php` + `partials/form-field.blade.php`), one
handler (`FormController`), and everything else derived from `form_fields`
rows — an editor adding a field in Filament gets a working, validated,
accessible control with no deploy.

### Required and optional

`FormField::rules()` starts every field with `required` or `nullable` from its
own `required` column, so the client hint and the server rule cannot drift.

In the markup:

- required → the native `required` attribute (which assistive tech announces on
  its own) **and** a red `*` as the sighted cue
- optional → the word **(optional)** beside the label, because "no asterisk" is
  not something anyone notices
- a legend above the fields explains the `*`, rendered only when the form
  actually has a required field

Currently: **contact** — name, email, message required; phone, subject optional.
**course-enquiry** — name, email, phone, course required; message optional.

### The `course` dropdown builds itself

`FormField::resolvedOptions()` reads `options_source === 'courses'` and queries
published courses live. Publishing a course adds it to the enquiry dropdown; the
`Rule::in()` in `rules()` is built from the same list, so a stale option cannot
be submitted.

### Validation messages take the label as written

`Form::validationAttributes()` returns the editor's label **verbatim**, and
`Form::validationMessages()` phrases the common rules around it:

```
:attribute is required.   →   "Your name is required."
```

Lower-casing the label — which is what it used to do — produced *"The your name
field is required."* If you add a rule that visitors will actually trip over,
add a message for it too.

### Accessibility wiring

| Concern | How |
|---|---|
| error text | `aria-describedby` → `#field-…-error` |
| help text | `aria-describedby` → `#field-…-help` |
| invalid state | `aria-invalid` + a red border, on every control type |
| radio / checkbox groups | `role="radiogroup"` / `role="group"` + `aria-labelledby` pointing at the label element |
| the error summary | `role="alert"`, `tabindex="-1"`, focused on arrival |

Old input is repopulated on failure, including for the optional fields.

### Spam and abuse

A honeypot `website` field (bots fill it; success is faked so they learn
nothing), plus `throttle:20,1` on the route and a per-IP `RateLimiter` at 5
submissions a minute.

---

## 5. Navigation

Built from `menu_items`, never hardcoded. `partials/menu-item.blade.php` renders
one item and its dropdown.

- **Desktop dropdowns open on hover *and* focus**, and close on Escape. Hover
  alone leaves the submenu unreachable without a pointing device.
- **Mobile submenus start closed.** A parent with children is a disclosure: the
  label follows the parent link, the chevron beside it toggles. Rendering them
  all expanded turned the menu into a 30-item scroll — Courses alone has ten
  children — and buried the top-level items.
- Items with no destination (`type = 'none'`) are `<button>`s when they have
  children and inert text otherwise — never `href="#"`.
- **Active matching is exact, or a real path segment below.** The naive test
  `fullUrlIs(url($url).'*')` marks Home current on every page, because for `/`
  that pattern is the bare origin followed by a wildcard. The current page also
  gets `aria-current="page"`.

---

## 6. Motion

Everything animated is decoration on content that is already readable without
it, which is what makes the reduced-motion block at the bottom of `app.css`
safe: it sets the end state immediately rather than degrading anything.

Covered there: the hero image push, the copy entrance, card and button lift, and
`html { scroll-behavior }`. The carousel's own JS checks the same media query
and keeps listening for changes, so toggling the OS setting takes effect without
a reload.

Shared easing is `--ease-out-brand: cubic-bezier(0.22, 0.61, 0.36, 1)`.

---

## 7. Traps

Things that fail silently. All five were live bugs found while building this.

1. **`{{ Vite::fonts() }}` missing** → no webfont at all, no error.
2. **Interpolated class names** → the class is not in the bundle.
3. **`@apply` on a `@layer components` class** → build fails, *"Cannot apply
   unknown utility class"*. Use `@utility`.
4. **Mixing `@php(…)` and `@php … @endphp` in one Blade file** → the inline
   assignments do not run and you get *"undefined variable"* at render. Use one
   `@php … @endphp` block.
5. **A statically-rendered class that `x-bind:class` also controls** → Alpine
   can never remove it.

---

## 8. Verifying a change

```bash
npm run build              # Tailwind only ships classes it can see — always rebuild
php artisan view:clear     # compiled Blade is cached
php artisan test           # 34 tests: every admin screen, every public route
./vendor/bin/pint          # style
```

Then look at it. At minimum: the homepage, `/courses`, an article, and
`/contact` with the form submitted empty — at a phone width and a desktop width.
For the carousel specifically, check that it stops when you hover it, when you
focus a button inside it, and when you scroll it out of view.
