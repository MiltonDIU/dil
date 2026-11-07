# PRODUCTION.md — deploying DILC

Runbook for deploying the Daffodil Institute of Languages & Culture site
(Laravel 13 + Filament 5). Written for both **cPanel shared hosting** and a
**VPS**.

> **The one rule that matters most**
>
> Ship the **finished database and media** from `deploy/`.
> **Never run `php artisan legacy:migrate` on the server.**
> The legacy Joomla database has no business on production, and a re-run
> against live data is the one thing that can destroy editor changes.
> The command refuses to run when `APP_ENV=production`, but do not rely on that.

---

## 1. Host requirements

| Requirement | Value |
|---|---|
| PHP | **8.3** (the version this was built and pinned against) |
| Extensions | `bcmath` `ctype` `curl` `dom` `fileinfo` `filter` `gd` `hash` `mbstring` `openssl` `pcre` `pdo` `pdo_mysql` `session` `tokenizer` `xml` `zip` |
| MySQL | 8.0+ (or MariaDB 10.6+) |
| Disk | ~150 MB for the app, ~3 MB for media |
| Node | **not required** — assets are built locally |

Verify the host's PHP version before anything else. If it is not 8.3, stop and
tell the developer: `composer.json` is pinned to `^8.3`.

```bash
php -v
php -m | sort
```

---

## 2. What you are deploying

`deploy/` contains four artifacts, all produced by `php artisan deploy:bundle`:

| File | Contents |
|---|---|
| `release.zip` | app code + `vendor/` + built `public/build/`. Excludes `node_modules`, `.git`, `.env`, tests, media, and the legacy source |
| `db.sql.gz` | the finished schema **and data** — 24 tables, all migrated content |
| `media.tar.gz` | `storage/app/public/media` |
| `.env.production.example` | every key, all secrets blank |

Assets are already built. **Do not** try to run `npm` on shared hosting.

---

## 3. Database

### cPanel
1. **MySQL Databases** → create a database and a user, grant **All Privileges**.
2. **phpMyAdmin** → select the database → **Import** → upload `db.sql.gz`.
   If the file exceeds the upload limit, gunzip it locally and import in parts,
   or use the terminal method below.

### VPS / terminal
```bash
mysql -u USER -p DATABASE < <(gunzip -c db.sql.gz)
```

Confirm the import:
```sql
SELECT COUNT(*) FROM pages;    -- expect 15
SELECT COUNT(*) FROM courses;  -- expect 12
SELECT COUNT(*) FROM menu_items; -- expect 24
SELECT COUNT(*) FROM redirects;  -- expect 55
```

---

## 4. Files

Upload and extract `release.zip`. Where it goes depends on the hosting pattern.

### Pattern A — you can move the document root (VPS, or cPanel add-on domain)

Extract to `/home/USER/dilc`, then point the domain's document root at
`/home/USER/dilc/public`. Nothing else to change. **Prefer this.**

### Pattern B — the document root is fixed at `public_html` (typical cPanel)

1. Extract the release to `/home/USER/dilc` (**outside** `public_html`).
2. Move the contents of `dilc/public/` into `public_html/`.
3. Edit **`public_html/index.php`** and fix the two require paths:

```php
// before
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

// after
require __DIR__.'/../dilc/vendor/autoload.php';
$app = require_once __DIR__.'/../dilc/bootstrap/app.php';
```

Those two lines are the whole of Pattern B. If the site shows a blank page or
"Failed opening required", they are wrong.

---

## 5. Environment

Copy `.env.production.example` to `.env` in the **application** root (not
`public_html`) and fill in:

```dotenv
APP_NAME="Daffodil Institute of Languages & Culture"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://dilc.daffodilvarsity.edu.bd    # the real HTTPS URL

DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS="diloffice@daffodilvarsity.edu.bd"
```

Then generate the key:
```bash
php artisan key:generate
```

> `APP_URL` **must** match the real HTTPS URL. If it does not, admin login
> fails with a 419 page-expired error. After changing it, re-run
> `php artisan config:cache`.

Leave every `LEGACY_*` and `GEMINI_*` line commented out. Production does not
need them, and the site renders correctly without Gemini — every AI-assisted
field has a raw counterpart stored beside it.

---

## 6. Media

```bash
tar -xzf media.tar.gz -C storage/app/public/
```

This creates `storage/app/public/media/`. Then link it:

```bash
php artisan storage:link
```

### If symlinks are disabled (some shared hosts)

`storage:link` will fail. Instead, copy the directory into the web root and add
a redeploy note for whoever comes next:

```bash
cp -r storage/app/public/media public_html/storage/media
```

