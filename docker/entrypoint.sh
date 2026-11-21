#!/bin/sh
# Prepares the storage volume and database, then runs the given command (Apache by default).
set -e

cd /var/www/html

# A fresh volume is empty: recreate Laravel's storage layout and the SQLite file.
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views \
    storage/logs storage/app/public/attachments
[ -f "$DB_DATABASE" ] || touch "$DB_DATABASE"
chown -R www-data:www-data storage

if [ "$1" = "apache2-foreground" ]; then
    if [ -z "$APP_KEY" ]; then
        echo "APP_KEY is not set. Generate one with: docker run --rm tech-log php artisan key:generate --show" >&2
        exit 1
    fi
    su -p -s /bin/sh www-data -c "php artisan migrate --force && php artisan optimize"
fi

exec "$@"
