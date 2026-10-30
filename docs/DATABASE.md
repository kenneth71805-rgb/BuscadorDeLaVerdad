# Database

Created by the migrations in `database/migrations`. Relationships (`->` means "belongs to"):

**People and access**
- `users` (name, email, password, `role`: admin / editor_in_chief / category_head, `is_active`, `can_override`, `can_publish`)
- `category_user` (user, category, `can_review`): which Heads look after which categories; each pair appears once.
- `staff_profiles`: public editorial-board entries; optional category and portrait (`photo_media_id` -> media). `is_public` defaults to off.

**Stories**
- `categories` (name, unique `slug`, order, `is_active`, `show_in_nav`)
- `articles` -> category, author (user), optional `lead_media_id` -> media; `status`, `published_at`, `scheduled_for`, `corrected_at`; soft-deleted.
- `article_status_histories` -> article, actor: every status change with its reason.
- `article_revisions` -> article, requested_by, decided_by: corrections, with the replaced text kept in the `previous_*` columns.
- `article_views` (article, day, count): the anonymous page-view counter.

**Media and archives**
- `media` -> uploader: photos stored privately in four sizes; `is_public` means approved for release.
- `galleries` and `gallery_items` (gallery, media, order); `issues` (date, cover -> media, private PDF path, `is_public`).

**Planning**
- `assignments` -> category, assigned_to, created_by; `calendar_events` (optional category, `is_public`); `notifications` (Laravel's standard table).

**Communication and support**
- `contact_submissions` (includes helper escalations with type `support`; `closed_at` starts the 1-week clock).
- `faq_entries`, `assistant_misses` (anonymous, deleted after a week).

**System**
- `settings` (key, value): contact details, About text, helper on/off. `audit_logs` -> actor: what happened, to what, when.
- Laravel's own: `sessions`, `cache`, `jobs`, `password_reset_tokens`, `migrations`.

## Rules the database and code both enforce

- Foreign keys use "restrict" for people and stories (you cannot delete a user who wrote articles; deactivate instead) and "cascade" only for owned child rows (history, gallery items, revisions).
- `articles.slug`, `categories.slug`, `galleries.slug`, `issues.slug` are unique and never change after creation, so public addresses stay stable.
- Indexes exist on the columns used for the public lists and filters (status and publication date, category and status, deadlines, notification recipient).
