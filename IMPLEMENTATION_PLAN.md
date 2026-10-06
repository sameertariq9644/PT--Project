# DPT to USA — Implementation Plan

Hand this file to the coding agent. Build the phases in order; each phase ends with passing tests and a working site. Do not start a phase until the previous one meets its "Done when" list.

---

## 1. What we are building

A consultation business site for a US-licensed physical therapist who guides Pakistani DPT graduates to US licensure. Three surfaces:

| Surface | Who | Purpose |
|---|---|---|
| Public website | Visitors / students | Learn, watch intro video, submit the lead (eligibility) form, buy a plan by bank transfer |
| Admin panel `/admin` | The doctor / staff | Leads, orders (payment verification), affiliates, services, email templates, settings |
| Affiliate portal `/affiliate` | Marketers | See clicks and sales from their link, generate a monthly invoice |

### The funnel (source of truth for all behaviour)

1. Visitor lands (possibly via `?ref=CODE`), watches the intro video on the home page.
2. Visitor submits the **lead form** → a `Lead` is stored (status *Needs review*) and a notification email goes to the address configured in Settings.
3. Admin opens **Leads**, clicks **Send video** → the lead receives the editable "lead video" email template (information + detailed video link).
4. Lead returns, picks a plan on **Services** → **Checkout**: enters details, sees our bank account, transfers money, uploads a receipt screenshot.
5. Admin opens **Orders**, views the receipt, clicks **Confirm payment** or **Reject**.
6. On confirm: order is *Confirmed*, the matching lead becomes *Customer*, the customer gets a confirmation email containing our WhatsApp, and the affiliate (if any) earns commission. Everything after that is manual.
7. Affiliates log in, see their numbers, generate an invoice for a month, send it to us, and we pay manually and mark it paid.

---

## 2. Current state of the repo (read before coding)

- Laravel 13.34, PHP 8.4, PHPUnit 12, Vite 8, **no CSS/JS framework** (hand-written BEM CSS in `resources/css/app.css`, vanilla JS modules in `resources/js`). SQLite locally, served by Laravel Herd at `http://dp-project.test`.
- Design mockups live in `z-design/About-html/**.dc.html`. They are reference mockups: replicate values faithfully, do not copy markup wholesale.
- **Already converted to Blade, but static / sample data only:**
  - Public: `resources/views/pages/{home,pathway,services,about,faq,contact}.blade.php`, layout `layouts/site.blade.php`.
  - Admin: `resources/views/admin/{leads,orders,affiliates}.blade.php`, layout `layouts/admin.blade.php`, controller `app/Http/Controllers/AdminController.php` (hard-coded arrays).
  - Affiliate: `resources/views/affiliate/portal.blade.php`, `AffiliatePortalController` (hard-coded arrays).
  - `resources/js/dashboard.js` generates **fake** traffic numbers (`buildRange()`); its rendering code (KPIs, chart, rows) is reusable.
- Reusable pieces: `<x-icon>`, `<x-faq-item>`, `<x-dashboard.range-bar>`, `<x-dashboard.chart>`, `App\Support\SiteContent` (page copy), `config/site.php` (brand/contact placeholders), CSS classes `.table`, `.status--{green,amber,red,grey,dark}`, `.stat-card`, `.tabs/.tab`, `.form-grid/.field/.input`, `.btn*`, `.tool-btn`, `.order-detail`, `.panel`.
- No auth, no models besides `User`, no forms wired up (buttons are `type="button"`), no mail, admin routes are publicly reachable.
- `App\Support\ResilientFilesystem` is bound as `files` to fix a Windows view-compile race. Leave it alone.

### Designs that do NOT exist yet

The mockups link to `Checkout.dc.html` and `Eligibility.dc.html`, but neither file is in `z-design`. There are also no mockups for: login, Settings, Email templates, admin Services, lead detail, add/edit affiliate, invoice.

**Rule:** if the owner supplies a mockup, follow it exactly. Otherwise compose the screen only from existing classes/components in `resources/css/app.css` so it is visually indistinguishable from the designed pages. Do not invent a new visual language.

---

## 3. Engineering rules

