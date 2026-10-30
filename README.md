# Los Sueños – Buscador De La Verdad

The website and editorial system of the official student publication of Kolehiyo ng Lungsod ng Dasmariñas (KLD).

Built with Laravel 12, Inertia, React, TypeScript and Tailwind CSS. The code is complete for the features listed below,
but **it has not been deployed to a live server or used by real staff yet**. Read "Before you go live" first.

## Guides

| Guide | What it covers |
|---|---|
| [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) | Putting the site on a server, updating it, and rolling back |
| [docs/SECURITY.md](docs/SECURITY.md) | What protects the site, and the checklist to finish before launch |
| [docs/BACKUP_RECOVERY.md](docs/BACKUP_RECOVERY.md) | Backups, copying them off the server, and restoring |
| [docs/DATABASE.md](docs/DATABASE.md) | Tables and how they relate |
| [docs/env.production.example](docs/env.production.example) | Production settings, with no real secrets |

## Local setup (Windows, Laravel Herd)

```
composer install
npm install
copy .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan staff:create-admin you@example.com "Your Name"
composer run dev
```

In a second terminal run `php artisan schedule:work` (scheduled publishing, reminders, nightly backup and data clean-up).
Run the tests with `php artisan test`.

## Who can do what

| | Admin | Editor-in-Chief | Head of Category | Visitor |
|---|---|---|---|---|
| Staff accounts, roles, category assignment | Yes | No | No | No |
| Categories, About page, Editorial board profiles, site settings | Yes | No | No | No |
| Audit log, backups | Yes | No | No | No |
| Write and submit articles | Yes | Yes | Own categories | No |
| Approve at category level | No | Yes | Own categories (not their own article) | No |
| Final approval, schedule, publish, decide corrections | Only with the separate override permission | Yes | Publish only if granted | No |
| Approve photos for public release | Yes | Yes | No | No |
| Galleries, PDF issues, public events, helper FAQ | Yes | Yes | No | No |
| Contact inbox | Yes | Yes | No | No |
| Assignments and deadlines | All | All | Own categories | No |
| Reports | All | All | Own categories | No |
| Read published stories, photos, issues, archives, events; search; contact form; helper | Yes | Yes | Yes | Yes |

Every rule is enforced on the server, not only hidden in the screens.

## What is built

**Public site:** home page, sections, stories at stable addresses, search, About page with the editorial board, photo galleries,
publication issues with PDF download, archives by year, public events, contact form, an automated FAQ helper, sitemap, robots file,
share previews and structured data.

**Editorial workflow:** draft, submit, category approval, editor review, approve, schedule or publish, archive; return for revision and
reject with a written reason; every change recorded with who, when and why. Corrections to live articles keep the story live while the
Editor-in-Chief reviews them, keep the replaced text, and show readers an "Updated" date and an optional notice.

**Newsroom tools:** media library with private-until-approved photos, assignments with deadlines and reminders, an editorial calendar,
in-app notifications, reports (publishing, turnaround, overdue, page views), audit log, nightly backups.

**Safety:** server-side permissions, upload checking by content (photos and PDFs), private files behind permission checks, rate limits,
anonymous page-view counting, automatic clean-up of contact messages (1 week after they are resolved), security headers.

## What is NOT built (compared with the original plan)

Please read this list. Each item is a decision for you and your adviser: build it, or accept it.

- **Two-step login (MFA)** for staff. Strongly recommended before launch (Laravel has a ready-made option).
- **Email.** Nothing is emailed except password-reset links, which need working mail settings. Notifications are in-app only.
- **Featured stories and editor-picked "popular" stories.** The lead story is simply the newest one. Tags and separate byline profiles do not exist; the byline is the author's account name.
- **Rich text and pictures inside articles.** Article text is plain paragraphs. Each article can have one lead photo.
- **Search filters** by author, date or tags. Search covers headline, subtitle and text; browsing by section and by year works.
- **Announcements** (site-wide notices) and **video** uploads.
- **Revision history of drafts.** The status history and the corrections history are kept, but drafts are overwritten when saved.
- **A free-form internal comment thread** on articles. Reasons are recorded when returning or rejecting.
- **Galleries and issues are managed by the Admin and Editor-in-Chief only**, not by Heads of Category.
- **Rejected articles are kept.** Only contact messages, helper questions and old backups are removed automatically. The retention rule for rejected articles (anonymised record) was left for your adviser to decide.
- **Share buttons and a reading-progress bar** on articles.
- **Analytics** are limited to the built-in page-view count (not unique visitors).
- The FAQ helper is rule-based, not an AI model.
- **Uploaded PDFs are only screened**, not virus-scanned. Add an antivirus scan on the server.

## Before you go live

1. Work through [docs/SECURITY.md](docs/SECURITY.md) and [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md).
2. Install on a **staging** server first and try every role by hand. The tests ran on Windows with SQLite; they have not been run against MySQL or a production server.
3. Have the privacy notice on the Contact page reviewed by your adviser or the school's data privacy officer.
4. Fill in the real About text, contact details, editorial board and FAQ answers in the Admin area.
5. Make sure the scheduler runs, and copy backups off the server.
