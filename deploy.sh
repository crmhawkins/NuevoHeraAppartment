#!/bin/bash
# Deploy script para Hawkins Suites CRM
# Uso: ssh claude@217.160.39.79 "docker exec laravel-f6irzmls5je67llxtivpv7lx /var/www/html/deploy.sh"

set -e

cd /var/www/html

echo "=== DEPLOY START ==="

# 1. Pull latest code
git config --global --add safe.directory /var/www/html 2>/dev/null
git fetch origin main
git reset --hard FETCH_HEAD
echo "Git: $(git log --oneline -1)"

# 2. Install dependencies
composer install --no-dev --no-interaction --optimize-autoloader --quiet
echo "Composer: OK"

# 3. Clear all caches
php artisan view:clear --quiet 2>/dev/null || true
php artisan route:clear --quiet 2>/dev/null || true
php artisan config:clear --quiet 2>/dev/null || true
find storage/framework/views -type f -delete 2>/dev/null || true
echo "Cache: cleared"

# 4. Reset opcache
php -r 'if(function_exists("opcache_reset")){opcache_reset();}' 2>/dev/null || true
echo "Opcache: reset"

echo "=== DEPLOY DONE ==="
