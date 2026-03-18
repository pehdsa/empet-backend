#!/bin/bash
set -e

# --- Run migrations ---
echo "[entrypoint] Running migrations..."
php artisan migrate --force --no-interaction

# --- Cache config/routes/views ---
echo "[entrypoint] Caching config, routes and views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# --- Execute CMD ---
exec "$@"
