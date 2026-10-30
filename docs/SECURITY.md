# Security

## What already protects the site

- **Permissions are enforced on the server** for every action (not just hidden buttons). A Head of Category cannot read or change another category, even by editing the request.
- **Only four kinds of people exist:** Admin, Editor-in-Chief, Head of Category, visitor. Nobody can give themselves a role: roles can only be set by an Admin, and every change is written to the audit log.
- **Accounts:** public sign-up is closed. Passwords are hashed and must be 12+ characters. Login attempts are rate-limited. Deactivating an account, or an Admin resetting a password, ends that person's sessions.
- **Editorial rules:** a story cannot jump to "published"; scheduled stories stay private until their time; a photo cannot appear publicly until an editor approves it.
- **Uploads:** photos and PDFs are checked by their actual contents (never by name or declared type), re-saved or screened, given random names, and stored where the web server cannot serve them directly.
- **Private data:** contact messages are deleted a week after they are resolved; the IP address is stored only as an irreversible fingerprint; page views are counted without cookies or identifiers; the helper's stored questions are scrubbed of emails and numbers and deleted after a week.
- **Output safety:** story text is always shown as plain text, never as HTML. Page details written into the HTML are escaped, and structured data cannot be closed early by story text.
- **Headers:** `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, `Cross-Origin-Opener-Policy`; optional `Strict-Transport-Security` and `Content-Security-Policy` (below). Staff and login pages carry `noindex`.
- **Audit log** of important actions, visible to Admins only and not editable from the site.

## Settings to turn on in production

All of these are in `.env` (see `docs/env.production.example`).

| Setting | Why |
|---|---|
| `APP_ENV=production`, `APP_DEBUG=false` | Debug pages leak passwords and file paths. |
| `FORCE_HTTPS=true` | All links use `https://`. |
| `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`, `SESSION_DRIVER=database` | Login cookies only over HTTPS; sessions can be ended centrally. |
| `SECURITY_HSTS=true` | Tells browsers to always use HTTPS. Turn on only after HTTPS works everywhere, because it is hard to undo. |
| `SECURITY_CSP=report-only`, then `enforce` | Stops injected scripts. Start with `report-only`: open the site and the staff screens with the browser's developer console open, and look for "Content Security Policy" messages. When there are none for a week, change to `enforce`. |
| `TRUSTED_PROXIES` | Only if the site sits behind a load balancer or reverse proxy. |

## Checklist before launch

Server
- [ ] HTTPS works and `http://` redirects to it; certificate renews automatically.
- [ ] Only ports 80, 443 and SSH are open (firewall). SSH uses keys, not passwords.
- [ ] The web root is `public/`; `.env`, `.git` and `storage/` cannot be reached from a browser (try the addresses).
- [ ] The database user has rights on this one database only; the database is not reachable from the internet.
- [ ] `.env` is not in Git and is readable only by the owner and the web server.
- [ ] `expose_php=Off`; PHP, the operating system and all packages are up to date.
- [ ] Antivirus (for example ClamAV) scans uploaded files. PDF screening in the app is basic.

Accounts
- [ ] Only the people who need an account have one; each has the lowest role that fits.
- [ ] Only one or two Admins; at most one holds the override permission.
- [ ] Everyone uses a strong, unique password. **Two-step login is not built yet: add it for the Admin and Editor-in-Chief accounts.**
- [ ] Leavers are deactivated the same day (Admin, Staff accounts).

Content and privacy (Data Privacy Act of 2012)
- [ ] Your adviser or the school's data privacy officer has reviewed the privacy notice on the Contact page.
- [ ] Portraits and photos of people are published only with consent; extra care for minors.
- [ ] The scheduler runs, so contact messages and helper questions are deleted on time (check the audit log for "contact.purged").
- [ ] Backups hold deleted contact messages until they age out (`BACKUP_KEEP` days). Choose a value you can justify, and protect the archives.
- [ ] You know who to tell, and how, if private data is ever exposed.

Operations
- [ ] Backups run nightly **and a copy is stored somewhere else** (see BACKUP_RECOVERY.md); you have practised a restore.
- [ ] Someone checks `storage/logs` and the audit log regularly, and the uptime monitor sends alerts to a real person.
- [ ] `composer audit` and `npm audit` are run monthly.
- [ ] The staging site has been tested with every role.

## Reporting a problem

Tell the Admin at once. If it involves personal data, tell your adviser or data privacy officer the same day.
