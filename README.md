# DPT to USA

A consultation site for a US-licensed physical therapist who guides Pakistani DPT graduates to US licensure.

| Surface | Who | Purpose |
|---|---|---|
| Public website | Visitors / students | Learn, watch the intro video, submit the eligibility (lead) form, buy a plan by bank transfer |
| Admin panel `/admin` | The doctor / staff | Leads, orders (payment verification), affiliates, services, email templates, settings |
| Affiliate portal `/affiliate` | Marketers | See clicks and sales from their link, generate a monthly invoice |

Laravel 13, PHP 8.4, PHPUnit 12, Vite 8. No CSS or JS framework: hand-written BEM CSS in `resources/css/app.css`, vanilla JS modules in `resources/js`. No Composer or npm dependencies beyond the framework.

## The funnel

1. A visitor lands (possibly through `?ref=CODE`) and watches the intro video.
2. They submit the lead form. A `Lead` is stored as *Needs review* and the admin is emailed.
3. The admin opens **Leads** and clicks **Send video**. The lead receives the editable "Lead video" email.
4. The lead picks a plan on **Services**, enters their details at **Checkout**, sees the bank account, transfers the money and uploads a receipt.
5. The admin opens **Orders**, views the receipt and clicks **Confirm payment** or **Reject**.
6. On confirm, the order is *Confirmed*, the lead becomes a *Customer*, the customer is emailed (including the WhatsApp number) and the affiliate, if any, earns commission.
7. Affiliates log in, see their numbers and generate a monthly invoice. Paying it is manual; the admin then marks the month as paid.

## Local setup

The site is served by Laravel Herd at <http://dp-project.test>.

```bash
composer install
cp .env.example .env          # then set ADMIN_EMAIL and ADMIN_PASSWORD
php artisan key:generate
php artisan migrate --seed    # plans, system email templates, default settings, the admin
npm install
npm run build                 # or `npm run dev` while working on CSS/JS
php artisan queue:work        # emails and click tracking are queued
```

Log in at `/login` with `ADMIN_EMAIL` / `ADMIN_PASSWORD`. With `MAIL_MAILER=log` (the default) emails are written to `storage/logs/laravel.log` instead of being sent.

To create another admin, or reset an admin's password:

```bash
php artisan app:create-admin you@example.com
```

Affiliates have no self-service password reset: the admin sets a new password under **Affiliates → Edit**.

### Tests

```bash
php artisan test
vendor/bin/pint --test
```

The test suite runs the array cache with serialization turned on (`CACHE_ARRAY_SERIALIZE`), so cached values behave as they do with the database or Redis store.

## Where things are

```
app/Actions            One class per business operation (CaptureLead, PlaceOrder, ConfirmOrder, ...)
app/Enums              UserRole, LeadStatus, OrderStatus
app/Http/Controllers   Thin; validation lives in app/Http/Requests
app/Mail               TemplatedMail (admin-written templates) and the admin notifications
app/Support            Settings, LeadForm, TemplateRenderer, AffiliateStats, MailConfigurator, SpamGuard, ...
config/lead_form.php   The one definition of the lead form
config/site.php        Brand, timezone, setting defaults, queue names, rate limits
routes/public.php      Stateless public routes (no session, no cookies, no CSRF)
routes/web.php         Session routes: login, admin, affiliate portal
tests/load/public.js   k6 load test
```

### Changing the lead form

Edit `config/lead_form.php` only. The public form, its validation, the admin table columns, the lead page, the CSV export and the notification email are all generated from that list. `name`, `email` and `whatsapp` are stored in their own columns; every other field is saved in `leads.answers`.

### What an admin can change without code

Under **Settings**: notification recipients, SMTP, bank details, WhatsApp and public email, video URLs, default commission and the attribution window. Under **Services**: plan names, texts, prices, features and visibility. Under **Email templates**: the wording of every email students receive.

### Money and time

Money is a whole number of US dollars. Timestamps are stored in UTC; dates shown to people and every "which day" bucket use `Asia/Karachi` (`config('site.timezone')`). Orders also store `placed_on` / `confirmed_on` (the Karachi calendar day) so reports can group by day on any database.

