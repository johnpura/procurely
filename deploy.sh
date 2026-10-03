#!/usr/bin/env bash
# Deploy Procurely to production. Run from the project root in Ubuntu: ./deploy.sh [--skip-tests]
set -euo pipefail

SERVER="jpura@johnpura.me"
APP_DIR="/var/www/procurely"
BRANCH="main"
URL="https://procurely.johnpura.me"
SSH_OPTS="-o ControlMaster=auto -o ControlPath=/tmp/deploy-%r@%h:%p -o ControlPersist=120"

SKIP_TESTS=0
[ "${1:-}" = "--skip-tests" ] && SKIP_TESTS=1

step() { printf '\n==> %s\n' "$1"; }
die() { printf 'ERROR: %s\n' "$1" >&2; exit 1; }

SITE_DOWN=0
on_exit() {
    status=$?
    if [ "$status" -ne 0 ] && [ "$SITE_DOWN" -eq 1 ]; then
        printf '\nDeploy failed with the site in maintenance mode.\n' >&2
        printf 'Fix the problem, then bring it back with:\n  ssh %s "cd %s && php artisan up"\n' "$SERVER" "$APP_DIR" >&2
    fi
}
trap on_exit EXIT

cd "$(dirname "$0")"

step "Checking the repository"
[ "$(git branch --show-current)" = "$BRANCH" ] || die "Not on $BRANCH."
[ -z "$(git status --porcelain)" ] || die "Uncommitted changes. Commit or stash them first."
git fetch origin --quiet
[ "$(git rev-parse HEAD)" = "$(git rev-parse "origin/$BRANCH")" ] || die "Local $BRANCH and origin/$BRANCH differ. Push or pull first."

if [ "$SKIP_TESTS" -eq 0 ]; then
    step "Running tests"
    ./vendor/bin/sail artisan test
else
    step "Skipping tests"
fi

step "Building assets"
./vendor/bin/sail npm run build
[ -f public/build/manifest.json ] || die "Build did not produce public/build/manifest.json."

step "Updating code on the server (maintenance mode on)"
SITE_DOWN=1
ssh $SSH_OPTS "$SERVER" "APP_DIR='$APP_DIR' BRANCH='$BRANCH' bash -s" <<'REMOTE'
set -euo pipefail
cd "$APP_DIR"
php artisan down --retry=60
git pull --ff-only origin "$BRANCH"
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
REMOTE

step "Copying built assets"
rsync -az --delete -e "ssh $SSH_OPTS" public/build/ "$SERVER:$APP_DIR/public/build/"

step "Caching and going live"
ssh $SSH_OPTS "$SERVER" "APP_DIR='$APP_DIR' bash -s" <<'REMOTE'
set -euo pipefail
cd "$APP_DIR"
chmod -R ug+rwX storage bootstrap/cache || true
php artisan optimize:clear
php artisan optimize
php artisan up
REMOTE
SITE_DOWN=0

step "Smoke test"
code=$(curl -s -o /dev/null -w '%{http_code}' "$URL/login")
[ "$code" = "200" ] || die "$URL/login returned HTTP $code. Check storage/logs/laravel.log on the server."

printf '\nDeployed. %s/login returned 200.\n' "$URL"
