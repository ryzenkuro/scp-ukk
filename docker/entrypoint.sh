#!/bin/sh
set -e

# Cache configuration if in production
if [ "$APP_ENV" = "production" ]; then
    echo "Running in production mode..."
    php artisan storage:link || true
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

exec "$@"
