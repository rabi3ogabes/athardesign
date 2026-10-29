# أثر لتصميم الويب — Athar Web Design

Bilingual (Arabic / English) luxury agency website with a full admin panel.
Built with **plain PHP 8.1+** (no framework, no Composer) on **MySQL 8 / MariaDB 10.4+**.
Runs on any ordinary PHP hosting (cPanel, Plesk, DirectAdmin, a VPS with Apache or Nginx).

## What is inside

| Area | Highlights |
|---|---|
| Public website | Arabic-first (RTL) with an English version at `/en`, block-based pages, services, portfolio, testimonials, contact form, GSAP motion, custom 404, maintenance mode |
| SEO | Per-page title / description / keywords / OG image / canonical / noindex, `hreflang`, JSON-LD (Organization, Service, CreativeWork, FAQ, Breadcrumbs), `sitemap.xml`, editable `robots.txt`, 301 redirects, SEO health score, SEO task board |
| Analytics | Every visitor and page view (device, browser, OS, country/city, referrer, UTM) and every click on WhatsApp / phone / email / social / CTA links, with a visitor timeline. No cookies banner needed: first-party cookie only |
| Admin panel | Dashboard with charts, visitors, clicks, enquiries, page builder (17 block types), services, portfolio + categories, testimonials, media library (auto WebP), settings (site, **appearance / themes**, contact & WhatsApp, social links, SEO, analytics, mail), users & roles, activity log, Arabic/English UI |
| Packages | Silver / Elite / Premium tiers per service line (combined website + social media by default; website, social, SEO, photography lines available). Each card has price, delivery time, included / excluded items, a detail page with Product + Offer JSON-LD, and an **Order on WhatsApp** button that opens WhatsApp with the package name and price already written |
| Email marketing | SMTP senders (several, rotated within hourly/daily limits), contact lists with import + verification scoring, HTML templates, campaigns with an inbox score (SPF / DKIM / DMARC / content checks), DKIM signing, List-Unsubscribe one-click, open / click / unsubscribe tracking and bounce suppression |
| Appearance | Six theme presets (Ink & brass, Ivory, Doha sand, Oud, Emerald, Midnight), custom accent colour, serif / sans / **custom uploaded typeface** (e.g. Lusail) for headlines and optionally body text, soft or sharp corners |
| Brand | Square-Kufi أثر mark (brass letters, ivory pixels) with Arabic/English lockups, light and one-colour versions, favicon + app icons and the social preview image in `public/assets/img/`; masters, test sheet, board and guidelines in `design/logo/` |
| Installer | `/install` sets everything up from the browser: server check → database → first admin. No SSH needed |

## Folder layout

```
public/            ← web root (index.php, assets, uploads, .htaccess)
app/
  Core/            ← router, MySQL data layer (Db, Rpc), installer, auth, i18n, SEO, blocks, analytics…
  Controllers/     ← Site/* (public), Admin/* and the InstallController
  Models/          ← thin table wrappers (cached for the public site)
  Views/           ← site/ (layout, templates, blocks/), admin/ and install.php
  lang/            ← ar.php / en.php interface strings
database/
  mysql/schema.sql ← all tables (MySQL 8 / MariaDB 10.4+), safe to re-run
  seed.php         ← starter content (pages, services, packages, SEO tasks)
  legacy-supabase/ ← the previous PostgreSQL/Supabase schema, kept for reference only (do not upload)
storage/           ← cache, logs and installed.lock (must be writable)
tools/             ← check.php, migrate.php, make-admin.php, mail-worker.php
```

## 1. Upload to your server (cPanel / Plesk / any PHP host)

1. **Create the database**: hosting panel → *MySQL Databases* → create a database and a user, give the user **ALL PRIVILEGES** on it. Note the three values (database name, user, password). On cPanel they look like `account_athar`.
2. **Upload the project** (everything except `database/legacy-supabase/`, `shots/` and `design/`, which are not needed online).
   * Best: point the domain's **document root to the `public/` folder**.
   * If the panel does not allow that (typical shared cPanel with `public_html`), upload the whole folder *into* `public_html`: the root `.htaccess` forwards every request into `public/` and blocks `app/`, `storage/`, `database/` and `tools/`.
3. **PHP 8.1 or newer** with the extensions `pdo_mysql`, `mbstring`, `gd`, `curl`, `fileinfo`, `json` (cPanel → *Select PHP Version*). Make `storage/` and `public/uploads/` writable (755 is enough when PHP runs as your user; otherwise 775).
4. Open **`https://your-domain/install`** in the browser. The installer:
   * checks PHP, extensions and folder permissions;
   * asks for the database details, creates all tables and the starter content (pages, services, packages, SEO tasks), and writes `.env` with a random `APP_SECRET`;
   * creates the first admin (owner) account and signs you in.
   The installer locks itself afterwards (`storage/installed.lock`) and `/install` disappears.
5. Done. Admin panel: `https://your-domain/admin`.

Nothing else to configure: no API keys, no external services. All data lives in your MySQL database and uploads in `public/uploads/`.

### Moving an existing installation to another server

Export the database (phpMyAdmin → Export), copy the project folder including `public/uploads/` and `.env`, import the SQL dump on the new server, then adjust the `DB_*` values and `APP_URL` in `.env`. The installer notices the existing admin account and stays locked.

