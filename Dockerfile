# Tech Log: one container with Apache + PHP 8.4 serving public/, SQLite and uploads in /var/www/html/storage.
# Build: docker build -t tech-log .
# Run:   see "Running with Docker" in README.md.

# --- Frontend assets --------------------------------------------------------------
FROM node:26-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

# --- PHP app ------------------------------------------------------------------------
FROM php:8.4-apache AS app

# pdo_sqlite (with FTS5), mbstring, curl, dom and the rest Laravel needs are built in;
# opcache is not, and Composer wants unzip.
RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip sqlite3 \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install opcache \
    && a2enmod rewrite headers \
    && echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php.ini /usr/local/etc/php/conf.d/tech-log.ini

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html

# Dependencies first so they cache between code changes.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --no-scripts --no-autoloader

COPY . .
COPY --from=assets /app/public/build ./public/build
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative \
    && php artisan storage:link \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/public \
    && chown -R www-data:www-data storage bootstrap/cache

# Production defaults; override with -e or an env file. APP_KEY has no default on purpose.
ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/var/www/html/storage/database.sqlite

VOLUME /var/www/html/storage
EXPOSE 80

COPY docker/entrypoint.sh /usr/local/bin/tech-log-entrypoint
RUN chmod +x /usr/local/bin/tech-log-entrypoint
ENTRYPOINT ["tech-log-entrypoint"]
CMD ["apache2-foreground"]
