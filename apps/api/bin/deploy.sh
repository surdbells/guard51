#!/bin/bash
set -e
cd /www/wwwroot/guard51

echo "[Deploy] Pulling latest..."
git pull origin main

echo "[Deploy] Backend deps..."
cd apps/api
composer install --no-interaction --no-dev --optimize-autoloader

echo "[Deploy] Migrations..."
php vendor/bin/doctrine-migrations migrate --no-interaction 2>/dev/null || true

echo "[Deploy] Restart PHP-FPM..."
kill -USR2 $(pgrep -o php-fpm) 2>/dev/null || true

echo "[Deploy] Restart worker..."
supervisorctl restart guard51-worker 2>/dev/null || true

echo "[Deploy] Done at $(date)"
