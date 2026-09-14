#!/usr/bin/env bash
#
# Builds a complete, ready-to-run release for DirectAdmin shared hosting.
#
# Nothing is built or installed on the server: it has no SSH, and a Vite build
# routinely exceeds shared-hosting memory limits. So everything is produced here
# and uploaded as finished files.
#
# Usage:  ./deploy/build-release.sh [output-directory]
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OUT="${1:-$ROOT/build/release}"
APP_DIR="$OUT/biertappen"
WEB_DIR="$OUT/public_html"

echo "==> Building release into $OUT"
rm -rf "$OUT"
mkdir -p "$APP_DIR" "$WEB_DIR"

# ---------------------------------------------------------------------------
# 1. PHP dependencies
# ---------------------------------------------------------------------------
# --no-dev drops the test and tooling packages. The platform PHP is pinned to
# 8.3.0 in composer.json, so the tree resolved here also runs on any 8.3+ server
# regardless of the (newer) PHP on this machine.
echo "==> composer install"
cd "$ROOT"
composer install --no-dev --optimize-autoloader --no-interaction --quiet

# ---------------------------------------------------------------------------
# 2. Frontend
# ---------------------------------------------------------------------------
echo "==> npm build"
npm ci --silent
npm run build

# ---------------------------------------------------------------------------
# 3. Application files
# ---------------------------------------------------------------------------
echo "==> copying application"
for path in app bootstrap config database lang resources routes schemas storage vendor artisan composer.json composer.lock; do
    cp -r "$ROOT/$path" "$APP_DIR/"
done

# Caches and logs ship empty; a stale bootstrap cache from this machine would
# reference absolute local paths and break the app on the server.
rm -rf "$APP_DIR"/bootstrap/cache/*.php
find "$APP_DIR/storage" -type f \( -name '*.log' -o -name '*.php' \) -delete 2>/dev/null || true
rm -rf "$APP_DIR"/storage/framework/{cache,sessions,views}/* 2>/dev/null || true

# Keep the directory skeleton Laravel expects.
mkdir -p "$APP_DIR"/storage/framework/{cache/data,sessions,views}
mkdir -p "$APP_DIR"/storage/{app/public,logs}

# ---------------------------------------------------------------------------
# 4. Web root
# ---------------------------------------------------------------------------
# The application lives OUTSIDE the web root: only public/ is served, so .env,
# vendor/ and storage/ are not reachable over HTTP even if a rewrite rule breaks.
echo "==> copying web root"
cp -r "$ROOT/public/." "$WEB_DIR/"
cp "$ROOT/deploy/public_html-index.php" "$WEB_DIR/index.php"
cp "$ROOT/deploy/htaccess" "$WEB_DIR/.htaccess"

# ---------------------------------------------------------------------------
# 5. Restore the dev dependency tree on this machine
# ---------------------------------------------------------------------------
echo "==> restoring dev dependencies locally"
composer install --quiet

cat <<EOF

==> Release ready

  $APP_DIR      -> upload to ~/biertappen/
  $WEB_DIR      -> upload to ~/public_html/

Do NOT overwrite on the server:
  ~/biertappen/.env
  ~/biertappen/storage/

Then upload an empty file to ~/biertappen/storage/app/deploy.trigger to run
migrations and warm the caches. See docs/deployment.md.
EOF