- Follow `CLAUDE.md` (Laravel Boost guidelines). Create files with `php artisan make:* --no-interaction`. Run `vendor/bin/pint` on changed files. Use the `laravel-best-practices` and `testing-best-practices` skills.
- **No new Composer or npm dependencies** without the owner's approval. Everything below is achievable with the framework alone (auth, mail, queues, validation, storage, markdown via `Str::markdown`).
- Thin controllers → Form Requests for validation → single-purpose **Action** classes for business operations → Eloquent models. Enums for every status. No business logic in Blade.
- Money is an integer number of PKR (no decimals). Timestamps stored in UTC; display and day-bucketing use `Asia/Karachi` (add `site.timezone` to `config/site.php`).
- All outgoing mail is queued. All list screens are paginated. Every foreign key and every column used in `where`/`order by` is indexed.
- Every phase adds feature tests for the behaviour it introduces and keeps `php artisan test` green. Update `tests/Feature/PagesTest.php` as routes become protected or dynamic.

### Target structure

```
app/
  Actions/            CaptureLead, SendLeadVideo, PlaceOrder, SubmitPaymentProof,
                      ConfirmOrder, RejectOrder, RecordAffiliateVisit, MarkPayoutPaid
  Enums/              UserRole, LeadStatus, OrderStatus
  Http/Controllers/   PageController, LeadController, ContactController, CheckoutController,
                      TrackingController, Auth/LoginController,
                      Admin/{Lead,Order,Affiliate,Plan,EmailTemplate,Setting}Controller,
                      Affiliate/{Dashboard,Invoice,Profile}Controller
  Http/Middleware/    EnsureRole, PublicCacheHeaders
  Http/Requests/      one per write endpoint
  Jobs/               RecordAffiliateVisitJob
  Mail/               TemplatedMail, LeadReceivedMail, OrderReceivedAdminMail, ContactMessageMail
  Models/             User, Affiliate, Lead, Plan, Order, EmailTemplate, Setting,
                      ContactMessage, AffiliateDailyStat, AffiliateVisit, AffiliatePayout
  Support/            Settings, LeadForm, TemplateRenderer, AffiliateStats, MailConfigurator
config/lead_form.php  field definitions for the lead form
routes/public.php     stateless public routes (see §4)
routes/web.php        session routes: login, admin, affiliate portal
```

---

## 4. Scalability architecture (target: 50,000 visitors/minute)

50,000/min is ~830 requests/second. PHP rendering every page with a database session will not reach that on modest hardware, so the design goal is: **anonymous traffic never touches PHP sessions or the database, and ideally never reaches PHP at all.**

### 4.1 Stateless, cacheable public pages

- Move every public GET route into `routes/public.php`, registered in `bootstrap/app.php` with a slim middleware group that has **no** `StartSession`, `EncryptCookies`, `ShareErrorsFromSession` or CSRF middleware. Today `SESSION_DRIVER=database` writes a row per visitor; this removes that entirely.
- `PublicCacheHeaders` middleware sets `Cache-Control: public, max-age=60, s-maxage=600, stale-while-revalidate=86400` on those responses and never sets a cookie. A CDN (Cloudflare) or nginx `fastcgi_cache` then serves them without invoking PHP. The cache key must ignore `ref` and `utm_*` query parameters.
- Public views must not contain per-visitor output: no `@csrf`, no `old()`, no `session()`, no `auth()`.
- Anything dynamic on public pages (plans, prices, bank details, video URL, contact details) is read through cached accessors (`Settings`, `Plan::cachedActive()`), invalidated when an admin saves.

### 4.2 Public forms without sessions

Lead form, contact form and checkout post with `fetch()` to stateless JSON endpoints:

- Success → `201` JSON; the JS swaps in a success state. Validation failure → `422` JSON; the JS shows errors under fields (add `resources/js/forms.js`, one small generic handler driven by `data-ajax-form`).
- These endpoints have no CSRF token (there is no session to protect). Abuse protection instead: named rate limiters per IP (`throttle:leads` 5/min, `throttle:contact` 5/min, `throttle:checkout` 10/min), a hidden honeypot field, and a minimum-fill-time check. Leave a clean seam (`App\Support\SpamGuard`) so Cloudflare Turnstile can be added later.
- `<noscript>` shows the WhatsApp/email fallback.

### 4.3 Write path

