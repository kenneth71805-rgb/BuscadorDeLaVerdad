# Backup and recovery

## What is backed up

One archive (`backup-YYYYMMDD-HHMMSS.zip`) holds:

- the whole database (`database.sql`, or `database.sqlite` on the simple setup), and
- every uploaded photo and PDF (`files/media/...`, `files/issues/...`),
- a `MANIFEST.txt` saying when it was made.

It does **not** contain the code (that is in Git) or your `.env` file (keep that in a password manager).

A backup runs automatically at 2:00 AM every night while the scheduler runs, and an Admin can press "Back up now" (Admin, Backups).
Each archive is checked after it is made; a failed backup leaves no broken file and is written to the audit log as `backup.failed`.
The newest 14 are kept (`BACKUP_KEEP`). Archives are stored in `storage/app/backups` (change with `BACKUP_PATH`) and cannot be downloaded from the website, because they contain private data.

The same thing from the command line: `php artisan backup:run`.

## Copy backups off the server (do this)

A backup on the same server is lost together with the server. Every night, after 2:30 AM, copy the newest archive somewhere else:
another machine, a school server, or cloud storage. For example, from a second Linux machine:

```
# crontab on the OTHER machine, 3:00 AM daily
0 3 * * * rsync -a backupuser@YOUR-SERVER:/var/www/lossuenos/storage/app/backups/ /srv/lossuenos-backups/
```

Keep at least a month of copies, and keep them private (encrypted disk or encrypted cloud folder).

## Practise a restore

Once a quarter, restore the newest archive onto a **staging** server and check that stories, photos and PDFs appear. A backup you have never restored is only a hope.

## Restoring (MySQL)

1. Put the site in maintenance mode: `php artisan down`.
2. Unpack the archive: `unzip backup-YYYYMMDD-HHMMSS.zip -d /tmp/restore`.
3. Restore the database (this replaces the current contents):
   ```
   mysql -u lossuenos -p lossuenos < /tmp/restore/database.sql
   ```
4. Restore the files into the private storage folder:
   ```
   cp -a /tmp/restore/files/media  storage/app/private/
   cp -a /tmp/restore/files/issues storage/app/private/
   sudo chown -R www-data:www-data storage
   ```
5. Apply any newer database changes, clear caches, and bring the site back:
   ```
   php artisan migrate --force
   php artisan config:cache && php artisan route:cache && php artisan view:cache
   php artisan up
   ```
6. Check the home page, log in as the Admin, open a story with a photo, and download a PDF issue.
7. Delete `/tmp/restore` (it contains private data).

## Restoring (SQLite, the simple setup)

Stop the site (`php artisan down`), copy `database.sqlite` from the archive over the file named in `DB_DATABASE` (default `database/database.sqlite`), copy the `files/` folders as in step 4, then run step 5.

## Rebuilding on a brand-new server

1. Follow `DEPLOYMENT.md` sections 1 to 4 (install, but skip creating an Admin).
2. Put your saved `.env` in place (the `APP_KEY` must be the same one, or existing sessions and encrypted data stop working).
3. Restore the newest archive as above.

## What to expect

- Anything done after the last backup is lost: with nightly backups, up to one day. Run "Back up now" before risky changes.
- Deleted contact messages stay inside older archives until those age out.