### Command line alternative (VPS / SSH)

```bash
cp .env.example .env            # fill in DB_* and APP_URL
php tools/migrate.php --seed    # tables + starter content
ATHAR_ADMIN_EMAIL=you@example.com ATHAR_ADMIN_PASSWORD='long secret' php tools/make-admin.php --name="Your name" --role=owner --lang=ar
php tools/check.php             # must end with "All checks passed"
```

### Nginx

```nginx
server {
    server_name athar.qa;
    root /var/www/athar/public;
    index index.php;
    location / { try_files $uri /index.php?$query_string; }
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
    location ~* \.(woff2|css|js|svg|png|jpg|jpeg|webp|gif|avif|ico)$ { expires 1y; add_header Cache-Control "public, immutable"; }
    location ~ /uploads/.*\.php$ { deny all; }
}
```

## 2. Configuration (`.env`)

Written by the installer; edit by hand only when something changes.

| Key | Meaning |
|---|---|
| `APP_URL` | Public URL without trailing slash (`https://athar.qa`). Empty = auto-detect |
| `APP_ENV` / `APP_DEBUG` | `production` / `false` online. `APP_DEBUG=true` prints stack traces: never on a live site |
| `APP_TIMEZONE` | Dashboard timezone (`Asia/Qatar`). Timestamps are stored in UTC |
| `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` | MySQL connection (`DB_SOCKET` for a unix socket) |
| `APP_SECRET` | Random 32+ chars; signs the mail-cron URL and tokens |
| `CACHE_TTL` | File cache lifetime for public content (seconds); cleared on every admin save |
| `ADMIN_PATH` | Path of the admin panel (default `/admin`) |

## 3. Email campaigns cron

Campaigns are sent in batches by a worker. Add one of these in the hosting panel:

```
* * * * * php /home/account/public_html/tools/mail-worker.php >> /home/account/public_html/storage/logs/mail-worker.log 2>&1
```

Hosts without shell cron can call the URL printed by `php tools/mail-worker.php --url` (or found in Admin → Campaigns) every minute instead.

## 4. Appearance

Settings → Appearance (or the colour dots on the dashboard) switches the whole website theme instantly; the header logo picks the light variant automatically on light themes. Upload your own logo in Settings → General to override the built-in one.

**Custom typeface (e.g. Lusail):** in Settings → Appearance upload the regular (and optionally bold) font file you hold a licence for (woff2 / woff / ttf / otf, up to 5 MB), set *Headline typeface* to **Custom**, and tick *Also use it for body text* if you want it everywhere.

## 5. Packages & WhatsApp ordering

Admin → Packages lists every tier grouped by service line. The public page `/packages` (editable in Pages, block "Packages & pricing") shows the combined *Website + social media* line by default. Each package's WhatsApp text is generated from a template with `{site} {package} {tier} {category} {price} {price_line} {url}` placeholders; the number comes from Settings → Contact. Clicks are recorded in Analytics.

## 6. After going live (SEO)

1. Settings → SEO: paste the Google Search Console verification code and the GA4 id.
2. Submit `https://your-domain/sitemap.xml` in Search Console.
3. Settings → Contact & WhatsApp: WhatsApp number (international format, digits only), phone, email, address, map link.
4. Settings → Social media: profile links. Empty links are hidden automatically.
5. Add real project photos in Portfolio and alt text in the media library.
6. Work through **SEO → SEO tasks**: the board is pre-filled with the launch checklist.

## 7. Local development

XAMPP / Laragon / any local MySQL works. Create an empty database, then either open `http://localhost:8080/install` or set the `DB_*` values in `.env` and run `php tools/migrate.php --seed`.

```bash
php -S localhost:8080 -t public tools/serve-router.php
```

## Data layer notes (for developers)

* `App\Core\Db` is a small PDO query builder: `Db::from('pages')->eq('slug', 'home')->first()`, `->insert()`, `->update()`, `->upsert()`, `->insertIgnore()`, `->getWithCount()`, `->or('name.ilike.*x*,email.ilike.*x*')`, and `select('*,category:project_categories(id,slug,name)')` to attach a related row.
* Rows come back ready to use: JSON columns as arrays, `TINYINT(1)` as booleans, `DATETIME` as ISO-8601 UTC strings, `DECIMAL` as numbers. UUIDs are generated in PHP.
* Aggregations that used to be database functions (dashboard overview, visitor timeline, tracking, SEO health, campaign stats, queue building) live in `App\Core\Rpc` and are called through `Db::rpc('name', [...])`.
* Every JSON column in `database/mysql/schema.sql` carries `COMMENT 'json'` so the driver decodes it on MariaDB too (where `JSON` is an alias of `LONGTEXT`). Keep that comment when adding columns.

## Security notes

* All admin forms are CSRF-protected; sessions are HttpOnly + SameSite=Lax; login is throttled per IP.
* Every query is a prepared statement; the application never builds SQL from user input.
* Uploaded images are re-encoded with GD (strips scripts/metadata); SVGs containing scripts are rejected; `uploads/` cannot execute PHP.
* `.env`, `storage/`, `app/`, `database/` and `tools/` are never reachable from the web (outside the document root, or blocked by the root `.htaccess`).
* A database outage answers HTTP 503 with `Retry-After`, so search engines keep the pages indexed.
* `storage/logs/` holds the application log when something fails.