- Lead/order/contact submissions do one insert and dispatch queued mail (`ShouldQueue`, `afterCommit`). The request never waits on SMTP.
- Affiliate click tracking is a `navigator.sendBeacon` call (see §9) that only dispatches `RecordAffiliateVisitJob` on a dedicated `tracking` queue; workers fold it into a **daily aggregate table**. Dashboards read aggregates only, never raw events.

### 4.4 Production infrastructure (document in README, set via `.env`)

| Concern | Local | Production |
|---|---|---|
| Database | SQLite | MySQL 8 or PostgreSQL (SQLite cannot take concurrent writes at this scale) |
| Cache / sessions / queue | `database` | Redis for all three |
| PHP | Herd | PHP-FPM with OPcache (`validate_timestamps=0`); `php artisan optimize` on deploy. Laravel Octane is an optional later step and needs approval (new dependency) |
| Edge | none | CDN in front; static assets from `public/build` with long cache headers |
| Workers | `queue:work` | Supervisor: `queue:work --queue=default,mail` and a separate `--queue=tracking` |
| Files | `local` disk | `local` or S3 (receipts must stay private) |

Admin and affiliate areas are low traffic and use normal sessions; no special treatment needed beyond pagination and indexes.

### 4.5 Proof

Add a `k6` (or `hey`) script under `tests/load/` and record results in the README: public pages through the cache, and the lead endpoint at a realistic submit rate. Do not claim the 50k/min figure until it is measured on production-like hardware.

---

## 5. Data model

All tables get `id`, timestamps. Index notes in the right column.

| Table | Columns | Notes |
|---|---|---|
| `users` (alter) | + `username` (unique, nullable), `role` (string, cast `UserRole`: Admin, Affiliate), `is_active` bool | Login by email **or** username |
| `affiliates` | `user_id` FK unique, `code` (unique, uppercase), `channel` nullable, `commission_rate` decimal(5,2), `payout_details` text nullable, `is_active` | `code` is what goes in `?ref=` |
| `leads` | `name`, `email`, `whatsapp`, `answers` JSON, `status` (cast `LeadStatus`), `video_sent_at` nullable, `affiliate_id` nullable FK, `utm_campaign` nullable, `ip` nullable | index `status`, `email`, `created_at` |
| `plans` | `slug` unique, `name`, `kicker`, `teaser`, `description`, `price` uint nullable, `is_featured`, `highlights` JSON, `features` JSON, `sort_order`, `is_active` | Seeded from `SiteContent::plans()` |
| `orders` | `number` unique (`DPT-1001`…), `plan_id` FK, `plan_name`, `amount` (snapshots), `name`, `email`, `whatsapp`, `status` (cast `OrderStatus`), `transaction_id` nullable, `transferred_on` date nullable, `receipt_path` nullable, `admin_note` nullable, `lead_id` nullable FK, `affiliate_id` nullable FK, `utm_campaign` nullable, `commission_rate` nullable, `commission_amount` uint default 0, `confirmed_at`, `rejected_at` | index `status`, `affiliate_id + confirmed_at`, `email`, `created_at` |
| `email_templates` | `key` unique nullable, `name`, `subject`, `body` text, `is_system` bool | System templates cannot be deleted |
| `settings` | `key` primary, `value` JSON | Read only through `App\Support\Settings` |
| `contact_messages` | `name`, `email`, `whatsapp` nullable, `topic`, `message` | |
| `affiliate_daily_stats` | `affiliate_id` FK, `date`, `campaign` string default `''`, `clicks` uint, `unique_visitors` uint | unique (`affiliate_id`,`date`,`campaign`) |
| `affiliate_visits` | `affiliate_id`, `visitor_id` uuid, `date` | unique on all three; used only to decide uniqueness; `Prunable` after 45 days |
| `affiliate_payouts` | `affiliate_id` FK, `period` char(7) `YYYY-MM`, `amount` uint, `paid_at`, `note` nullable | unique (`affiliate_id`,`period`) |

**Enums**

- `LeadStatus`: `NeedsReview` (default), `Eligible`, `NotEligible`, `Customer`. Each has `label()` and `tone()` (amber/green/grey/dark) to drive `.status--*`.
- `OrderStatus`: `AwaitingPayment` (details entered, no receipt yet), `Pending` (receipt uploaded), `Confirmed`, `Rejected`.
- "Checkouts started" = orders created (any status). "Sales confirmed", revenue and commission are always computed **from `orders`** (low volume, always correct). Only clicks and unique visitors come from `affiliate_daily_stats`.

