# Diane & Jomar — wedding website

Laravel 13 + Inertia 3 + Vue 3 + Tailwind 4, SQL Server (`localhost\SQLSERVER`, database `diane_wedding`, Windows auth).
A one-page invitation site (`/`) plus an admin panel (`/admin`) that edits every piece of content and monitors RSVPs live.

## Commands

- `php artisan serve` + `npm run dev` — local development (or `npm run build` once and only `php artisan serve`).
- `php artisan migrate` then `php artisan db:seed` — safe to repeat: a database with no admin user gets the admin and all dummy content; otherwise only missing settings are added (`WeddingSettingsSeeder`), so deleted content never comes back. In production a new database needs `ADMIN_PASSWORD`.
- `php artisan test` — runs on SQLite `:memory:` (see `phpunit.xml`), never on SQL Server. Every test class uses `$seed = true`: the in-memory database is migrated once and shared between classes.
- `php artisan storage:link` — needed for admin uploads (`/storage/uploads/...`) locally.

## Deployment: pushing to `main` deploys

Live at https://diane-and-jomar-wedding.runasp.net (`site103294.siteasp.net` is only the Web Deploy server name and shows MonsterASP's placeholder). `.github/workflows/deploy.yml`: an Ubuntu job tests and builds the release; a Windows job writes the production `.env` from GitHub secrets/variables, runs `migrate --force` + `db:seed --force` against the production database, then syncs to the MonsterASP.NET site `site103294` with Web Deploy (`/wwwroot` holds the whole app; the root `web.config` serves `public/`). Pull requests only test and build.

- Production database `db76831`: the site uses `db76831.databaseasp.net` (private network only); anything outside MonsterASP, GitHub included, must use `db76831.public.databaseasp.net`.
- The server `.env` is rewritten on every deploy, so production configuration changes go in the workflow or GitHub secrets/variables, never on the server.
- Migrations run automatically on deploy, so they must be forward-only and additive.
- `public/build` is mirrored; everything else uses `DoNotDeleteRule`, so uploads, logs and sessions survive and a file deleted from the repo stays on the server until removed by hand.
- Uploads go straight into `public/storage` there (`PUBLIC_DISK_IN_WEBROOT=true`; IIS hosting has no `storage:link`). PHP upload limits: `public/.user.ini`; IIS request size: `web.config`.

## Where things live

