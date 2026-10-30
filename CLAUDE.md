# Diane & Jomar — wedding website

Laravel 13 + Inertia 3 + Vue 3 + Tailwind 4, SQL Server (`localhost\SQLSERVER`, database `diane_wedding`, Windows auth).
A one-page invitation site (`/`) plus an admin panel (`/admin`) that edits every piece of content and monitors RSVPs live.

## Commands

- `php artisan serve` + `npm run dev` — local development (or `npm run build` once and only `php artisan serve`).
- `php artisan migrate` then `php artisan db:seed` — the seeders are idempotent: settings are added only when missing, lists only while empty.
- `php artisan test` — runs on SQLite `:memory:` (see `phpunit.xml`), never on SQL Server.
- `php artisan storage:link` — needed for admin uploads (`/storage/uploads/...`).

## Where things live

- Public page: `resources/js/Pages/Home.vue` composes `resources/js/Components/Wedding/*Section.vue`.
- Motion: `resources/js/lib/motion.js` (GSAP + ScrollTrigger + SplitText, Lenis smooth scroll, `v-reveal` directive, `drawOnScroll`). Everything respects `prefers-reduced-motion`.
- Background music fallback: `resources/js/lib/musicBox.js` (Web Audio, Pachelbel's Canon) — used when the `music_url` setting is empty.
- Content model: `settings` (key/value, grouped; drives most text and photos) + list tables (love_stories, wedding_events, schedule_items, entourage_members, motif_colors, gallery_photos, faqs, gift_options) + invitations/rsvps.
- Admin list editors are generic: add a list by adding an entry to `config/wedding.php` (fields drive both the form and validation) and a link in `resources/js/Layouts/AdminLayout.vue`.
- Dummy photos: `public/images/dummy/` (Unsplash, free licence).
- Invitation email: `App\Mail\InvitationMail` + `resources/views/mail/invitation*.blade.php`; sent from the Invitations page by `Admin\InvitationMailController` (one request per guest, no queue worker). Preview: `/admin/invitations/email-preview`.

## Gotchas

- `pdo_sqlsrv` returns integer columns as strings — cast integer columns used in JS arithmetic (see `Invitation::casts`, `Rsvp::casts`).
- Named SQL Server instance: keep `DB_PORT` empty; empty `DB_USERNAME`/`DB_PASSWORD` means Windows authentication.
- Write `.env` without a BOM (Windows PowerShell `Set-Content -Encoding utf8` adds one and breaks the first key).
- Invitation links are `/?invite=CODE`; `/?to=Name` only personalises the greeting. Seats are enforced server-side in `RsvpController`.
- Every guest-facing link is built from `Setting::siteUrl()` (the `site_url` setting, edited on the Invitations page); empty means the current server. Never hard-code `url()` for links that leave the site.
- `.env` mail is live Gmail SMTP. Tests use `MAIL_MAILER=array` (phpunit.xml); manual checks must not press Send / Send test unless the user asks.
- Fonts are self-hosted at build time by `laravel-vite-plugin/fonts` (`@fonts` in `app.blade.php`); the build needs internet the first time.
