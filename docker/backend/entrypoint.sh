#!/bin/bash
set -e

# --- Wait for Postgres ---
if [ -n "$DB_HOST" ]; then
    echo "[entrypoint] Waiting for Postgres at $DB_HOST:${DB_PORT:-5432}..."
    until nc -z "$DB_HOST" "${DB_PORT:-5432}" 2>/dev/null; do
        sleep 1
    done
    echo "[entrypoint] Postgres is ready."
fi

# --- Fix permissions ---
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
chown -R app:app storage bootstrap/cache 2>/dev/null || true

# --- Install dependencies ---
if [ ! -d "vendor" ]; then
    echo "[entrypoint] Installing composer dependencies..."
    composer install --no-interaction --quiet
fi

# --- Setup .env ---
if [ ! -f ".env" ]; then
    echo "[entrypoint] Copying .env.example to .env..."
    cp .env.example .env
fi

# --- Generate APP_KEY if missing ---
if ! grep -q "^APP_KEY=base64:" .env 2>/dev/null; then
    echo "[entrypoint] Generating APP_KEY..."
    php artisan key:generate --no-interaction
fi

# --- Run migrations ---
echo "[entrypoint] Running migrations..."
php artisan migrate --force --no-interaction

# --- Execute CMD ---
exec "$@"