- Public page: `resources/js/Pages/Home.vue` composes `resources/js/Components/Wedding/*Section.vue`.
- Motion: `resources/js/lib/motion.js` (GSAP + ScrollTrigger + SplitText, Lenis smooth scroll, `v-reveal` directive, `drawOnScroll`). Everything respects `prefers-reduced-motion`.
- Background music: Admin > Music (`Admin\MusicController`; the `music` settings group is hidden from Site Settings). `music_url` is a suggested recording, the couple's upload or link, or empty for the music box (`resources/js/lib/musicBox.js`, Web Audio, Pachelbel's Canon); `music_enabled` = '0' removes the player. Suggestions are listed in `config/wedding.php` (`music`) and live in `public/music`: public-domain recordings from Wikimedia Commons (U.S. Air Force Band, Musopen and others), converted to 128 kbps MP3 with matched loudness (ffmpeg `loudnorm` at -20 LUFS), so the site needs no credit line. Add a new one only if it is public domain or CC0.
- Content model: `settings` (key/value, grouped; drives most text and photos) + list tables (love_stories, wedding_events, schedule_items, entourage_members, motif_colors, gallery_photos, faqs, gift_options) + invitations/rsvps.
- Admin list editors are generic: add a list by adding an entry to `config/wedding.php` (fields drive both the form and validation) and a link in `resources/js/Layouts/AdminLayout.vue`. A list's optional `headings` (setting keys) appear on its admin page as "Name on the website" — e.g. the entourage section's title and menu label (`entourage_title`, `entourage_menu_label`).
- Hiding content: every content list has `is_active` (`App\Models\Concerns\HasVisibility`, `visible()` scope) and a website-section switch, a `toggle` setting in the `sections` group named by `section` in `config/wedding.php`. `HomeController` sends hidden content as empty lists; `Home.vue` then drops the empty section and its menu link.
- Theme: boho colour tokens in the `@theme` block of `resources/css/app.css` (paper, linen, cream, sand, latte, caramel, cocoa, espresso, ink, film, terracotta, clay, ochre `gold`, rust). Backgrounds stay brown to beige: the couple asked for no green. Boho decorations are drawn in code by `resources/js/lib/bohoArt.js` (seeded, so identical on every visit): `BohoCorner.vue` (corner florals: pampas, palm spears, cream roses, bunny tails; replaced by the `decor_corner_image` setting when the admin uploads their own artwork, provided from `Home.vue`) and `PampasWreath.vue` (the monogram wreath on the save-the-date card). Also the rainbow-arch divider in `SectionTitle.vue` and the `linen` texture utility. The invitation email repeats the colours inline.
- Opening screen (`IntroGate.vue`): a full-screen cream paper invitation (`boho-paper` utility, shared with the save-the-date card) using the `cover_kicker` and `invite_line` settings and the first event's venue. It shows no photo: `cover_image` is only the link-preview photo and the email fallback.
- Save-the-date card (`SaveTheDateSection.vue`): a cream boho invitation; its wording is the `std_*` settings, the venues are the first two Ceremony & Reception events. Beside it, "Our Little Film" (`std_video_url`, setting type `video`: an uploaded video or a YouTube link) can be switched off with `show_std_film`; playing it pauses the background music.
- Video uploads go up in 8 MB pieces (`MediaField.vue` → `Admin\VideoUploadController`, up to 250 MB): IIS refuses requests over 40 MB (`web.config`), so never raise that limit for videos. The pieces wait in `storage/app/private/video-chunks` and are joined on the public disk; `web.config` maps the video MIME types.
- Print Cards (`/admin/print`, `Admin\PrintController`): a printable save-the-date card. Paper presets or any custom size (50–1000 mm a side) are remembered as `print_*` settings (group `print`, hidden from Site Settings). The PDF is made in the browser, because the IIS shared hosting has no headless Chrome: `Pages/Print/SaveTheDate.vue` (opened in a new tab) renders `Components/Print/SaveTheDateCard.vue` hidden behind the page, and `lib/pdf.js` (html2canvas-pro + jsPDF, loaded only there) draws it at 300 dpi onto a page of the exact paper size. Not html-to-image: its SVG `<foreignObject>` route leaves pictures out in Safari (the QR code went missing on iPhones). For html2canvas the card avoids what it draws wrong: the QR code is an inline SVG, `BohoCorner` turns its arrangement inside the SVG (not with Tailwind `rotate`/`scale`, which it ignores), and gradients fade to their own colour at zero alpha, never `transparent` (grey blotches). The card can show the wedding hashtag; names are stacked ("Jomar / and / Diana", the couple's choice) or on one line (`names_layout`); its text comes in three groups (names, accents, details), each with a colour and bold / italic switches, plus two size sliders (70–150 %) and a per-line fine-tune (`item_sizes`, 50–200 %, keys in `PrintController::ITEMS`). Bold uses Cormorant Garamond 700 and Jost 600, which `vite.config.js` bundles for this.
- Attire samples (`attire_samples`, admin "Attire Samples"): outfit pictures for guests or sponsors, ladies or gentlemen, with a full-size viewer, at the top of the dress code. Below them come the motif palette, then the written dress-code cards (`dress_*` settings).
- Dummy photos: `public/images/dummy/` (Unsplash, free licence).
- Invitation email: `App\Mail\InvitationMail` + `resources/views/mail/invitation*.blade.php`; sent from the Invitations page by `Admin\InvitationMailController` (one request per guest, no queue worker). Preview: `/admin/invitations/email-preview`. It copies the website's boho invitation (cream paper, stacked names, date between rules, hashtag); the corner flowers are PNG snapshots of `BohoCorner` in `public/images/email/`, attached inline. Re-render them if the drawing changes. Gmail on phones in dark mode darkens light emails whatever the email asks.

## Gotchas

- File names are case-sensitive in CI and production (Linux runners), not on this Windows machine. Pages live in `resources/js/Pages`, so `config/inertia.php` overrides Inertia's lowercase `js/pages` default; without it `assertInertia` fails only in CI.
- `pdo_sqlsrv` returns integer columns as strings — cast integer columns used in JS arithmetic (see `Invitation::casts`, `Rsvp::casts`).
- Named SQL Server instance: keep `DB_PORT` empty; empty `DB_USERNAME`/`DB_PASSWORD` means Windows authentication.
- Write `.env` without a BOM (Windows PowerShell `Set-Content -Encoding utf8` adds one and breaks the first key).
- Invitation links are `/?invite=CODE`; `/?to=Name` only personalises the greeting. Seats are enforced server-side in `RsvpController`.
- Every guest-facing link is built from `Setting::siteUrl()` (the `site_url` setting, edited on the Invitations page); empty means the current server. Never hard-code `url()` for links that leave the site.
- `.env` mail is live Gmail SMTP. Tests use `MAIL_MAILER=array` (phpunit.xml); manual checks must not press Send / Send test unless the user asks.
- Fonts are self-hosted at build time by `laravel-vite-plugin/fonts` (`@fonts` in `app.blade.php`); the build needs internet the first time.