New uploads then land in `storage/app/public/media` but are served from
`public_html/storage/media`, so the copy must be repeated after content
changes. Ask the host to enable symlinks if at all possible — it removes this
whole class of problem.

> **Redeploy rule: never overwrite `storage/app/public/media`.**
> That is where new uploads from the admin live. Extract `media.tar.gz` on the
> first install only.

---

## 7. Permissions

```bash
chmod -R 775 storage bootstrap/cache
```

On cPanel the owner is usually correct already. On a VPS:
```bash
chown -R www-data:www-data storage bootstrap/cache
```

---

## 8. Caches

Run these **after** `.env` is final:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:optimize
```

Any later `.env` change requires `php artisan config:clear` followed by
`config:cache`, or the change is silently ignored.

---

## 9. Admin access

Two administrators were migrated from Joomla and **kept their existing
passwords** (their hashes were bcrypt, which Laravel accepts unchanged):

| Name | Email | Username |
|---|---|---|
| Muntachir Razzaque | web@daffodilvarsity.edu.bd | web |
| DIU Press | library12@daffodilvarsity.edu.bd | support |

To create another admin, or reset a password:

```bash
php artisan tinker
>>> $u = App\Models\User::create([
...   'name' => 'Name', 'email' => 'name@example.com',
...   'password' => 'a-strong-password', 'is_admin' => true,
... ]);
```

The panel is at **`/admin`**. Only users with `is_admin = 1` and
`blocked = 0` can sign in; everyone else gets a 403.

---

## 10. Go-live checklist

- [ ] `php -v` reports 8.3
- [ ] Database imported; the four row counts in §3 match
- [ ] `.env` has the real `APP_URL` with `https://`, `APP_DEBUG=false`
- [ ] `php artisan key:generate` has been run
- [ ] Media extracted and `storage:link` succeeded (or the §6 fallback applied)
- [ ] `chmod -R 775 storage bootstrap/cache`
- [ ] All four cache commands run
- [ ] Homepage loads with the slider, welcome message, course cards and map
- [ ] `/courses` lists 12 courses with fees
- [ ] `/admin` login works for a real admin
- [ ] Submit the contact form; the submission appears under **Forms → Submissions**
      and the notification email arrives
- [ ] Old URL test: `https://DOMAIN/index.php/about` returns **301** to `/about`
- [ ] `https://DOMAIN/sitemap.xml` renders
- [ ] Submit the sitemap in Google Search Console
- [ ] Fill in the 7 blank settings (5 social links, share image, analytics ID)
- [ ] Ask the client to replace the **About** page text — see the note below

> **Known content issues carried over deliberately.** The About page still
> contains text about the DIU *Office of Students' Affairs*, inherited from the
> site this Joomla install was cloned from. It was migrated unchanged rather
> than invented; the client needs to supply correct copy. Five published pages
> and courses were already empty in Joomla and remain empty — see
> **Pages → Needs content** and **Courses → Needs content** in the admin.

---

## 11. Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| Blank white page | Wrong require paths (Pattern B) | Fix the two lines in `public_html/index.php` (§4) |
| 419 Page Expired on admin login | `APP_URL` does not match the real URL | Correct it, then `config:clear && config:cache` |
| 500 after deploy, no detail | Caches built against the old `.env` | `config:clear`, `route:clear`, `view:clear`, then re-cache |
| Images give 404 | `storage:link` missing or symlinks disabled | §6, including the fallback |
| Unstyled site | `public/build` missing | Build locally and upload `public/build/` |
| "Please provide a valid cache path" | `storage/framework/*` missing | `mkdir -p storage/framework/{cache,sessions,views}` then chmod |
| `SQLSTATE[HY000] [1045]` | Wrong DB credentials | Check `.env`, then `config:cache` |
| Admin loads but no styles | Filament assets stale | `php artisan filament:assets` |
| Form emails never arrive | `MAIL_*` not configured | Set SMTP in `.env`; check **Forms → Submissions** — the submission is stored even when the email fails |
| Course fees show as blank | Legacy copy never stated one | Correct: 4 courses had no fee in Joomla. Edit them in the admin |

---

## 12. Redeploying (code changes only)

```bash
# locally
npm run build
php artisan deploy:bundle --skip-assets
```

On the server:
1. Upload and extract `release.zip` over the app directory.
2. **Do not** import `db.sql.gz` — it would overwrite everything editors have
   changed since launch.
3. **Do not** extract `media.tar.gz` — it would clobber new uploads.
4. `php artisan migrate --force` (only if the release adds migrations).
5. Re-run the four cache commands from §8.