---

## 6. Phases

### Phase 0 — Foundation

1. Create `routes/public.php` and the stateless middleware group (§4.1); move the six page routes and `/eligibility` redirect there. Keep route names unchanged.
2. Migrations, models, factories, enums from §5. Seeders: plans (from `SiteContent::plans()`), system email templates (Phase 3), default settings, one admin user from `ADMIN_EMAIL` / `ADMIN_PASSWORD` env values (never hard-code credentials).
3. `App\Support\Settings`: `get(key, default)`, `set(array)`, backed by the `settings` table and `Cache::rememberForever('settings')`, flushed on write. Replace reads of `config('site.email')`, `config('site.whatsapp')` in views with Settings values falling back to config.
4. `Plan::cachedActive()` and switch `PageController` + `home`/`services` views from `SiteContent::plans()` to it. Prices format through one helper (`PKR 30,000`, or `PKR [—]` when null).

**Done when:** all existing page tests pass, public responses carry no `Set-Cookie` and have the cache header (tested), `migrate:fresh --seed` works.

### Phase 1 — Authentication and access control

- One login screen `/login` (username or email + password) for both roles; `LoginController` using `Auth::attempt`, `session()->regenerate()`, `RateLimiter` (5 attempts/min per login+IP), "remember me", logout via POST. After login redirect by role: admin → `admin.leads`, affiliate → `affiliate.portal`.
- `EnsureRole` middleware (`role:admin`, `role:affiliate`); inactive users cannot log in. Wrap `/admin/*` and `/affiliate/*` in `auth` + role.
- `/admin` redirects to `/admin/leads`. In `layouts/admin.blade.php`: remove the "Overview" item (no design), make every remaining nav item a real route, drive the Leads/Orders badges from real counts (needs-review leads, pending orders — shared via a view composer with a short cache), show the logged-in user and a logout button.
- No public registration, no password reset UI in MVP (admin resets affiliate passwords; admin password reset via an `app:create-admin` artisan command).

**Done when:** guests are redirected from admin/portal, an affiliate gets 403 on admin routes and vice-versa, login throttling is tested.

### Phase 2 — Lead capture

