#!/bin/sh
set -e

cd /var/www

if [ ! -f .env ]; then
    cp .env.example .env
    echo "Created .env from .env.example"
fi

if [ ! -d vendor ] || [ ! -f vendor/autoload.php ]; then
    echo "Installing composer dependencies..."
    composer install --no-interaction --optimize-autoloader
fi

if ! grep -q "^APP_KEY=base64:" .env 2>/dev/null; then
    php artisan key:generate --force
    echo "Generated application key"
fi

php artisan config:clear 2>/dev/null || true

mkdir -p storage/framework/{sessions,views,cache}
mkdir -p storage/app/invoices
mkdir -p storage/app/reports
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

exec "$@"
