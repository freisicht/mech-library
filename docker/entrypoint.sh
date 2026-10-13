#!/bin/sh
set -e

cd /var/www/html
mkdir -p var/cache var/log data/raw public/images/units
# The web server writes the Twig and mPDF caches and its log; CLI commands run as root.
chown -R www-data:www-data var

if [ "$1" = "apache2-foreground" ]; then
    tries=0
    until vendor/bin/perpl migration:migrate --no-interaction; do
        tries=$((tries + 1))
        if [ "$tries" -ge 30 ]; then
            echo "mechlib: database not reachable, giving up" >&2
            exit 1
        fi
        echo "mechlib: waiting for the database ($tries)" >&2
        sleep 2
    done
fi

exec docker-php-entrypoint "$@"
