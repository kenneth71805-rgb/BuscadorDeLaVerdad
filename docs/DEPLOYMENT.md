# Deployment guide

Nothing here has been run on a live server yet. Do every step on a **staging** server first, with a copy of the settings
you will use for real, and try each role by hand before you repeat it for the live site.

## 1. What the server needs

- A Linux server you control (a small VPS is enough), a domain name, and HTTPS (a free Let's Encrypt certificate works).
- PHP 8.2 or newer with these extensions: `mbstring`, `xml`, `curl`, `zip`, `gd`, `fileinfo`, `pdo_mysql`, `openssl`, `tokenizer`, `ctype`, `bcmath`; `exif` is optional (it straightens phone photos).
- MySQL 8 or MariaDB 10.6+, Composer 2, Node.js 20+ and Git.
- The command-line tools `mysqldump` (for backups) and, ideally, `clamav` (to scan uploads).

PHP settings (`php.ini`, for both web and command line):

```
upload_max_filesize = 45M
post_max_size       = 50M
memory_limit        = 256M
expose_php          = Off
```

## 2. First install

```
git clone https://github.com/kenneth71805-rgb/BuscadorDeLaVerdad.git /var/www/lossuenos
cd /var/www/lossuenos

composer install --no-dev --optimize-autoloader
npm ci
npm run build

cp docs/env.production.example .env     # then edit .env: domain, database, mail, APP_URL
php artisan key:generate

php artisan migrate --force
php artisan db:seed --force             # adds the default sections only; safe to repeat
php artisan staff:create-admin you@yourdomain "Your Name"   # asks for a password (12+ characters)

rm public/robots.txt                    # the site generates its own robots file

php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Run `npm ci` only after `composer install` (the build needs a package that Composer installs).

Permissions: the web server's user (often `www-data`) must be able to write to `storage/` and `bootstrap/cache/`, and **nothing else**.
`.env` should be readable only by the owner and the web server (`chmod 640 .env`).

```
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R u+rwX,g+rwX,o-rwx storage bootstrap/cache
```

There is no `storage:link` step: photos and PDFs are private and are served through the application, which checks permission first.

## 3. The web server (nginx example)

The web root must be the `public` folder, never the project folder.

```
server {
    listen 443 ssl http2;
    server_name YOUR-DOMAIN;
    root /var/www/lossuenos/public;
    index index.php;

    client_max_body_size 50M;          # must be at least as large as PHP's post_max_size

    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }
    location ~ /\.(?!well-known) { deny all; }   # never serve .env, .git, etc.
}
server { listen 80; server_name YOUR-DOMAIN; return 301 https://$host$request_uri; }
```

## 4. The scheduler (required)

Scheduled publishing, deadline reminders, the nightly backup and the one-week clean-up of contact messages all depend on it.
Add one line with `crontab -e -u www-data`:

```
* * * * * cd /var/www/lossuenos && php artisan schedule:run >> /dev/null 2>&1
```

Check it works: schedule a story for two minutes ahead and see that it goes live. No queue worker is needed.

## 5. After the first install: check these by hand

1. The home page loads over HTTPS and `http://` redirects to `https://`.
2. Log in as the Admin. Create an Editor-in-Chief and a Head of Category (with a category). Log in as each.
3. As the Head, write and submit a story. As the Editor-in-Chief, approve and publish it. See it on the home page.
4. Upload a photo, approve it, attach it to a story, and open the story in a private window. Open the photo's address while logged out *before* approving it: it must show "not found".
5. Upload a PDF issue and download it as a visitor.
6. Send a contact message and read it in the Inbox.
7. Press "Back up now" in Admin, then check the archive exists (see the backup guide).
8. Request a password reset and confirm the email arrives.
9. Open `https://YOUR-DOMAIN/sitemap.xml` and `/robots.txt`. Paste a story link into Facebook's Sharing Debugger to see the preview.
10. Turn on the stricter settings in [SECURITY.md](SECURITY.md).

## 6. Updating the site

```
cd /var/www/lossuenos
php artisan backup:run            # always back up first
php artisan down                  # shows a "back soon" page to visitors
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan up
```

Then repeat checks 1 to 3 from section 5. Test every update on staging first.

## 7. Rolling back

If an update goes wrong:

1. `php artisan down`
2. Go back to the previous version of the code: `git log --oneline` to find it, then `git checkout <that-commit>`, then `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build`.
3. **If the update changed the database**, restore the backup you took in step 1 of the update (see [BACKUP_RECOVERY.md](BACKUP_RECOVERY.md)). Do not rely on `migrate:rollback`: not every migration can be undone safely.
4. Clear and rebuild caches (`php artisan config:cache && php artisan route:cache && php artisan view:cache`), then `php artisan up`.

## 8. A staging server

Use the same steps with a different domain, database and `.env` (`APP_ENV=staging`). Protect it with a password at the web-server level
(HTTP basic authentication) so search engines and strangers cannot reach it, and use test accounts and fake content only.

## 9. Keeping it healthy

- `/up` returns a simple "healthy" answer; point an uptime monitor (for example UptimeRobot) at it.
- Logs are in `storage/logs/`. Check them after each update.
- Run `composer audit` and `npm audit` monthly, and update PHP, the operating system and the dependencies regularly.