- `config/lead_form.php` defines the form **once**: for each field `key`, `label`, `type` (text, email, tel, select, radio, date, number), `options`, `placeholder`, `rules`, `width` (half/full), `in_table` (show as a column in admin). `name`, `email`, `whatsapp` are fixed core columns; every other field is saved into `leads.answers`. Start with the fields currently in the home page design (graduation year, university, clinical experience, TOEFL, FCCPT). The owner will send final fields (father's name, degree, graduation date, CGPA…) — adding one must require editing only this config file.
- `App\Support\LeadForm` exposes `fields()`, `rules()`, `tableColumns()`. A `<x-lead-form>` component renders the form from it inside the existing `#eligibility` markup/classes in `pages/home.blade.php`; the admin table and the notification email render from the same definition.
- `POST /leads` (stateless, §4.2) → `StoreLeadRequest` → `CaptureLead` action: create the lead (attach affiliate from the `dpt_ref` cookie if the code is valid and active, plus `utm_campaign`), then queue `LeadReceivedMail` to the recipients in Settings (`notifications.lead_recipients`, comma-separated list). The mail has `Reply-To` set to the lead's email so the admin can answer straight from Gmail.
- Duplicate submissions with the same email within 24 hours update the existing lead instead of creating another.
- **Admin → Leads** (`Admin\LeadController@index`), keeping the existing design: real stat cards, working tabs (All / Eligible / Needs review / Not eligible / Customers) as `?status=` links with real counts, search by name/email (`?q=`), pagination (25), **Export CSV** as a streamed response (`lazy()` cursor). Columns come from `LeadForm::tableColumns()`.
- Row actions: change status (small select or buttons), and the **Send video** button (Phase 3). A lead detail drawer/page lists every answer.

**Done when:** submitting the form creates a lead, queues the admin mail (`Mail::fake` test), shows it in admin; validation errors return 422 JSON; throttle and honeypot are tested; changing `config/lead_form.php` in a test changes validation and columns.

### Phase 3 — Settings, email templates, mail delivery

**Settings page** (`/admin/settings`, one form with sections, one `UpdateSettingsRequest`):

| Section | Keys |
|---|---|
| Notifications | lead recipient email(s), order recipient email(s) |
| Outgoing email | from name, from address, SMTP host, port, encryption, username, password, "Send test email" button |
| Payment | bank name, account title, account number, IBAN, payment instructions |
| Contact | WhatsApp number, public email |
| Content | intro video URL (home page), lead video URL (used in the email template) |
| Affiliates | default commission rate (%), attribution window (days) |

- The SMTP password is stored encrypted (`Crypt`), never rendered back into the form (blank field = keep existing), never logged.
- `MailConfigurator::apply()` overrides `config('mail.*')` from Settings and purges the resolved mailer. Call it at boot **and** on the `JobProcessing` queue event so long-running workers pick up changed settings. If no SMTP settings are saved, fall back to `.env`.
- "Send test email" sends synchronously and shows the real SMTP error on failure.

**Email templates** (`/admin/email-templates`): list, create, edit, delete (not system ones).

- Fields: name, subject, body. Body is **Markdown** in a plain textarea, with clickable placeholder chips and a live preview pane. Placeholders: `{{name}}`, `{{email}}`, `{{video_url}}`, `{{whatsapp}}`, `{{plan}}`, `{{amount}}`, `{{order_number}}`, `{{site_name}}`.
- `TemplateRenderer` does plain string substitution of escaped values, then `Str::markdown($body, ['html_input' => 'strip', 'allow_unsafe_links' => false])`, wrapped in one branded Blade email layout with a plain-text alternative. **Never** pass admin-written text through `Blade::render` (it would allow code execution).
- `TemplatedMail` (queued) takes a template key + variables. Seed system templates: `lead_video`, `order_received`, `order_confirmed`, `order_rejected`. Only `lead_video` matters at launch; the others ship with sensible default text and are editable the same way.
- **Send video** on a lead → `SendLeadVideo` action: queue `TemplatedMail('lead_video')`, set `video_sent_at`, move `NeedsReview` → `Eligible`. The button becomes "Sent ✓" (existing `.sent` style); allow resend from lead detail. Custom templates can be sent to a lead from the lead detail ("Send email" → choose template).

**Staying out of spam** (code cannot guarantee inbox placement; these are the controls):

1. Recommended: send from the business domain (`hello@clientdomain.com`) through a transactional provider's SMTP (Resend, Postmark, Brevo, SES) with **SPF, DKIM and DMARC** DNS records. Document the exact records in the README.
2. Gmail SMTP is supported for launch: `smtp.gmail.com:587`, TLS, a Google **App Password** (2-Step Verification required), and the From address must be that same Gmail address. Limit is roughly 500 recipients/day.
3. In code: From always equals the authenticated address; visitor addresses only ever go in `Reply-To`; multipart HTML + text; real subject lines; no URL shorteners; queue retries with backoff (`tries=3`); failed jobs visible via `queue:failed`.
4. Verify with mail-tester.com and a real Gmail inbox before launch; record the score in the README.

**Done when:** settings persist and invalidate cache; password is encrypted at rest; template CRUD works; rendering escapes HTML and ignores Blade syntax (tested with `{{ 7*7 }}` and `@php`); Send video queues the right mail and updates the lead.

### Phase 4 — Services, checkout, orders

- **Admin → Services** (`/admin/services`): list plans, edit name, texts, price, features (one per line), featured flag, active flag, order. Saving flushes the plan cache. The comparison table stays in `SiteContent` for now.
- **Checkout** (`/checkout/{plan:slug}`, stateless cacheable GET). "Choose this plan" on `services.blade.php` and the home teaser link here. Until a checkout mockup is supplied, build it from existing form/panel classes as two steps on one page:
  1. *Your details* — name, email, WhatsApp (+ order summary card: plan, price, what's included). `POST /checkout/{plan}` → `PlaceOrder` creates the order as `AwaitingPayment` with snapshotted plan name/amount, affiliate attribution from the `dpt_ref` cookie (rate snapshotted from the affiliate), links the lead by email, and returns a signed URL for step 2.
  2. *Pay & upload* — `/orders/{order}/pay` (signed URL, `no-store`): shows bank details from Settings and the exact amount, a form for transaction ID, transfer date and the receipt file. `SubmitPaymentProof` stores the file and sets `Pending`; queues `order_received` to the customer and `OrderReceivedAdminMail` to the admin. Then a thank-you state with WhatsApp contact.
- Receipt upload: `image/jpeg, png, webp` or `pdf`, max 5 MB, validated by real MIME, stored on the private `local` disk under `receipts/` with a random name. Served only through an admin-only controller route (`response()->file`), never from `public/`.
- **Admin → Orders**, keeping the existing two-pane design: real stat cards, tabs by status with counts, search, pagination; clicking a row selects it (`?order=`) and fills the detail pane with real data, receipt preview (click to enlarge), internal note, **Confirm payment** / **Reject** buttons (POST, with confirmation).
  - `ConfirmOrder` (DB transaction): status `Confirmed`, `confirmed_at`, compute `commission_amount = round(amount × rate / 100)` if attributed, set the linked lead to `Customer`, queue `order_confirmed` (includes WhatsApp) to the customer.
  - `RejectOrder`: status `Rejected`, note required, queue `order_rejected`.
  - Both are idempotent: acting on an already-decided order is a no-op with a message.

**Done when:** full flow is covered by a feature test (place order → upload → confirm → lead is Customer, commission stored, mails queued); receipt is not reachable by guests or affiliates; invalid uploads are rejected.

### Phase 5 — Affiliates

**Tracking (`resources/js/tracking.js`, loaded on public pages):**

- If the URL has `?ref=CODE`: set first-party cookies `dpt_ref` (and `dpt_campaign` from `utm_campaign`) for the attribution window (default 30 days, last click wins), ensure a `dpt_vid` UUID cookie exists, then `navigator.sendBeacon('/t/visit', {ref, campaign, vid, path})`.
- `POST /t/visit` (stateless, `throttle:tracking`, returns 204, ignores known bot user agents) only dispatches `RecordAffiliateVisitJob` on the `tracking` queue.
- The job: resolve the active affiliate by code (cached lookup) → `insertOrIgnore` into `affiliate_visits` → upsert-increment `affiliate_daily_stats.clicks` (+ `unique_visitors` when the visit row was new).
- Lead and checkout endpoints read `dpt_ref` / `dpt_campaign` cookies server-side for attribution. An affiliate cannot earn on an order placed with their own email.

**`App\Support\AffiliateStats`:** given an optional affiliate and a date range, returns totals, previous-period totals (for the deltas) and buckets (daily; monthly when the range exceeds ~92 days) for: link clicks, unique visitors, checkouts started, confirmed sales, revenue, commission. Uses grouped queries only (two on stats, two on orders) — no per-row loops.

**Admin → Affiliates** (existing design, simplified for MVP):

- Keep: affiliate filter, date range bar (7 days, 30 days, this month, last month, this year, custom), the six KPI cards, the "over time" chart, the per-affiliate table (clicks, visitors, checkouts, sales, conversion, revenue, commission, status), Export CSV.
- Remove for MVP: the conversion funnel panel and the "payout run" banner.
- `dashboard.js`: delete the fake generator (`rnd`, `day`, `buildRange`, `AOV`, `RATE`, `TODAY`) and fetch `GET /admin/affiliates/stats?range=…&affiliate=…` (JSON from `AffiliateStats`); keep the rendering functions. Remove every `SAMPLE DATA` tag.
- **+ Add affiliate** / edit: name, username, password (admin sets it and hands it over), referral code (unique, auto-suggested), channel, commission rate (defaults from Settings), active toggle. Creating an affiliate creates the `User` (role Affiliate) and the `Affiliate` in one transaction. Pausing an affiliate stops attribution and login.
- Affiliate detail: monthly commission list with **Mark as paid** (`MarkPayoutPaid` → `affiliate_payouts` row) and a link to the same invoice the affiliate sees.

**Affiliate portal** (existing design, real data, `auth` + `role:affiliate`):

- Link builder (already works client-side) using the affiliate's real code and rate; earnings cards — Pending (orders `Pending`), Approved (confirmed, month not paid), Paid out (payouts total); range bar, KPI cards, chart from `GET /affiliate/stats`; recent referrals (their orders, student names masked like `A***a K.`); top links grouped by campaign.
- Scope every query through the logged-in user's affiliate (`$request->user()->affiliate`) — never accept an affiliate id from the request.
- "Update payout details" → small form to edit `payout_details` and change own password.
- **Invoice:** month picker → `/affiliate/invoices/{YYYY-MM}`: a print-optimised HTML page (print stylesheet, "Print / Save as PDF" button — no PDF library) with invoice number `INV-{CODE}-{YYYYMM}`, affiliate name and payout details, bill-to from Settings, one line per order confirmed in that month (date, order number, plan, sale amount, rate, commission), total, and paid/unpaid state. Only completed months can be invoiced.

**Done when:** a visit with `?ref=` increments clicks and unique visitors once per visitor per day; an attributed confirmed order shows in both dashboards with correct commission; affiliates cannot see each other's data (tested); invoice totals equal the sum of that month's commissions.

### Phase 6 — Contact form, polish, hardening

- Contact form: `POST /contact` (stateless) → store `ContactMessage` + queue `ContactMessageMail` to the admin (Reply-To the sender).
- Home intro video: play button opens the Settings video URL (YouTube/Vimeo embed in a lightweight modal, iframe created only on click so it costs nothing on page load).
- Replace remaining bracketed placeholders that Settings now covers (email, WhatsApp, bank details). Leave genuine content placeholders (bio, testimonials, stats) for the owner and list them in the README.
- Security pass: security headers middleware (`X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`), authorization tests on every admin/affiliate route, `APP_DEBUG=false` in production, mass-assignment review, no secrets in logs.
- Scheduler: `model:prune` daily (affiliate visits), `queue:prune-failed` weekly.
- Performance pass: check every admin list with `DB::listen`/query log for N+1s; confirm public pages issue zero queries on a warm cache; run the load script from §4.5.
- README: local setup, production `.env`, queue/supervisor config, DNS records for mail, deploy steps (`npm run build`, `migrate --force`, `optimize`, `queue:restart`).

---

## 7. Route map

| Method & URI | Name | Access |
|---|---|---|
| GET `/`, `/pathway`, `/services`, `/about`, `/faq`, `/contact` | existing | public, cacheable |
| GET `/checkout/{plan}` | `checkout.show` | public, cacheable |
| POST `/leads`, `/contact`, `/checkout/{plan}` | `leads.store`, `contact.store`, `checkout.store` | public, throttled, stateless |
| GET/POST `/orders/{order}/pay` | `orders.pay`, `orders.proof` | signed URL |
| POST `/t/visit` | `tracking.visit` | public, throttled |
| GET/POST `/login`, POST `/logout` | `login`, `logout` | guest / auth |
| `/admin/leads`, `/admin/orders`, `/admin/affiliates`, `/admin/services`, `/admin/email-templates`, `/admin/settings` (+ actions) | `admin.*` | `auth`, `role:admin` |
| `/affiliate`, `/affiliate/stats`, `/affiliate/invoices/{month}`, `/affiliate/profile` | `affiliate.*` | `auth`, `role:affiliate` |

---

## 8. Inputs needed from the owner

None of these block the build; use the stated default until they arrive.

| Needed | Default until supplied |
|---|---|
| `Checkout.dc.html` mockup (linked from Services design but missing) | Two-step checkout built from existing styles |
| Mockups for login, Settings, Email templates, Services admin, invoice | Composed from existing admin/portal styles |
| Final lead form fields | The fields in the current home page design |
| Plan prices | `PKR [—]`; editable in Admin → Services |
| Bank account details, WhatsApp, receiving email | Entered in Admin → Settings after launch |
| Sending method: business-domain provider (recommended) or Gmail App Password | SMTP settings left empty, mail goes to the log |
| Commission: percentage per sale (assumed, default 20%, per-affiliate override) | 20% |
| Video URLs (intro + detailed) | Placeholders |

---

## 9. Definition of done (whole project)

- The seven-step funnel in §1 works end to end on `http://dp-project.test` with real data and no `SAMPLE DATA` tags left.
- An admin can change recipient emails, SMTP, bank details, plan prices and email wording without touching code.
- Public pages send no cookies, are cacheable, and run zero queries when warm; every email is queued.
- `php artisan test` passes; `vendor/bin/pint --test` passes; `npm run build` succeeds.
- README documents deployment, mail DNS, queue workers and measured load-test results.