## Content the owner still has to supply

Settings covers the email, WhatsApp, bank details, video URLs and prices. These placeholders are content and stay in the code until the owner sends them:

- `config/site.php`: the doctor's name (`Dr. [Full Name], DPT`), short name, social handle
- Home: hero stats (`[120+]`, `[8 yrs]`), portrait and photo placeholders, the "About" teaser paragraphs, three student stories, verification time (`[24 hours]`)
- About: intro paragraph, quote, timeline years and text, credentials
- Pathway: typical time per step (`[X–Y months]`)
- Services: check-in counts and support duration in plan features, the comparison table (`App\Support\SiteContent`), the fair-use note
- FAQ: typical overall duration, refund policy, session times
- Contact: response hours
- Final lead form fields (currently the fields from the home page design)
- Mockups for checkout, login, settings, email templates, services admin and the invoice. Until they arrive these screens are composed from the existing styles.

Also set `APP_NAME` in `.env`; it is the default From name on emails until one is saved in Settings.

## Architecture for scale

The target is 50,000 visitors a minute (about 830 requests a second). The design goal: anonymous traffic never touches PHP sessions or the database, and ideally never reaches PHP at all.

- **Stateless public pages.** Everything in `routes/public.php` runs in a slim middleware group with no session, no encrypted cookies and no CSRF. Responses carry `Cache-Control: public, max-age=60, s-maxage=600, stale-while-revalidate=86400` and never a cookie.
- **Zero queries when warm.** Plans and settings are read through cached accessors (`Plan::cachedActive()`, `App\Support\Settings`) that are flushed when an admin saves. A test asserts that every public page runs no queries on a warm cache.
- **Forms without sessions.** The lead, contact and checkout forms post with `fetch()` to JSON endpoints. Instead of CSRF tokens they are protected by per-IP rate limits, a honeypot field and a minimum fill time (`App\Support\SpamGuard`, which is also the place to verify a Cloudflare Turnstile token later).
- **Fast write path.** A submission does one insert and queues its mail. Affiliate clicks are a `sendBeacon` call that only queues a job; workers fold clicks into a daily aggregate table and dashboards read aggregates, never raw events.

### Edge cache

Put a CDN (Cloudflare) or nginx `fastcgi_cache` in front. The cache key **must ignore `ref` and `utm_*`** query parameters, otherwise every affiliate link is a cache miss. Attribution is not affected: the tracking script reads those parameters in the browser.

Never cache `/login`, `/admin/*`, `/affiliate/*`, `/orders/*` or any `POST`.

```nginx
# Strip ref / utm_* from the cache key (the browser still sees the full URL).
map $args $cache_args {
    default                                   $args;
    "~^(.*?)&?(?:ref|utm_[a-z]+)=[^&]*(.*)$"  $1$2;
}
fastcgi_cache_key "$scheme$request_method$host$uri?$cache_args";
```

On Cloudflare, use a Cache Rule with a custom cache key that excludes those query parameters.

### Production `.env`

| Concern | Local | Production |
|---|---|---|
| Database | SQLite | MySQL 8 or PostgreSQL. SQLite cannot take concurrent writes at this scale |
| Cache / sessions / queue | `database` | Redis for all three |
| PHP | Herd | PHP-FPM with OPcache (`opcache.validate_timestamps=0`). Laravel Octane is an optional later step and needs approval (new dependency) |
| Edge | none | CDN in front; `public/build` served with long cache headers |
| Files | `local` disk | `local` or S3. Receipts must stay private |

```dotenv
APP_NAME="DPT to USA"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
CACHE_STORE=redis
SESSION_DRIVER=redis
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=redis

QUEUE_MAIL=mail
QUEUE_TRACKING=tracking

ADMIN_EMAIL=owner@yourdomain.com
ADMIN_PASSWORD=change-me-then-remove-this-line
```

### Queue workers and the scheduler

Run two Supervisor programs so a burst of clicks never delays an email:

