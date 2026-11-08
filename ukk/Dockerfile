# ==============================================================================
# Stage 1: Build Frontend Assets (Vite + React)
# ==============================================================================
FROM node:22-alpine AS frontend

WORKDIR /app

COPY package*.json ./
RUN npm ci || npm install

COPY . .
RUN npm run build

# ==============================================================================
# Stage 2: Application Runner (PHP 8.4 Alpine + Nginx + Supervisor)
# ==============================================================================
FROM php:8.4-fpm-alpine AS runner

WORKDIR /var/www/html

# Install system dependencies & build tools
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    bash \
    git \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    ca-certificates \
    $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS \
    && rm -rf /tmp/pear

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy configuration files
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Copy application files
COPY . .

# Copy built frontend assets from frontend stage
COPY --from=frontend /app/public/build ./public/build

# Install production PHP dependencies
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Set permissions for Laravel storage and cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache \
    && mkdir -p /var/log/nginx /var/log/supervisor /run/nginx \
    && chown -R www-data:www-data /var/log/nginx /run/nginx

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
