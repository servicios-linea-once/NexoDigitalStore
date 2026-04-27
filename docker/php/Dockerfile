# ──────────────────────────────────────────────────────────────────────────────
# Nexo Digital Store — PHP-FPM Dockerfile
# PHP 8.4 | Laravel 13 | predis (pure PHP Redis)
# ──────────────────────────────────────────────────────────────────────────────
FROM php:8.4-fpm-alpine AS base

# System dependencies
RUN apk add --no-cache \
    bash \
    curl \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    oniguruma-dev \
    libxml2-dev \
    linux-headers \
    $PHPIZE_DEPS

# PHP extensions (no redis extension needed — using predis/predis pure PHP)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    xml \
    opcache

# Composer
COPY --from=composer:2.9 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# ──────────────────────────────────────────────────────────────────────────────
# Development target
# ──────────────────────────────────────────────────────────────────────────────
FROM base AS development

# Node.js for npm run build inside container if needed
RUN apk add --no-cache nodejs npm

# Copy composer files first for layer caching
COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader --no-interaction

# Copy source
COPY . .

# Finalize composer
RUN composer dump-autoload --optimize

# Permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
 && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]

# ──────────────────────────────────────────────────────────────────────────────
# Production target
# ──────────────────────────────────────────────────────────────────────────────
FROM base AS production

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --optimize-autoloader --no-interaction

COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative

RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
 && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
