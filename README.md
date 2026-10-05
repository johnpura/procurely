# Procurely

Procurement portal for a city: a public bid board for vendors (open bids, closed bids and search) and a staff area where staff draft bids and administrators publish and award them.

Laravel 13, Breeze (Blade), Tailwind v4 and Alpine via Vite, TailAdmin layout, MySQL 8.4.

## Local setup (Laravel Sail on WSL Ubuntu)
```bash
    git clone git@github.com:johnpura/procurely.git ~/projects/procurely
    cd ~/projects/procurely
    cp .env.example .env
    docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html laravelsail/php84-composer:latest composer install --ignore-platform-reqs
    ./vendor/bin/sail up -d
    ./vendor/bin/sail artisan key:generate
    ./vendor/bin/sail artisan migrate --seed
    ./vendor/bin/sail npm ci && ./vendor/bin/sail npm run build
```
Open http://localhost. Seeding in the local environment creates an admin account and sample bids.

## Daily commands

    sail up -d              start the containers
    sail stop               stop them
    sail artisan test       run the tests
    sail npm run build      rebuild CSS and JS
    sail bin pint           format the code

## Roles

- admin: manages users; publishes, cancels and awards bids.
- staff: drafts bids.
- Accounts are created by admins. There is no public registration.
- Users have is_active (temporary disable) and soft deletes (left the organization).

## Configuration

The organization name, department and default contact are in config/procurely.php. Set APP_TIMEZONE to the city's time zone so bid closing times are correct.

## Deploying

Create a `deploy.sh` script and place it in the project root.

Run it on the developer machine. It checks that the repo is clean and pushed, runs the tests, builds the assets, puts the site in maintenance mode, pulls, installs, migrates, copies public/build, caches, brings the site back up, and smoke-tests /login.

Before the first seed on a new server, set ADMIN_EMAIL and ADMIN_PASSWORD in .env, run `php artisan db:seed --force`, then remove ADMIN_PASSWORD.

## Production settings

PHP (create `/etc/php/<version>/apache2/conf.d/99-procurely.ini`, then reload Apache):

    upload_max_filesize = 25M
    post_max_size = 110M
    max_file_uploads = 20
    max_input_time = 300
    max_execution_time = 120
    memory_limit = 256M

An online response can contain up to 5 PDFs of 20 MB each, so one request can reach about 100 MB.

Mail (.env): MAIL_MAILER, MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_ENCRYPTION, MAIL_FROM_ADDRESS, MAIL_FROM_NAME. Confirmation emails and password resets depend on it. Test with:

    php artisan tinker --execute="Mail::raw('Test', fn(\$m) => \$m->to('you@example.com')->subject('Procurely test'));"

After deploying, test an upload of a large PDF through the vendor form on the live site.

## Records retention

**Status: period not yet confirmed.** Confirm with the city clerk or counsel before launch, then fill in the table below.

| Record | Where it lives | Retention period | Notes |
|---|---|---|---|
| Bids, descriptions and award results | `bids` table | _to be confirmed_ | Never deleted by the app once published. Only drafts can be deleted. |
| Bid documents (RFP packets, addenda) | `storage/app/private/bids/` | _to be confirmed_ | Stored privately and served through the site. |
| Vendor responses and their files | `bid_responses`, `storage/app/private/responses/` | _to be confirmed_ | Contain vendor contact details and proposals. A bid with responses cannot be deleted in the app. |
| Audit log | `audit_logs` | _to be confirmed_ | Append-only: the app cannot edit or delete entries. |
| User accounts | `users` | Kept while employed; soft-deleted afterwards | Removed staff stay in the database so history still shows who acted. |

Until a period is set, nothing is deleted. Deleting anything past its retention period is a deliberate manual task, and should be recorded.

Questions to settle with the clerk:
1. How long must bid records and vendor responses be kept after award or cancellation?
2. Do unsuccessful vendors' responses follow the same period as the winning one?
3. Does the audit log have its own period?
4. How are public records requests handled (who exports responses, and what is redacted)?
5. Is a legal hold process needed, so records cannot be purged while a protest or lawsuit is open?
