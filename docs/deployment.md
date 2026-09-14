# Deploying Biertappen to DirectAdmin

The hosting has **no SSH**, so nothing is built or installed on the server. A
release is produced locally as finished files and uploaded; migrations are run
by a scheduled command triggered by a file.

**Requires PHP 8.3 or newer** on the server. Check this in the panel's PHP
selector before the first deploy — `vendor/` is resolved against 8.3 locally, so
an older runtime will fail at startup rather than degrade.

---

## One-time setup

### 1. Directory layout

The application lives **outside** the web root:

```
~/biertappen/      the application (vendor, storage, .env, artisan)
~/public_html/     only what should be publicly reachable
```

This is not cosmetic. If `.env` sat under `public_html`, one broken rewrite rule
would serve your Lemon Squeezy API key as plain text.

### 2. Create the database

In DirectAdmin → **MySQL Management**, create a database and user, and note the
credentials. MariaDB 10.4+ is fine; the schema is tested against it.

### 3. Upload the first release

Build it (see below), then upload:

- `build/release/biertappen/` → `~/biertappen/`
- `build/release/public_html/` → `~/public_html/`

### 4. Write `.env` on the server

Copy `.env.example` to `~/biertappen/.env` and fill it in. **`.env` is never part
of a release** — it is uploaded once, by hand, and left alone afterwards.

```ini
APP_ENV=production
APP_DEBUG=false          # a stack trace on an error page leaks paths and config
APP_URL=https://biertappen.app

DB_CONNECTION=mariadb
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

LEMON_SQUEEZY_API_KEY=...
LEMON_SQUEEZY_STORE=474508
LEMON_SQUEEZY_SIGNING_SECRET=...
LEMONSQUEEZY_PREMIUM_VARIANT_ID=...
LEMONSQUEEZY_DONATION_VARIANT_ID=...

GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"

MAIL_MAILER=smtp
MAIL_HOST=...
```

Generate a key locally with `php artisan key:generate --show` and paste it as
`APP_KEY`. Without one, sessions and encrypted cookies will not work.

### 5. Add the cron entry

DirectAdmin → **Cron Jobs**, every minute:

```
* * * * * /usr/local/bin/php ~/biertappen/artisan schedule:run >/dev/null 2>&1
```

Confirm the PHP binary path in the panel; it differs between servers. This single
entry drives everything: the deploy hook, the queue drain, and weekly cleanup.

### 6. Point the domain at the apex

`biertappen.app` must serve **without** redirecting to `www`. Lemon Squeezy does
not follow redirects, so a 301 there silently breaks every webhook delivery — and
the failure looks like "purchases do not grant access" rather than a DNS problem.

---

## Deploying a release

### 1. Build locally

```bash
./deploy/build-release.sh
```

This installs production PHP dependencies, builds the frontend, and assembles
`build/release/`. It restores your dev dependencies afterwards.

### 2. Upload

Upload `biertappen/` and `public_html/` over FTP, **skipping**:

- `~/biertappen/.env`
- `~/biertappen/storage/`

Overwriting `storage/` would delete sessions, logs, and the queue.

### 3. Trigger the finish step

Upload an empty file to:

```
~/biertappen/storage/app/deploy.trigger
```

Within a minute the scheduler notices it, runs migrations, rebuilds the config,
route, view and event caches, and deletes the trigger.

### 4. Verify

Check `~/biertappen/storage/logs/deploy.log`:

```
Deploy hook started at 2026-09-14 20:00:01
> php artisan migrate
> php artisan config:cache
Deploy hook finished successfully.
Trigger removed.
```

**If the trigger file is still there, the deploy failed.** It is left in place
deliberately — removing it on failure would hide a half-applied release behind a
cron job that looks like it worked. The log says why.

---

## Lemon Squeezy

Register the webhook at:

```
https://biertappen.app/lemon-squeezy/webhook
```

Subscribe to **`order_created`** and **`order_refunded`** only, and copy the
signing secret into `.env`.

An unset `LEMON_SQUEEZY_SIGNING_SECRET` makes the endpoint return 500 by design —
it fails closed rather than trusting unsigned payloads.

> **Before going live:** the premium product must be a **fixed price**. A
> pay-what-you-want variant lets a buyer name their own price for the deck
> creator. Pay-what-you-want is correct for the donation product only.

---

## Creating the first administrator

Roles are not mass-assignable, so this cannot be done through the app by a new
user. Run once via the DirectAdmin cron editor (a one-off entry you delete after
it fires), or through phpMyAdmin:

```sql
UPDATE users SET role = 'admin' WHERE email = 'you@example.com';
```

After that, further admins are promoted from **Admin → Users**.

---

## Troubleshooting

**500 on every page** — check `storage/logs/laravel.log`. Usually `storage/` is
not writable; it needs `755` and to be owned by the web user.

**Assets 404** — `public_html/build/` did not upload. The manifest references
hashed filenames, so a partial upload breaks every page.

**419 on every form** — a stale cached config with the wrong `APP_URL`, or
`storage/framework/sessions` missing. Re-trigger the deploy hook.

**Webhooks failing** — check for a redirect (`curl -I https://biertappen.app/lemon-squeezy/webhook`
should not return 301), then confirm the signing secret matches the endpoint's.

**Changes not taking effect** — the config cache is stale. Upload a new
`deploy.trigger`.
