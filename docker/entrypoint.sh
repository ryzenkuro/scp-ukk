#!/bin/sh
set -e

# Pastikan folder buffer Nginx & storage siap dengan hak akses yang benar
mkdir -p /var/lib/nginx/tmp/client_body \
         /var/lib/nginx/tmp/proxy \
         /var/lib/nginx/tmp/fastcgi \
         /var/lib/nginx/tmp/uwsgi \
         /var/lib/nginx/tmp/scgi \
         /var/www/html/storage/app/public/journal_documentations \
         /var/www/html/storage/app/public/leave_selfies \
         /var/www/html/storage/app/public/leave_attachments \
         /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs

chown -R www-data:www-data /var/lib/nginx /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/lib/nginx /var/www/html/storage /var/www/html/bootstrap/cache

# Selalu pastikan symlink storage terpasang
php artisan storage:link --force || true

# Cache configuration if in production
if [ "$APP_ENV" = "production" ]; then
    echo "Running in production mode..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

exec "$@"
