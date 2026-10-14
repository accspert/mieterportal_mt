#!/usr/bin/env bash
#
# Update deployment of the Liegenschaftsportal on the server, run from the project directory
# after the new code has been uploaded (or pulled):  ./deploy.sh
#
# All property managements share one database, so there are no per-tenant steps.
#
# ---------------------------------------------------------------------------
# One-time server setup (not done by this script)
# ---------------------------------------------------------------------------
#  1. PHP 8.2+ with pdo_mysql, zip, intl, mbstring, gd, bcmath; MySQL/MariaDB (+ mysqldump for backups)
#  2. Web root pointing to <project>/public (Hostinger: public_html -> public symlink)
#  3. .env created from .env.production.example, then: php artisan key:generate
#  4. Cron job (Hostinger: hPanel → Advanced → Cron Jobs), every minute:
#       cd /home/<user>/domains/liegenschaftsportal.ch && php artisan schedule:run >> /dev/null 2>&1
#     Runs the daily backups and the housekeeping.
#  5. Optional: a queue worker with QUEUE_CONNECTION=database. Without it (QUEUE_CONNECTION=sync)
#     mails are sent directly during the request.
#
# ---------------------------------------------------------------------------
set -euo pipefail

cd "$(dirname "$0")"

echo "==> Maintenance mode"
php artisan down --retry=30 || true

# Always leave maintenance mode, even when a step fails.
trap 'php artisan up' EXIT

echo "==> Installing PHP dependencies"
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

echo "==> Migrating the database"
php artisan migrate --force

echo "==> Roles and permissions"
php artisan db:seed --class=InitialSetupSeeder --force
php artisan permission:cache-reset

echo "==> Caching configuration, routes, views, events and Filament components"
php artisan optimize
php artisan filament:optimize

echo "==> Restarting queue workers (if any)"
php artisan queue:restart

echo "==> Health check"
php artisan mieterportal:health

echo "==> Deployment finished"