```ini
[program:dpt-worker]
command=php /var/www/dpt/artisan queue:work --queue=default,mail --tries=3 --max-time=3600
numprocs=2
process_name=%(program_name)s_%(process_num)02d
autostart=true
autorestart=true
stopwaitsecs=3600
user=www-data

[program:dpt-tracking]
command=php /var/www/dpt/artisan queue:work --queue=tracking --tries=3 --max-time=3600
numprocs=2
process_name=%(program_name)s_%(process_num)02d
autostart=true
autorestart=true
user=www-data
```

Workers re-read the mail settings before every job, so changing SMTP under Settings needs no restart. Failed jobs are listed by `php artisan queue:failed`.

The scheduler prunes raw affiliate visit rows after 45 days and old failed jobs weekly:

```cron
* * * * * cd /var/www/dpt && php artisan schedule:run >> /dev/null 2>&1
```

### Deploy

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan db:seed --force      # safe to repeat; only adds what is missing
php artisan optimize
php artisan queue:restart
```

## Email delivery

All outgoing mail is queued and sent as multipart HTML + text. The From address always equals the authenticated sending address; a visitor's address only ever appears in `Reply-To`, so the admin can answer a lead straight from the inbox.

Code cannot guarantee inbox placement. These are the controls:

**Recommended: a transactional provider on the business domain.** Send from `hello@yourdomain.com` through the SMTP service of Resend, Postmark, Brevo or Amazon SES, and add the DNS records the provider shows you. They have this shape (the exact names and values come from the provider's dashboard):

| Record | Host | Value |
|---|---|---|
| SPF (TXT) | `yourdomain.com` | `v=spf1 include:<provider's SPF include> ~all` (one SPF record only; merge includes if you already have one) |
| DKIM (CNAME or TXT) | `<selector>._domainkey.yourdomain.com` | the key or target given by the provider |
| DMARC (TXT) | `_dmarc.yourdomain.com` | `v=DMARC1; p=none; rua=mailto:dmarc@yourdomain.com` (move to `p=quarantine` once reports are clean) |

**Supported for launch: Gmail SMTP.** Host `smtp.gmail.com`, port `587`, TLS, username = the Gmail address, password = a Google **App Password** (needs 2-Step Verification). The From address must be that same Gmail address. The limit is roughly 500 recipients a day.

Enter either under **Admin → Settings → Outgoing email**, save, then use **Send test email**; it sends immediately and shows the real SMTP error if it fails. The SMTP password is stored encrypted and never shown again. With no SMTP host saved, the site uses the mailer from `.env`.

Before launch, send a template to [mail-tester.com](https://www.mail-tester.com) and to a real Gmail inbox, and record the score here.

- mail-tester score: **not measured yet** (needs the real sending domain)

## Security notes

- `/admin/*` needs an active admin, `/affiliate/*` an active affiliate; a paused affiliate can neither log in nor earn on new referrals. Login is limited to 5 attempts a minute per login and IP.
- Affiliates only ever see their own data: every portal query is scoped through the logged-in user's affiliate, and student names are masked (`A***a K.`).
- Receipts are validated by real file content (JPEG, PNG, WebP or PDF, up to 5 MB), stored on the private disk under a random name and served only through an admin-only route.
- The checkout payment page is reachable only through a signed link and is never cached.
- Email templates are rendered by plain placeholder substitution and Markdown with raw HTML stripped. Admin-written text is never compiled as Blade.
- Every response carries `X-Frame-Options`, `X-Content-Type-Options` and `Referrer-Policy`.

## Load test results

`tests/load/public.js` is a [k6](https://k6.io) script that ramps the public pages to about 830 requests a second through the cache and posts the lead form at a realistic rate:

```bash
k6 run -e BASE_URL=https://staging.yourdomain.com tests/load/public.js
```

**Not measured yet.** The 50,000 visitors a minute figure is a design target, not a result: it has not been run on production-like hardware, and k6 is not installed on the development machine. Record the k6 summary here after running it against staging, behind the CDN:

| Date | Environment | Scenario | Rate | p95 | p99 | Errors |
|---|---|---|---|---|---|---|
| — | — | public pages through the cache | — | — | — | — |
| — | — | `POST /leads` | — | — | — | — |
